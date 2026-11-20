<?php

namespace App\Models;

use App\Exceptions\InsufficientStockException;
use PDO;

/**
 * Calculates stock levels from the stock movement ledger.
 *
 * Stock identity:
 *
 *   product + unit + model + item_condition
 *
 * Stock movement types:
 *
 *   IN  = stock physically entered the store
 *   OUT = stock physically left the store
 *
 * Movement type does NOT describe the reason/source.
 * The reason/source belongs in reference_type, for example:
 *
 *   stock_in, stock_out, return, adjustment, loss, damage, transfer
 *
 * Stock quantities:
 *
 *   on_hand   = total IN - total OUT
 *   reserved  = quantities of APPROVED stock-out requests not yet issued
 *   available = on_hand - reserved
 *
 * Request status:
 *
 *   pending  = not reserved
 *   approved = reserved
 *   rejected = not reserved
 *   issued   = physically issued; OUT movement already exists
 */
class StockLevelModel
{
    /**
     * Allowed item conditions (must match the ENUM in the database).
     */
    public const CONDITIONS = ['new', 'used', 'consumable'];

    /**
     * Stock movement quantity signed according to movement type.
     *
     * IMPORTANT:
     * movement_type contains ONLY IN or OUT.
     */
    private const SIGNED_QTY = "
        CASE
            WHEN m.movement_type = 'IN'
                THEN CAST(m.quantity AS SIGNED)
            WHEN m.movement_type = 'OUT'
                THEN -CAST(m.quantity AS SIGNED)
            ELSE 0
        END
    ";

    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Normalize model text for application-level comparison.
     *
     * This must match the normalization used when grouping
     * product/unit/model combinations.
     */
    public static function normalizeModel(string $model): string
    {
        return mb_strtolower(trim($model));
    }

    /**
     * Stable key for a model.
     *
     * Useful when passing product/unit/model combinations
     * through HTML/JavaScript.
     */
    public static function modelKey(string $model): string
    {
        return sha1(self::normalizeModel($model));
    }

    /**
     * Validate and normalize an item condition.
     *
     * @throws \InvalidArgumentException when the condition is not allowed.
     */
    public static function normalizeCondition(string $condition): string
    {
        $condition = mb_strtolower(trim($condition));

        if (!in_array($condition, self::CONDITIONS, true)) {
            throw new \InvalidArgumentException('Invalid item condition.');
        }

        return $condition;
    }

