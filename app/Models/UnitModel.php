<?php

namespace App\Models;

use PDO;

class UnitModel
{
    public function __construct(private PDO $db)
    {
    }

    public function findActiveOptions(int $businessId): array
    {
        $stmt = $this->db->prepare("
            SELECT uuid, name FROM units
            WHERE business_id = :business_id AND is_active = 1
            ORDER BY name ASC
        ");
        $stmt->execute(['business_id' => $businessId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string,int|string> uuid => id (active units of this business only) */
    public function findIdsByUuids(array $uuids, int $businessId): array
    {
        if (!$uuids) {
            return [];
        }

        $in   = implode(',', array_fill(0, count($uuids), '?'));
        $stmt = $this->db->prepare("
            SELECT uuid, id FROM units
            WHERE business_id = ? AND is_active = 1 AND uuid IN ($in)
        ");
        $stmt->execute([$businessId, ...array_values($uuids)]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}