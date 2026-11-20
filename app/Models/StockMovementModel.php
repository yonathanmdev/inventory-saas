<?php

namespace App\Models;

use PDO;
use Ramsey\Uuid\Uuid;

class StockMovementModel
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Append one immutable row to the stock ledger.
     *
     * Must be called inside the transaction of the operation
     * that causes the movement.
     */
    public function record(array $m): void
    {
        $movementType = strtoupper((string) ($m['movement_type'] ?? ''));

        if (!in_array($movementType, ['IN', 'OUT'], true)) {
            throw new \InvalidArgumentException(
                'Invalid stock movement type.'
            );
        }

        if ((int) ($m['quantity'] ?? 0) <= 0) {
            throw new \InvalidArgumentException(
                'Stock movement quantity must be greater than zero.'
            );
        }

        $stmt = $this->db->prepare("
            INSERT INTO stock_movements (
                uuid,
                business_id,
                product_id,
                unit_id,
                model,
                item_condition,
                movement_type,
                quantity,
                reference_type,
                reference_id,
                reference_uuid,
                reference_item_id,
                remark,
                created_by
            ) VALUES (
                :uuid,
                :business_id,
                :product_id,
                :unit_id,
                :model,
                :item_condition,
                :movement_type,
                :quantity,
                :reference_type,
                :reference_id,
                :reference_uuid,
                :reference_item_id,
                :remark,
                :created_by
            )
        ");

        $stmt->execute([
            'uuid'           => Uuid::uuid7()->toString(),
            'business_id'    => (int) $m['business_id'],
            'product_id'     => (int) $m['product_id'],
            'unit_id'        => (int) $m['unit_id'],
            'model'          => (string) ($m['model'] ?? ''),
            'item_condition' => (string) ($m['item_condition'] ?? ''),
            'movement_type'  => $movementType,
            'quantity'       => (int) $m['quantity'],
            'reference_type' => $m['reference_type'] ?? null,
            'reference_id'   => isset($m['reference_id'])
                ? (int) $m['reference_id']
                : null,
            'reference_uuid' => $m['reference_uuid'] ?? null,
            'reference_item_id' => isset($m['reference_item_id'])
                ? (int) $m['reference_item_id']
                : null,
            'remark'         => isset($m['remark'])
                ? mb_substr((string) $m['remark'], 0, 500)
                : null,
            'created_by'     => isset($m['created_by'])
                ? (int) $m['created_by']
                : null,
        ]);
    }
}