    /**
     * Return every product + unit + model + condition combination
     * that currently has stock available for issue.
     *
     * Available:
     *
     *     on_hand - reserved
     *
     * Only active products and active units are returned.
     */
    public function availableOptions(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.id AS product_id,
                p.uuid AS product_uuid,
                p.name AS product_name,

                un.id AS unit_id,
                un.uuid AS unit_uuid,
                un.name AS unit_name,

                l.model AS model,
                l.item_condition AS item_condition,
                l.on_hand AS on_hand,

                COALESCE(q.reserved, 0) AS reserved,

                (
                    l.on_hand - COALESCE(q.reserved, 0)
                ) AS available

            FROM (
                SELECT
                    m.product_id,
                    m.unit_id,
                    TRIM(m.model) AS model,
                    m.item_condition,

                    SUM(
                        " . self::SIGNED_QTY . "
                    ) AS on_hand

                FROM stock_movements m

                WHERE m.business_id = :business_id_ledger

                GROUP BY
                    m.product_id,
                    m.unit_id,
                    TRIM(m.model),
                    m.item_condition

                HAVING SUM(
                    " . self::SIGNED_QTY . "
                ) > 0
            ) l

            INNER JOIN products p
                ON p.id = l.product_id
                AND p.business_id = :business_id_product
                AND p.is_active = 1

            INNER JOIN units un
                ON un.id = l.unit_id
                AND un.business_id = :business_id_unit
                AND un.is_active = 1

            LEFT JOIN (
                SELECT
                    ri.product_id,
                    ri.unit_id,
                    TRIM(ri.model) AS model,
                    ri.item_condition,

                    SUM(
                        COALESCE(
                            ri.approved_quantity,
                            ri.quantity
                        )
                    ) AS reserved

                FROM stock_out_request_items ri

                INNER JOIN stock_out_requests rq
                    ON rq.id = ri.request_id

                WHERE rq.business_id = :business_id_reserved
                  AND rq.status = 'approved'

                GROUP BY
                    ri.product_id,
                    ri.unit_id,
                    TRIM(ri.model),
                    ri.item_condition
            ) q
                ON q.product_id = l.product_id
                AND q.unit_id = l.unit_id
                AND q.item_condition = l.item_condition
                AND LOWER(q.model) = LOWER(l.model)

            WHERE (
                l.on_hand - COALESCE(q.reserved, 0)
            ) > 0

            ORDER BY
                p.name ASC,
                un.name ASC,
                l.model ASC,
                l.item_condition ASC
        ");

        $stmt->execute([
            'business_id_ledger'   => $businessId,
            'business_id_product'  => $businessId,
            'business_id_unit'     => $businessId,
            'business_id_reserved' => $businessId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['product_id'] = (int) $row['product_id'];
            $row['unit_id'] = (int) $row['unit_id'];

            $row['on_hand'] = (int) $row['on_hand'];
            $row['reserved'] = (int) $row['reserved'];
            $row['available'] = (int) $row['available'];

            $row['model'] = (string) $row['model'];
            $row['model_key'] = self::modelKey($row['model']);
            $row['item_condition'] = (string) $row['item_condition'];
        }

        unset($row);

        return $rows;
    }

    /**
     * Return physical stock currently in the store.
     *
     * on_hand = IN - OUT
     *
     * Reservations are NOT considered here.
     */
    public function onHandFor(
        int $businessId,
        int $productId,
        int $unitId,
        string $model,
        string $condition
    ): int {
        $model = self::normalizeModel($model);
        $condition = self::normalizeCondition($condition);

        $stmt = $this->db->prepare("
            SELECT COALESCE(
                SUM(
                    " . self::SIGNED_QTY . "
                ),
                0
            )

            FROM stock_movements m

            WHERE m.business_id = :business_id
              AND m.product_id = :product_id
              AND m.unit_id = :unit_id
              AND m.item_condition = :condition
              AND LOWER(TRIM(m.model)) = :model
        ");

        $stmt->execute([
            'business_id' => $businessId,
            'product_id'  => $productId,
            'unit_id'     => $unitId,
            'condition'   => $condition,
            'model'       => $model,
        ]);

        return max(0, (int) $stmt->fetchColumn());
    }

    /**
     * Return stock that can currently be issued.
     *
     * available = on_hand - approved reservations
     *
     * Pending requests do not reduce availability.
     *
     * Issued requests do not reduce availability because their
     * physical quantity is already represented by an OUT movement.
     */
    public function availableFor(
        int $businessId,
        int $productId,
        int $unitId,
        string $model,
        string $condition
    ): int {
        $model = self::normalizeModel($model);
        $condition = self::normalizeCondition($condition);

        $stmt = $this->db->prepare("
            SELECT
                (
                    COALESCE(
                        (
                            SELECT SUM(
                                " . self::SIGNED_QTY . "
                            )
                            FROM stock_movements m

                            WHERE m.business_id = :ledger_business_id
                              AND m.product_id = :ledger_product_id
                              AND m.unit_id = :ledger_unit_id
                              AND m.item_condition = :ledger_condition
                              AND LOWER(TRIM(m.model)) = :ledger_model
                        ),
                        0
                    )

                    -

                    COALESCE(
                        (
                            SELECT SUM(
                                COALESCE(
                                    ri.approved_quantity,
                                    ri.quantity
                                )
                            )

                            FROM stock_out_request_items ri

                            INNER JOIN stock_out_requests rq
                                ON rq.id = ri.request_id

                            WHERE rq.business_id = :reserved_business_id
                              AND rq.status = 'approved'
                              AND ri.product_id = :reserved_product_id
                              AND ri.unit_id = :reserved_unit_id
                              AND ri.item_condition = :reserved_condition
                              AND LOWER(TRIM(ri.model)) = :reserved_model
                        ),
                        0
                    )
                ) AS available
        ");

        $stmt->execute([
            'ledger_business_id'    => $businessId,
            'ledger_product_id'     => $productId,
            'ledger_unit_id'        => $unitId,
            'ledger_condition'      => $condition,
            'ledger_model'          => $model,

            'reserved_business_id'  => $businessId,
            'reserved_product_id'   => $productId,
            'reserved_unit_id'      => $unitId,
            'reserved_condition'    => $condition,
            'reserved_model'        => $model,
        ]);

        return max(0, (int) $stmt->fetchColumn());
    }

    /**
     * Lock product rows before performing a stock-changing operation.
     *
     * This should be called INSIDE the same database transaction
     * that subsequently checks and changes stock.
     *
     * Example:
     *
     *     $db->beginTransaction();
     *
     *     $levels->lockProducts($businessId, $productIds);
     *     $levels->assertAvailable(...);
     *
     *     // approve / issue / cancel / adjustment...
     *
     *     $db->commit();
     */
    public function lockProducts(
        int $businessId,
        array $productIds
    ): void {
        $ids = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $productIds),
                    static fn (int $id): bool => $id > 0
                )
            )
        );

