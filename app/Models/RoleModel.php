<?php

namespace App\Models;

use PDO;

class RoleModel
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Get all roles with their business.
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                r.id,
                r.business_id,
                r.name,
                r.is_system_role,
                b.name AS business_name
            FROM roles r
            LEFT JOIN businesses b
                ON b.id = r.business_id
            ORDER BY r.id DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find one role.
     */
    public function findById(int $roleId): ?array
    {
        $sql = "
            SELECT
                r.id,
                r.business_id,
                r.name,
                r.is_system_role,
                b.name AS business_name
            FROM roles r
            LEFT JOIN businesses b
                ON b.id = r.business_id
            WHERE r.id = :role_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'role_id' => $roleId
        ]);

        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        return $role ?: null;
    }

    /**
     * Get roles belonging to one business.
     *
     * This will also be useful when registering users.
     */
    public function findByBusinessId(int $businessId): array
    {
        $sql = "
            SELECT
                id,
                business_id,
                name,
                is_system_role
            FROM roles
            WHERE business_id = :business_id
            ORDER BY name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'business_id' => $businessId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check whether a role already exists
     * for the selected business.
     */
    public function existsByBusinessAndName(
        int $businessId,
        string $name
    ): bool {

        $sql = "
            SELECT id
            FROM roles
            WHERE business_id = :business_id
              AND name = :name
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'business_id' => $businessId,
            'name'        => $name
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Create a business role.
     */
    public function create(
        int $businessId,
        string $name
    ): int {

        $sql = "
            INSERT INTO roles (
                business_id,
                name,
                is_system_role
            )
            VALUES (
                :business_id,
                :name,
                0
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'business_id' => $businessId,
            'name'        => $name
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a role name.
     */
    public function update(
        int $roleId,
        string $name
    ): bool {

        $sql = "
            UPDATE roles
            SET name = :name
            WHERE id = :role_id
              AND is_system_role = 0
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'role_id' => $roleId,
            'name'    => $name
        ]);
    }

    /**
     * Delete a business role.
     */
    public function delete(int $roleId): bool
    {
        $sql = "
            DELETE FROM roles
            WHERE id = :role_id
              AND is_system_role = 0
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'role_id' => $roleId
        ]);
    }
}