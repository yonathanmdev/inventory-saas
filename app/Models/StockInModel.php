<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use PDO;

class StockInModel
{
    public function __construct(
        private PDO $db,
        private StockLevelModel $levels,
        private StockMovementModel $movements
    ) {}

    public function nextReceiptNo(int $businessId): int
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(CAST(receipt_no AS UNSIGNED)), 0) + 1
            FROM stock_ins WHERE business_id = :business_id
        ");
        $stmt->execute(['business_id' => $businessId]);

        return (int) $stmt->fetchColumn();
    }

    public function receiptNoExists(int $businessId, string $receiptNo): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM stock_ins
            WHERE business_id = :business_id AND receipt_no = :receipt_no LIMIT 1
        ");
        $stmt->execute(['business_id' => $businessId, 'receipt_no' => $receiptNo]);

        return (bool) $stmt->fetchColumn();
    }

    /** Header + lines + stock movements in one transaction. */
    public function createWithItems(array $h, array $items): int
    {
        $this->db->beginTransaction();

        try {
            // 1. Create Stock In header (Added 'source' column and placeholder)
            $this->db->prepare("
                INSERT INTO stock_ins (
                    uuid,
                    business_id,
                    receipt_no,
                    expenditure_item_no,
                    incoming_register_no,
                    classification,
                    store_no,
                    shelf_no,
                    department,
                    supplier_name,
                    received_date,
                    item_condition,
                    source,
                    total_amount,
                    created_by
                ) VALUES (
                    :uuid,
                    :business_id,
                    :receipt_no,
                    :expenditure_item_no,
                    :incoming_register_no,
                    :classification,
                    :store_no,
                    :shelf_no,
                    :department,
                    :supplier_name,
                    :received_date,
                    :item_condition,
                    :source,
                    :total_amount,
                    :created_by
                )
            ")->execute($h);

            $stockInId = (int) $this->db->lastInsertId();

            // 2. Prepare Stock-In item insert
            $itemStmt = $this->db->prepare("
                INSERT INTO stock_in_items (
                    stock_in_id,
                    product_id,
                    model,
                    page_from,
                    page_to,
                    unit,
                    quantity,
                    unit_price,
                    total_price
                ) VALUES (
                    :stock_in_id,
                    :product_id,
                    :model,
                    :page_from,
                    :page_to,
                    :unit_id,
                    :quantity,
                    :unit_price,
                    :total_price
                )
            ");

            // 3. Prepare serial insert
            $serialStmt = $this->db->prepare("
                INSERT INTO inventory_serials (
                    uuid,
                    business_id,
                    product_id,
                    unit_id,
                    model,
                    serial_no,
                    status,
                    stock_in_item_id
                ) VALUES (
                    :uuid,
                    :business_id,
                    :product_id,
                    :unit_id,
                    :model,
                    :serial_no,
                    'IN_STOCK',
                    :stock_in_item_id
                )
            ");

            // 4. Insert items
            foreach ($items as $item) {

                $itemStmt->execute([
                    'stock_in_id' => $stockInId,
                    'product_id'  => $item['product_id'],
                    'model'       => $item['model'],
                    'page_from'   => $item['page_from'],
                    'page_to'     => $item['page_to'],
                    'unit_id'     => $item['unit_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => number_format($item['unit_cents'] / 100, 2, '.', ''),
                    'total_price' => number_format($item['total_cents'] / 100, 2, '.', ''),
                ]);

                $itemId = (int) $this->db->lastInsertId();

                // 5. Save serial numbers for THIS row only
                if (!empty($item['track_serials'])) {
                    foreach ($item['serials'] as $serial) {
                        $serialStmt->execute([
                            'uuid'            => Uuid::uuid7()->toString(),
                            'business_id'     => (int) $h['business_id'],
                            'product_id'      => (int) $item['product_id'],
                            'unit_id'         => (int) $item['unit_id'],
                            'model'           => (string) ($item['model'] ?? ''),
                            'serial_no'       => $serial,
                            'stock_in_item_id'=> $itemId,
                        ]);
                    }
                }

                // 6. Immediately register stock movement
                $this->movements->record([
                    'business_id'       => (int) $h['business_id'],
                    'product_id'        => (int) $item['product_id'],
                    'unit_id'           => (int) $item['unit_id'],
                    'model'             => (string) ($item['model'] ?? ''),
                    'item_condition'    => (string) ($h['item_condition'] ?? ''),
                    'movement_type'     => 'IN',
                    'quantity'          => (int) $item['quantity'],
                    'reference_type'    => 'stock_in',
                    'reference_id'      => $stockInId,
                    'reference_uuid'    => $h['uuid'],
                    'reference_item_id' => $itemId,
                    'remark'            => 'Receipt ' . $h['receipt_no'],
                    'created_by'        => $h['created_by'] ?? null,
                ]);
            }

            // 7. Everything succeeded
            $this->db->commit();

            return $stockInId;

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

private function buildFilter(int $businessId, string $search, string $condition): array
{
    $where  = 's.business_id = :business_id';
    $params = ['business_id' => $businessId];

    if ($condition !== '') {
        $where .= ' AND s.item_condition = :condition';
        $params['condition'] = $condition;
    }

    if ($search !== '') {
        $where .= ' AND (s.receipt_no LIKE :s1 OR s.supplier_name LIKE :s2 OR s.receiver_name LIKE :s3)';

        $like = '%' . addcslashes($search, '\\%_') . '%';
        $params['s1'] = $params['s2'] = $params['s3'] = $like;
    }

    return [$where, $params];
}

public function countByBusinessId(int $businessId, string $search = '', string $condition = ''): int
{
    [$where, $params] = $this->buildFilter($businessId, $search, $condition);

    $stmt = $this->db->prepare("SELECT COUNT(*) FROM stock_ins s WHERE {$where}");
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

public function findPageByBusinessId(
    int $businessId,
    string $search,
    string $condition,
    int $limit,
    int $offset
): array {
    [$where, $params] = $this->buildFilter($businessId, $search, $condition);

    $stmt = $this->db->prepare("
        SELECT
            s.uuid, s.receipt_no, s.supplier_name,
            s.received_date, s.item_condition, s.total_amount, s.created_at,
            (SELECT COUNT(*) FROM stock_in_items i WHERE i.stock_in_id = s.id) AS item_count
        FROM stock_ins s
        WHERE {$where}
        ORDER BY s.received_date DESC, s.id DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function findByUuid(string $uuid, int $businessId): ?array
{
    $stmt = $this->db->prepare("
        SELECT s.*
        FROM stock_ins s
        WHERE s.uuid = :uuid AND s.business_id = :business_id
        LIMIT 1
    ");
    $stmt->execute(['uuid' => $uuid, 'business_id' => $businessId]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function findItems(int $stockInId, int $businessId): array
{
    // 1. Fetch stock-in items with product/unit names AND their UUIDs
    $stmt = $this->db->prepare("
        SELECT
            i.id, i.model, i.page_from, i.page_to,
            i.quantity, i.unit_price, i.total_price,
            p.uuid  AS product_uuid,
            p.name  AS product_name,
            un.uuid AS unit_uuid,
            un.name AS unit_name
        FROM stock_in_items i
        INNER JOIN stock_ins s ON s.id = i.stock_in_id AND s.business_id = :business_id
        INNER JOIN products p  ON p.id = i.product_id
        INNER JOIN units un    ON un.id = i.unit
        WHERE i.stock_in_id = :stock_in_id
        ORDER BY i.id ASC
    ");
    $stmt->execute(['business_id' => $businessId, 'stock_in_id' => $stockInId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        return [];
    }

    // 2. Extract item IDs to fetch their serial numbers in a single efficient query
    $itemIds = array_column($items, 'id');
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

    $serialStmt = $this->db->prepare("
        SELECT stock_in_item_id, serial_no
        FROM inventory_serials
        WHERE stock_in_item_id IN ($placeholders)
    ");
    $serialStmt->execute($itemIds);
    $serialsRows = $serialStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Group serial numbers by stock_in_item_id
    $serialsByItem = [];
    foreach ($serialsRows as $row) {
        $serialsByItem[$row['stock_in_item_id']][] = $row['serial_no'];
    }

    // 4. Map UUIDs and attach serials so the form dropdowns select correctly on Edit
    foreach ($items as &$item) {
        $item['product'] = $item['product_uuid']; // Maps to items[i][product] in view
        $item['unit']    = $item['unit_uuid'];    // Maps to items[i][unit] in view
        $item['serials'] = $serialsByItem[$item['id']] ?? [];
    }
    unset($item);

    return $items;
}

public function receiptNoExistsExcept(int $businessId, string $receiptNo, int $excludeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM stock_ins
            WHERE business_id = :business_id AND receipt_no = :receipt_no AND id != :exclude_id LIMIT 1
        ");
        $stmt->execute([
            'business_id' => $businessId,
            'receipt_no'  => $receiptNo,
            'exclude_id'  => $excludeId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /** Update header, sync items, serials and movements in one transaction. */
    public function updateWithItems(array $h, array $items): bool
    {
        $this->db->beginTransaction();

        try {
            $stockInId = (int) $h['id'];

            // 1. Update Stock In header
            $stmt = $this->db->prepare("
                UPDATE stock_ins SET
                    receipt_no = :receipt_no,
                    expenditure_item_no = :expenditure_item_no,
                    incoming_register_no = :incoming_register_no,
                    classification = :classification,
                    store_no = :store_no,
                    shelf_no = :shelf_no,
                    department = :department,
                    supplier_name = :supplier_name,
                    received_date = :received_date,
                    item_condition = :item_condition,
                    source = :source,
                    total_amount = :total_amount
                WHERE id = :id AND business_id = :business_id
            ");
            $stmt->execute([
                'receipt_no'           => $h['receipt_no'],
                'expenditure_item_no'  => $h['expenditure_item_no'],
                'incoming_register_no' => $h['incoming_register_no'],
                'classification'       => $h['classification'],
                'store_no'             => $h['store_no'],
                'shelf_no'             => $h['shelf_no'],
                'department'           => $h['department'],
                'supplier_name'        => $h['supplier_name'],
                'received_date'        => $h['received_date'],
                'item_condition'       => $h['item_condition'],
                'source'               => $h['source'],
                'total_amount'         => $h['total_amount'],
                'id'                   => $stockInId,
                'business_id'          => $h['business_id'],
            ]);

            // 2. Clear old movements and serials associated with this stock-in to resync cleanly
            $this->db->prepare("DELETE FROM inventory_serials WHERE stock_in_item_id IN (SELECT id FROM stock_in_items WHERE stock_in_id = ?)")->execute([$stockInId]);
            $this->db->prepare("DELETE FROM stock_movements WHERE reference_type = 'stock_in' AND reference_id = ?")->execute([$stockInId]);
            $this->db->prepare("DELETE FROM stock_in_items WHERE stock_in_id = ?")->execute([$stockInId]);

            // 3. Re-insert items, serials, and movements
            $itemStmt = $this->db->prepare("
                INSERT INTO stock_in_items (
                    stock_in_id, product_id, model, page_from, page_to, unit, quantity, unit_price, total_price
                ) VALUES (
                    :stock_in_id, :product_id, :model, :page_from, :page_to, :unit, :quantity, :unit_price, :total_price
                )
            ");

            $serialStmt = $this->db->prepare("
                INSERT INTO inventory_serials (
                    uuid, business_id, product_id, unit_id, model, serial_no, status, stock_in_item_id
                ) VALUES (
                    :uuid, :business_id, :product_id, :unit_id, :model, :serial_no, 'IN_STOCK', :stock_in_item_id
                )
            ");

            foreach ($items as $item) {
                $itemStmt->execute([
                    'stock_in_id' => $stockInId,
                    'product_id'  => $item['product_id'],
                    'model'       => $item['model'],
                    'page_from'   => $item['page_from'],
                    'page_to'     => $item['page_to'],
                    'unit'        => $item['unit'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => number_format($item['unit_cents'] / 100, 2, '.', ''),
                    'total_price' => number_format($item['total_cents'] / 100, 2, '.', ''),
                ]);

                $itemId = (int) $this->db->lastInsertId();

                if (!empty($item['track_serials'])) {
                    foreach ($item['serials'] as $serial) {
                        $serialStmt->execute([
                            'uuid'             => Uuid::uuid7()->toString(),
                            'business_id'      => (int) $h['business_id'],
                            'product_id'       => (int) $item['product_id'],
                            'unit_id'          => (int) $item['unit'],
                            'model'            => (string) ($item['model'] ?? ''),
                            'serial_no'        => $serial,
                            'stock_in_item_id' => $itemId,
                        ]);
                    }
                }

                $this->movements->record([
                    'business_id'       => (int) $h['business_id'],
                    'product_id'        => (int) $item['product_id'],
                    'unit_id'           => (int) $item['unit'],
                    'model'             => (string) ($item['model'] ?? ''),
                    'movement_type'     => 'IN',
                    'reference_type'    => 'stock_in',
                    'reference_id'      => $stockInId,
                    'reference_uuid'    => $h['uuid'],
                    'reference_item_id' => $itemId,
                    'quantity'          => (int) $item['quantity'],
                    'remark'            => 'Receipt ' . $h['receipt_no'] . ' (Updated)',
                    'created_by'        => null,
                ]);
            }

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}