        if (!$ids) {
            return;
        }

        /*
         * Always lock in the same order.
         *
         * This reduces the possibility of deadlocks when
         * two transactions affect multiple products.
         */
        sort($ids, SORT_NUMERIC);

        $placeholders = implode(
            ',',
            array_fill(0, count($ids), '?')
        );

        $stmt = $this->db->prepare("
            SELECT id
            FROM products
            WHERE business_id = ?
              AND id IN ($placeholders)
            ORDER BY id
            FOR UPDATE
        ");

        $stmt->execute([
            $businessId,
            ...$ids,
        ]);
    }

    /**
     * Assert that the requested quantities are available.
     *
     * Available means:
     *
     *     on_hand - approved reservations
     *
     * Duplicate lines for the same product + unit + model + condition
     * are aggregated before checking.
     *
     * Example:
     *
     *     Laptop / pcs / Dell / new = 5
     *     Laptop / pcs / Dell / new = 7
     *
     * is checked as:
     *
     *     Laptop / pcs / Dell / new = 12
     *
     * while "Laptop / pcs / Dell / used" is checked separately.
     */
    public function assertAvailable(
        int $businessId,
        array $lines,
        string $qtyKey
    ): void {
        foreach ($this->totals($lines, $qtyKey) as $total) {
            $available = $this->availableFor(
                $businessId,
                $total['product_id'],
                $total['unit_id'],
                $total['model'],
                $total['item_condition']
            );

            if ($total['qty'] > $available) {
                throw new InsufficientStockException(
                    $total['product_id'],
                    $total['unit_id'],
                    $total['model'],
                    $total['item_condition'],
                    $total['qty'],
                    $available
                );
            }
        }
    }

    /**
     * Assert that the requested quantities are physically
     * present in the store.
     *
     * This ignores reservations.
     *
     * Use this when an operation requires actual physical stock,
     * rather than merely unreserved stock.
     */
    public function assertOnHand(
        int $businessId,
        array $lines,
        string $qtyKey
    ): void {
        foreach ($this->totals($lines, $qtyKey) as $total) {
            $onHand = $this->onHandFor(
                $businessId,
                $total['product_id'],
                $total['unit_id'],
                $total['model'],
                $total['item_condition']
            );

            if ($total['qty'] > $onHand) {
                throw new InsufficientStockException(
                    $total['product_id'],
                    $total['unit_id'],
                    $total['model'],
                    $total['item_condition'],
                    $total['qty'],
                    $onHand
                );
            }
        }
    }

    /**
     * Calculate stock quantities grouped by:
     *
     *     product_id + unit_id + normalized model + item_condition
     *
     * This prevents duplicate request lines from bypassing
     * the stock validation, and prevents stock of different
     * conditions from being added together.
     */
    private function totals(
        array $lines,
        string $qtyKey
    ): array {
        if ($qtyKey === '') {
            throw new \InvalidArgumentException(
                'Quantity field is required.'
            );
        }

        $totals = [];

        foreach ($lines as $line) {
            if (
                !isset($line['product_id']) ||
                !isset($line['unit_id'])
            ) {
                throw new \InvalidArgumentException(
                    'Each stock line must contain product_id and unit_id.'
                );
            }

            if (!array_key_exists($qtyKey, $line)) {
                throw new \InvalidArgumentException(
                    "Missing quantity field: {$qtyKey}"
                );
            }

            $productId = (int) $line['product_id'];
            $unitId = (int) $line['unit_id'];

            $model = self::normalizeModel(
                (string) ($line['model'] ?? '')
            );

            // Throws if missing or invalid.
            $condition = self::normalizeCondition(
                (string) ($line['item_condition'] ?? '')
            );

            $quantity = (int) $line[$qtyKey];

            if ($productId <= 0) {
                throw new \InvalidArgumentException(
                    'Invalid product.'
                );
            }

            if ($unitId <= 0) {
                throw new \InvalidArgumentException(
                    'Invalid unit.'
                );
            }

            if ($quantity <= 0) {
                throw new \InvalidArgumentException(
                    'Stock quantity must be greater than zero.'
                );
            }

            $key = $productId
                . '|'
                . $unitId
                . '|'
                . $model
                . '|'
                . $condition;

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'product_id'     => $productId,
                    'unit_id'        => $unitId,
                    'model'          => $model,
                    'item_condition' => $condition,
                    'qty'            => 0,
                ];
            }

            $totals[$key]['qty'] += $quantity;
        }

        return array_values($totals);
    }
}