<?php

namespace App\Models;

use PDO;

class PermissionModel
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findAll(): array
    {
        $sql = "
            SELECT
                id,
                key_name,
                description
            FROM permissions
            ORDER BY key_name ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                key_name,
                description
            FROM permissions
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $id
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function existsByKeyName(string $keyName): bool
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM permissions
            WHERE key_name = :key_name
            LIMIT 1
        ");

        $stmt->execute([
            'key_name' => $keyName
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function findKeysByRoleId(int $roleId): array
    {
        $sql = "
            SELECT p.key_name
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'role_id' => $roleId
        ]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function create(
        string $keyName,
        ?string $description
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO permissions (
                key_name,
                description
            )
            VALUES (
                :key_name,
                :description
            )
        ");

        $stmt->execute([
            'key_name' => $keyName,
            'description' => $description
        ]);

        return (int) $this->db->lastInsertId();
    }
}