<?php

namespace App\Models;

use PDO;
use Ramsey\Uuid\Uuid;

class StockOutRequestModel
{
    public function __construct(
        private PDO $db,
        private StockLevelModel $stock,
        private StockMovementModel $movements
    ) {
    }

    /**
     * UUID, name and logo information of a business.
     */
    public function findBranding(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT
               *
            FROM businesses
            WHERE id = :business_id
            LIMIT 1
        ");

        $stmt->execute([
            'business_id' => $businessId,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a new stock-out request.
     *
     * A newly created request is always PENDING.
     *
     * IMPORTANT:
     * No stock movement is created here.
     *
     * pending  = no reservation
     * approved = reserved
     * rejected = no stock effect
     * issued   = physical OUT movement
     */
    public function create(
        array $header,
        array $items
    ): array {
        if (!$items) {
            throw new \InvalidArgumentException(
                'At least one stock-out item is required.'
            );
        }

        $this->db->beginTransaction();

        try {
            /*
             * Validate header.
             */
            $businessId = (int) ($header['business_id'] ?? 0);
            $createdBy = isset($header['created_by'])
                ? (int) $header['created_by']
                : null;

            if ($businessId <= 0) {
                throw new \InvalidArgumentException(
                    'Invalid business.'
                );
            }

            if (empty($header['uuid'])) {
                $header['uuid'] = Uuid::uuid7()->toString();
            }

            /*
             * Insert request header.
             */
            $stmt = $this->db->prepare("
                INSERT INTO stock_out_requests (
                    uuid,
                    business_id,
                    request_date,
                    requester_name,
                    dept,
                    status,
                    created_by
                )
                VALUES (
                    :uuid,
                    :business_id,
                    :request_date,
                    :requester_name,
                    :dept,
                    'pending',
                    :created_by
                )
            ");

            $stmt->execute([
                'uuid'           => $header['uuid'],
                'business_id'    => $businessId,
                'request_date'   => $header['request_date'] ?? null,
                'requester_name' => trim(
                    (string) ($header['requester_name'] ?? '')
                ),
                'dept'           => trim(
                    (string) ($header['dept'] ?? '')
                ),
                'created_by'     => $createdBy,
            ]);

            $requestId = (int) $this->db->lastInsertId();

            /*
             * Insert request items.
             *
             * IMPORTANT:
             * model is part of the stock identity:
             *
             *     product + unit + model
             *
             * Therefore it must be stored on the request item.
             */
            $stmt = $this->db->prepare("
                INSERT INTO stock_out_request_items (
                    request_id,
                    product_id,
                    unit_id,
                    model,
                    item_condition,
                    quantity,
                    remark
                )
                VALUES (
                    :request_id,
                    :product_id,
                    :unit_id,
                    :model,
                    :item_condition,
                    :quantity,
                    :remark
                )
            ");

            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $unitId = (int) ($item['unit_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

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
                        'Stock-out quantity must be greater than zero.'
                    );
                }

                $stmt->execute([
                    'request_id' => $requestId,
                    'product_id' => $productId,
                    'unit_id'    => $unitId,
                    'model'      => StockLevelModel::normalizeModel(
                        (string) ($item['model'] ?? '')
                    ),
                    'item_condition' => StockLevelModel::normalizeCondition(
                        (string) ($item['item_condition'] ?? '')
                    ),
                    'quantity'   => $quantity,
                    'remark'     => isset($item['remark'])
                        ? mb_substr(
                            trim((string) $item['remark']),
                            0,
                            500
                        )
                        : null,
                ]);
            }

            $this->db->commit();

            return [
                'id'   => $requestId,
                'uuid' => $header['uuid'],
            ];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
 * Update a PENDING request (header + replace all items).
 */
public function update(
    int $requestId,
    int $businessId,
    string $dept, // <-- Accept dept
    array $items
): void {
    if (!$items) {
        throw new \InvalidArgumentException('At least one stock-out item is required.');
    }

    $this->db->beginTransaction();

    try {
        $stmt = $this->db->prepare("
            SELECT id, status
            FROM stock_out_requests
            WHERE id = :id AND business_id = :business_id
            FOR UPDATE
        ");
        $stmt->execute(['id' => $requestId, 'business_id' => $businessId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new \DomainException('Stock-out request not found.');
        }
        if ($request['status'] !== 'pending') {
            throw new \DomainException('Only pending requests can be edited.');
        }

        $stmt = $this->db->prepare("
            UPDATE stock_out_requests
            SET dept = :dept
            WHERE id = :id AND business_id = :business_id AND status = 'pending'
        ");
        $stmt->execute([
            'dept'         => $dept, // <-- Update dept
            'id'           => $requestId,
            'business_id'  => $businessId,
        ]);

        // ድሮ ዕቃዎችን ሰርዝ → አዲሶቹን ጻፍ
        $stmt = $this->db->prepare("
            DELETE FROM stock_out_request_items WHERE request_id = :request_id
        ");
        $stmt->execute(['request_id' => $requestId]);

        $this->insertItems($requestId, $items);

        $this->db->commit();
    } catch (\Throwable $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        throw $e;
    }
}

private function insertItems(int $requestId, array $items): void
{
    $stmt = $this->db->prepare("
        INSERT INTO stock_out_request_items
            (request_id, product_id, unit_id, model, item_condition, quantity, remark)
        VALUES
            (:request_id, :product_id, :unit_id, :model, :item_condition, :quantity, :remark)
    ");

    foreach ($items as $item) {
        $stmt->execute([
            'request_id' => $requestId,
            'product_id' => (int) $item['product_id'],
            'unit_id'    => (int) $item['unit_id'],
            'model'      => StockLevelModel::normalizeModel((string) ($item['model'] ?? '')),
            'item_condition' => StockLevelModel::normalizeCondition((string) ($item['item_condition'] ?? '')),
            'quantity'   => (int) $item['quantity'],
            'remark'     => isset($item['remark'])
                ? mb_substr(trim((string) $item['remark']), 0, 500)
                : null,
        ]);
    }
}
public function getPendingCount(int $businessId): int
{
    $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM stock_out_requests 
        WHERE business_id = :business_id AND status = 'pending'
    ");
    $stmt->execute(['business_id' => $businessId]);

    return (int) $stmt->fetchColumn();
}
    /**
     * Approve a pending stock-out request.
     *
     * Approval DOES NOT create an OUT movement.
     *
     * It only reserves the requested stock.
     *
     * Transaction:
     *
     *     lock products
     *          ↓
     *     check available stock
     *          ↓
     *     pending → approved
     */
/**
 * Approve a pending stock-out request.
 *
 * Approval DOES NOT create an OUT movement.
 * It only RESERVES the approved quantities (status = 'approved').
 *
 * $approvedQuantities: stock_out_request_items.id => approved quantity
 * Lines that are not listed are approved with the requested quantity.
 *
 * Order of work:
 *
 *     lock request
 *          ↓
 *     validate approved quantities
 *          ↓
 *     lock products
 *          ↓
 *     check available stock
 *          ↓
 *     save approved quantities
 *          ↓
 *     pending → approved
 */
public function approve(
    int $requestId,
    int $businessId,
    ?int $userId = null,
    array $approvedQuantities = []
): void {
    $this->db->beginTransaction();

    try {
        /*
         * Lock the request itself.
         */
        $stmt = $this->db->prepare("
            SELECT
                id,
                uuid,
                status,
                created_by
            FROM stock_out_requests
            WHERE id = :request_id
              AND business_id = :business_id
            FOR UPDATE
        ");

        $stmt->execute([
            'request_id'  => $requestId,
            'business_id' => $businessId,
        ]);

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new \DomainException(
                'Stock-out request not found.'
            );
        }

        if ($request['status'] !== 'pending') {
            throw new \DomainException(
                'Only pending requests can be approved.'
            );
        }

        /*
         * OPTIONAL: segregation of duties.
         * Uncomment to stop users approving their own requests.
         *
         * if ($userId !== null && (int) $request['created_by'] === $userId) {
         *     throw new \DomainException(
         *         'You cannot approve your own request.'
         *     );
         * }
         */

        /*
         * Get all request lines.
         */
        $items = $this->findItems(
            $requestId,
            $businessId
        );

        if (!$items) {
            throw new \DomainException(
                'The stock-out request has no items.'
            );
        }

        /*
         * Reject quantities sent for lines that do not belong
         * to this request.
         */
        $itemIds = array_map(
            static fn (array $item): int => (int) $item['id'],
            $items
        );

        foreach (array_keys($approvedQuantities) as $sentId) {
            if (!in_array((int) $sentId, $itemIds, true)) {
                throw new \DomainException(
                    'Approved quantity was sent for an unknown item.'
                );
            }
        }

        /*
         * Decide the approved quantity of every line.
         *
         * - not sent    → approve exactly what was requested
         * - sent        → must be between 0 and the requested quantity
         *                 (the approver may reduce, never increase)
         * - 0           → this line is not approved
         */
        $approvedByItem = [];
        $approvedLines  = [];

        foreach ($items as $item) {
            $itemId    = (int) $item['id'];
            $requested = (int) $item['quantity'];

            $approved = array_key_exists($itemId, $approvedQuantities)
                ? (int) $approvedQuantities[$itemId]
                : $requested;

            if ($approved < 0 || $approved > $requested) {
                throw new \DomainException(
                    'Approved quantity must be between 0 and the requested quantity.'
                );
            }

            $approvedByItem[$itemId] = $approved;

            if ($approved > 0) {
                $item['approved_quantity'] = $approved;
                $approvedLines[] = $item;
            }
        }

        if (!$approvedLines) {
            throw new \DomainException(
                'At least one item must be approved. Reject the request instead.'
            );
        }

        /*
         * Lock all affected products before checking stock.
         *
         * This is important for concurrent approvals.
         */
        $this->stock->lockProducts(
            $businessId,
            array_column($approvedLines, 'product_id')
        );

        /*
         * Check AVAILABLE stock:
         *
         *     on hand - existing approved reservations
         *
         * This request is still pending, so it is not yet
         * included in the reservations.
         *
         * Stock is checked per
         * product + unit + model + item_condition.
         */
        $this->stock->assertAvailable(
            $businessId,
            $approvedLines,
            'approved_quantity'
        );

        /*
         * Save the approved quantity of EVERY line
         * (0 for lines that were not approved).
         */
        $setApproved = $this->db->prepare("
            UPDATE stock_out_request_items
            SET approved_quantity = :qty
            WHERE id = :id
              AND request_id = :request_id
        ");

        foreach ($approvedByItem as $itemId => $qty) {
            $setApproved->execute([
                'qty'        => $qty,
                'id'         => $itemId,
                'request_id' => $requestId,
            ]);
        }

        /*
         * Reserve the stock by changing the request status.
         *
         * Reserved stock is calculated from requests whose
         * status is 'approved', so this is what actually
         * reserves the stock. No stock movement is created.
         */
        $stmt = $this->db->prepare("
            UPDATE stock_out_requests
            SET
                status = 'approved',
                approved_by = :approved_by,
                approved_at = NOW()
            WHERE id = :request_id
              AND business_id = :business_id
              AND status = 'pending'
        ");

        $stmt->execute([
            'approved_by' => $userId,
            'request_id'  => $requestId,
            'business_id' => $businessId,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \DomainException(
                'The stock-out request could not be approved.'
            );
        }

        $this->db->commit();

    } catch (\Throwable $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        throw $e;
    }
}

    /**
     * Reject a pending request.
     *
     * Rejection has no effect on the stock ledger.
     */
    public function reject(
        int $requestId,
        int $businessId,
        ?int $userId = null,
        ?string $reason = null
    ): void {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE stock_out_requests
                SET
                    status = 'rejected',
                    rejected_by = :rejected_by,
                    rejected_at = NOW(),
                    rejection_reason = :rejection_reason
                WHERE id = :request_id
                  AND business_id = :business_id
                  AND status = 'pending'
            ");

            $stmt->execute([
                'rejected_by'     => $userId,
                'rejection_reason'=> $reason !== null
                    ? mb_substr(trim($reason), 0, 500)
                    : null,
                'request_id'      => $requestId,
                'business_id'     => $businessId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new \DomainException(
                    'Only pending requests can be rejected.'
                );
            }

            $this->db->commit();

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Issue an approved stock-out request.
     *
     * This is the point where stock physically leaves inventory.
     *
     * Transaction:
     *
     *     lock request
     *          ↓
     *     lock products
     *          ↓
     *     verify physical stock
     *          ↓
     *     create OUT movements
     *          ↓
     *     approved → issued
     */
    public function issue(
    int $requestId,
    int $businessId,
    ?int $userId = null,
    array $itemSerials = [],   // item_id => [serial_no, ...]
    array $itemBatches = []    // item_id => [stock_in_item_id => qty]
): void {
    $this->db->beginTransaction();

    try {
        /* 1. Lock the request */
        $stmt = $this->db->prepare("
            SELECT id, uuid, status
            FROM stock_out_requests
            WHERE id = :request_id AND business_id = :business_id
            FOR UPDATE
        ");
        $stmt->execute(['request_id' => $requestId, 'business_id' => $businessId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new \DomainException('Stock-out request not found.');
        }
        if ($request['status'] !== 'approved') {
            throw new \DomainException('Only approved requests can be issued.');
        }

        /* 2. Request lines (lines approved as 0 are skipped) */
        $items = $this->findItems($requestId, $businessId);
        if (!$items) {
            throw new \DomainException('The stock-out request has no items.');
        }

        $lines = [];
        foreach ($items as $item) {
            $qty = (int) ($item['approved_quantity'] ?? $item['quantity']);
            if ($qty > 0) {
                $item['issue_quantity'] = $qty;
                $lines[] = $item;
            }
        }
        if (!$lines) {
            throw new \DomainException('There is no approved quantity to issue.');
        }

        /* 3. Lock products, 4. check physical stock */
        $this->stock->lockProducts($businessId, array_column($lines, 'product_id'));
        $this->stock->assertOnHand($businessId, $lines, 'issue_quantity');

        /* 5. Per line: allocations (real Model 19 prices) + serials + OUT movement */
        $grandTotal = 0.0;

        foreach ($lines as $item) {
            $itemId = (int) $item['id'];

            $r = $this->allocateItem(
                $businessId,
                $item,
                $itemSerials[$itemId] ?? [],
                $itemBatches[$itemId] ?? []
            );

            $this->movements->record([
                'business_id'       => $businessId,
                'product_id'        => (int) $item['product_id'],
                'unit_id'           => (int) $item['unit_id'],
                'model'             => (string) $item['model'],
                'item_condition'    => (string) $item['item_condition'],
                'movement_type'     => 'OUT',
                'quantity'          => $r['qty'],
                'reference_type'    => 'stock_out',
                'reference_id'      => $requestId,
                'reference_uuid'    => $request['uuid'],
                'reference_item_id' => $itemId,
                'remark'            => $item['remark'] ?? null,
                'created_by'        => $userId,
            ]);

            $grandTotal += $r['total'];
        }

        /* 6. Mark the request as issued */
        $stmt = $this->db->prepare("
            UPDATE stock_out_requests
            SET status = 'issued', issued_by = :issued_by, issued_at = NOW()
            WHERE id = :request_id AND business_id = :business_id AND status = 'approved'
        ");
        $stmt->execute([
            'issued_by'   => $userId,
            'request_id'  => $requestId,
            'business_id' => $businessId,
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new \DomainException('The stock-out request could not be issued.');
        }

        $this->db->commit();

    } catch (\Throwable $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        throw $e;
    }
}
    /**
     * Find a request belonging to the current business.
     */
    public function findByUuid(
        string $uuid,
        int $businessId
    ): ?array {
        $stmt = $this->db->prepare("
            SELECT
                r.*
            FROM stock_out_requests r
            WHERE r.uuid = :uuid
              AND r.business_id = :business_id
            LIMIT 1
        ");

        $stmt->execute([
            'uuid'        => $uuid,
            'business_id' => $businessId,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get request items.
     *
     * Model is included because stock identity is:
     *
     *     product + unit + model
     */
   public function findItems(
    int $requestId,
    int $businessId
): array {
    $stmt = $this->db->prepare("
        SELECT
            i.id,
            i.product_id,
            i.unit_id,
            i.quantity,
            i.approved_quantity,
            i.model,
            i.item_condition,
            i.remark,

            p.uuid AS product_uuid,
            p.name AS product_name,
            p.is_serialized,

            u.uuid AS unit_uuid,
            u.name AS unit_name

        FROM stock_out_request_items i

        INNER JOIN stock_out_requests r
            ON r.id = i.request_id
           AND r.business_id = :business_id

        INNER JOIN products p
            ON p.id = i.product_id
           AND p.business_id = r.business_id

        INNER JOIN units u
            ON u.id = i.unit_id
           AND u.business_id = r.business_id

        WHERE i.request_id = :request_id

        ORDER BY i.id ASC
    ");

    $stmt->execute([
        'business_id' => $businessId,
        'request_id'  => $requestId,
    ]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item['id']         = (int) $item['id'];
        $item['product_id'] = (int) $item['product_id'];
        $item['unit_id']    = (int) $item['unit_id'];
        $item['quantity']   = (int) $item['quantity'];
        $item['is_serialized'] = (int) $item['is_serialized'];
        $item['approved_quantity'] = $item['approved_quantity'] !== null
            ? (int) $item['approved_quantity']
            : null;

        $item['item_condition'] = StockLevelModel::normalizeCondition(
            (string) $item['item_condition']
        );

        $item['model'] = StockLevelModel::normalizeModel(
            (string) ($item['model'] ?? '')
        );
    }

    unset($item);

    return $items;
}


    /**
 * Paginated list of stock-out requests.
 *
 * $onlyUserId !== null  → only requests created by that user.
 */
public function paginate(
    int $businessId,
    array $filters,
    ?int $onlyUserId = null
): array {
    $search  = trim((string) ($filters['search'] ?? ''));
    $status  = (string) ($filters['status'] ?? '');
    $perPage = (int) ($filters['per_page'] ?? 25);
    $page    = max(1, (int) ($filters['page'] ?? 1));

    $where  = ['r.business_id = :business_id'];
    $params = [':business_id' => $businessId];

    // ዋናው የሚና ማጣሪያ
    if ($onlyUserId !== null) {
        $where[] = 'r.created_by = :created_by';
        $params[':created_by'] = $onlyUserId;
    }

    if (in_array($status, ['pending', 'approved', 'rejected', 'issued'], true)) {
        $where[] = 'r.status = :status';
        $params[':status'] = $status;
    }

    if ($search !== '') {
        // ተመሳሳይ named parameter ደግሞ መጠቀም ስለማይቻል (emulate off) ሁለት ስም
        $where[] = '(:q1 OR r.requester_name LIKE :q2)';
        $params[':q1'] = '%' . $search . '%';
        $params[':q2'] = '%' . $search . '%';
    }

    $whereSql = implode(' AND ', $where);

    // ጠቅላላ ብዛት
    $stmt = $this->db->prepare("
        SELECT COUNT(*) FROM stock_out_requests r WHERE $whereSql
    ");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    $stmt = $this->db->prepare("
        SELECT
            r.id,
            r.uuid,
            r.request_date,
            r.requester_name,
            r.dept,
            r.status,
            r.created_by,
            (SELECT COUNT(*)
               FROM stock_out_request_items i
              WHERE i.request_id = r.id) AS item_count
        FROM stock_out_requests r
        WHERE $whereSql
        ORDER BY r.id DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'rows'       => $rows,
        'pagination' => [
            'search'      => $search,
            'status'      => $status,
            'per_page'    => $perPage,
            'page'        => $page,
            'total'       => $total,
            'total_pages' => $totalPages,
            'from'        => $total ? $offset + 1 : 0,
            'to'          => $offset + count($rows),
        ],
    ];
}

/**
     * Find a request along with its items AND available serial numbers (Strictly for Model 22 Issuance).
     */
   public function findForIssuance(string $uuid, int $businessId): ?array
{
    $request = $this->findByUuid($uuid, $businessId);
    if (!$request) {
        return null;
    }

    $items = $this->findItems((int) $request['id'], $businessId);

    $serialStmt = $this->db->prepare("
        SELECT sn.id, sn.serial_no, sn.model, sn.stock_in_item_id,
               i.unit_price, st.receipt_no, st.received_date
        FROM inventory_serials sn
        JOIN stock_in_items i ON i.id = sn.stock_in_item_id
        JOIN stock_ins st     ON st.id = i.stock_in_id
        WHERE sn.business_id = :business_id
          AND sn.product_id  = :product_id
          AND sn.unit_id     = :unit_id
          AND LOWER(TRIM(sn.model)) = :model
          AND st.item_condition = :condition
          AND sn.status = 'IN_STOCK'
        ORDER BY sn.serial_no ASC
    ");

    foreach ($items as &$item) {
        $item['available_serials'] = [];
        $item['batches']           = [];

        if ((int) $item['is_serialized'] === 1) {
            // Serialized: the chosen serials decide the stock-in line and price.
            $serialStmt->execute([
                'business_id' => $businessId,
                'product_id'  => $item['product_id'],
                'unit_id'     => $item['unit_id'],
                'model'       => StockLevelModel::normalizeModel((string) $item['model']),
                'condition'   => $item['item_condition'],
            ]);
            $item['available_serials'] = $serialStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Not serialized: the storekeeper picks the stock-in line.
            $item['batches'] = $this->batchesForItem($businessId, $item);
        }
    }
    unset($item);

    $request['items'] = $items;
    return $request;
}
    public function batchesForItem(int $businessId, array $item): array
{
    $stmt = $this->db->prepare("
        SELECT i.id AS stock_in_item_id,
               s.receipt_no, s.received_date, s.supplier_name,
               i.unit_price,
               i.quantity - COALESCE((
                   SELECT SUM(a.quantity)
                   FROM stock_out_item_allocations a
                   WHERE a.stock_in_item_id = i.id
               ), 0) AS remaining
        FROM stock_in_items i
        JOIN stock_ins s ON s.id = i.stock_in_id
        WHERE s.business_id = :b
          AND s.status = 'posted'
          AND s.item_condition = :c
          AND i.product_id = :p
          AND i.unit = :u
          AND LOWER(TRIM(i.model)) = :m
        HAVING remaining > 0
        ORDER BY s.received_date, i.id
    ");
    $stmt->execute([
        'b' => $businessId,
        'c' => $item['item_condition'],
        'p' => $item['product_id'],
        'u' => $item['unit_id'],
        'm' => StockLevelModel::normalizeModel((string) $item['model']),
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Validates the storekeeper's choice for ONE request item, saves the
 * allocation rows (real Model 19 prices) and returns the issued quantity
 * and the line total.
 *
 * @return array{qty:int,total:float}
 */
private function allocateItem(int $businessId, array $item, array $serials, array $batches): array
{
    $need = (int) ($item['approved_quantity'] ?? $item['quantity']);
    if ($need <= 0) {
        return ['qty' => 0, 'total' => 0.0];      // line approved as 0: nothing issued
    }

    $model  = StockLevelModel::normalizeModel((string) $item['model']);
    $chosen = [];   // stock_in_item_id => qty
    $price  = [];   // stock_in_item_id => unit_price
    $serialIds = [];

    if ((int) $item['is_serialized'] === 1) {
        // ---- serialized: the serials decide the stock-in line and price ----
        if (count($serials) !== $need) {
            throw new \DomainException("ሰሪያል ብዛት ({$need}) መመረጥ አለበት።");
        }

        $ph = implode(',', array_fill(0, count($serials), '?'));
        $q  = $this->db->prepare("
            SELECT sn.id, sn.stock_in_item_id, i.unit_price
            FROM inventory_serials sn
            JOIN stock_in_items i ON i.id = sn.stock_in_item_id
            JOIN stock_ins st     ON st.id = i.stock_in_id
            WHERE sn.business_id = ? AND sn.product_id = ? AND sn.unit_id = ?
              AND LOWER(TRIM(sn.model)) = ? AND st.item_condition = ?
              AND sn.status = 'IN_STOCK'
              AND sn.serial_no IN ($ph)
            FOR UPDATE
        ");
        $q->execute([$businessId, $item['product_id'], $item['unit_id'],
                     $model, $item['item_condition'], ...$serials]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) !== $need) {
            throw new \DomainException('የተመረጡ ሰሪያሎች በስቶክ የሉም ወይም ከዕቃው ጋር አይመሳሰሉም።');
        }
        foreach ($rows as $r) {
            $sid = (int) $r['stock_in_item_id'];
            $chosen[$sid] = ($chosen[$sid] ?? 0) + 1;
            $price[$sid]  = $r['unit_price'];
            $serialIds[]  = (int) $r['id'];
        }
    } else {
        // ---- not serialized: the storekeeper chose the stock-in lines ----
        if (!$batches) {
            throw new \DomainException('ከየትኛው የግዥ መዝገብ እንደሚወጣ ይምረጡ።');
        }

        $ids = array_keys($batches);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $q   = $this->db->prepare("
            SELECT i.id, i.unit_price,
                   i.quantity - COALESCE((SELECT SUM(a.quantity)
                                          FROM stock_out_item_allocations a
                                          WHERE a.stock_in_item_id = i.id), 0) AS remaining
            FROM stock_in_items i
            JOIN stock_ins s ON s.id = i.stock_in_id
            WHERE i.id IN ($ph)
              AND s.business_id = ? AND s.status = 'posted'
              AND s.item_condition = ?
              AND i.product_id = ? AND i.unit = ?
              AND LOWER(TRIM(i.model)) = ?
            FOR UPDATE
        ");
        $q->execute([...$ids, $businessId, $item['item_condition'],
                     $item['product_id'], $item['unit_id'], $model]);
        $lines = array_column($q->fetchAll(PDO::FETCH_ASSOC), null, 'id');

        $sum = 0;
        foreach ($batches as $sid => $qty) {
            if (!isset($lines[$sid])) {
                throw new \DomainException('የተመረጠው የግዥ መዝገብ ከዕቃው ጋር አይመሳሰልም።');
            }
            if ($qty > (int) $lines[$sid]['remaining']) {
                throw new \DomainException('ከተመረጠው የግዥ መዝገብ ቀሪ ብዛት በላይ ጠይቀዋል።');
            }
            $sum += $qty;
            $price[$sid] = $lines[$sid]['unit_price'];
        }
        if ($sum !== $need) {
            throw new \DomainException("የተመረጠው ጠቅላላ ብዛት ({$sum}) ከተፈቀደው ({$need}) ጋር እኩል መሆን አለበት።");
        }
        $chosen = $batches;
    }

    // ---- save allocations (price copied from stock_in_items) ----
    $ins   = $this->db->prepare("
        INSERT INTO stock_out_item_allocations
            (stock_out_item_id, stock_in_item_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    $total = 0.0;
    foreach ($chosen as $sid => $qty) {
        $ins->execute([$item['id'], $sid, $qty, $price[$sid]]);
        $total += $qty * (float) $price[$sid];
    }

    // ---- mark serials as issued ----
    if ($serialIds) {
        $ph  = implode(',', array_fill(0, count($serialIds), '?'));
        $upd = $this->db->prepare("
            UPDATE inventory_serials
            SET status = 'ISSUED', stock_out_item_id = ?, issued_at = NOW()
            WHERE id IN ($ph) AND status = 'IN_STOCK'
        ");
        $upd->execute([$item['id'], ...$serialIds]);
        if ($upd->rowCount() !== count($serialIds)) {
            throw new \DomainException('ሰሪያሎች በሌላ ሰው ቀድመው ወጥተዋል። እባክዎ እንደገና ይሞክሩ።');
        }
    }

    // ---- issued quantity on the request line ----
    $this->db->prepare("UPDATE stock_out_request_items SET issued_quantity = ? WHERE id = ?")
             ->execute([$need, $item['id']]);

    return ['qty' => $need, 'total' => round($total, 2)];
}
/** One row per allocation (receipt) of an issued request, with serial numbers. */
public function findIssuedLines(int $requestId, int $businessId): array
{
    $stmt = $this->db->prepare("
        SELECT a.id, a.stock_out_item_id, a.stock_in_item_id,
               a.quantity, a.unit_price,
               p.name AS product_name, ri.model, ri.remark,
               un.name AS unit_name,
               st.receipt_no, st.received_date
        FROM stock_out_item_allocations a
        JOIN stock_out_request_items ri ON ri.id = a.stock_out_item_id
        JOIN stock_out_requests rq      ON rq.id = ri.request_id
        JOIN products p                 ON p.id = ri.product_id
        JOIN units un                   ON un.id = ri.unit_id
        JOIN stock_in_items i           ON i.id = a.stock_in_item_id
        JOIN stock_ins st               ON st.id = i.stock_in_id
        WHERE ri.request_id = :rid AND rq.business_id = :b
        ORDER BY ri.id, st.received_date, a.id
    ");
    $stmt->execute(['rid' => $requestId, 'b' => $businessId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        return [];
    }

    $itemIds = array_values(array_unique(array_column($rows, 'stock_out_item_id')));
    $ph = implode(',', array_fill(0, count($itemIds), '?'));

    $s = $this->db->prepare("
        SELECT stock_out_item_id, stock_in_item_id, serial_no
        FROM inventory_serials
        WHERE stock_out_item_id IN ($ph)
        ORDER BY serial_no
    ");
    $s->execute($itemIds);

    $map = [];
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $map[$r['stock_out_item_id']][$r['stock_in_item_id']][] = $r['serial_no'];
    }

    foreach ($rows as &$row) {
        $row['serials'] = $map[$row['stock_out_item_id']][$row['stock_in_item_id']] ?? [];
    }
    unset($row);

    return $rows;
}
}