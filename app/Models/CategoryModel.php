<?php

namespace App\Models;

use PDO;

class CategoryModel
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Get all categories for a specific business.
     */
    public function findAllByBusinessId(int $businessId): array
    {
        $sql = "
            SELECT
                c.*,
                b.name AS business_name
            FROM categories c
            INNER JOIN businesses b
                ON b.id = c.business_id
            WHERE c.business_id = :business_id
            ORDER BY c.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'business_id' => $businessId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Get all categories for System Admin.
     */
    public function findAllWithBusiness(): array
    {
        $sql = "
            SELECT
                c.*,
                b.name AS business_name
            FROM categories c
            INNER JOIN businesses b
                ON b.id = c.business_id
            ORDER BY c.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Find one category by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                c.*,
                b.name AS business_name
            FROM categories c
            INNER JOIN businesses b
                ON b.id = c.business_id
            WHERE c.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }
 /**
     * Find one category by ID.
     */
   public function findByUuidAndBusinessId(string $uuid, int $businessId): ?array
{
    $stmt = $this->db->prepare("
        SELECT c.id, c.uuid, c.business_id, c.name
        FROM categories c
        WHERE c.uuid = :uuid
          AND c.business_id = :business_id
        LIMIT 1
    ");

    $stmt->execute([
        'uuid'        => $uuid,
        'business_id' => $businessId,
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


    /**
     * Find a category belonging to a specific business.
     */
    public function findByIdAndBusiness(
        int $id,
        int $businessId
    ): ?array {

        $sql = "
            SELECT
                c.*,
                b.name AS business_name
            FROM categories c
            INNER JOIN businesses b
                ON b.id = c.business_id
            WHERE c.id = :id
              AND c.business_id = :business_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id,
            'business_id' => $businessId
        ]);

        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }


    /**
     * Check whether a category name already exists
     * for a business.
     */
    public function findByName(
        string $name,
        int $businessId,
        ?int $excludeId = null
    ): ?array {

        $sql = "
            SELECT *
            FROM categories
            WHERE business_id = :business_id
              AND LOWER(name) = LOWER(:name)
        ";

        $params = [
            'business_id' => $businessId,
            'name' => $name
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";

            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }


    /**
     * Create a category.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO categories (
                uuid,
                business_id,
                name,
                description,
                is_active
            )
            VALUES (
                :uuid,
                :business_id,
                :name,
                :description,
                :is_active
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'uuid' => $data['uuid'],
            'business_id' => $data['business_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active']
        ]);

        return (int) $this->db->lastInsertId();
    }


    /**
     * Update a category.
     */
    public function update(
        int $id,
        array $data
    ): bool {

        $sql = "
            UPDATE categories
            SET
                name = :name,
                description = :description,
                is_active = :is_active
            WHERE id = :id
              AND business_id = :business_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'business_id' => $data['business_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active']
        ]);
    }


    /**
     * Delete a category.
     */
    public function delete(
        int $id,
        int $businessId
    ): bool {

        $sql = "
            DELETE FROM categories
            WHERE id = :id
              AND business_id = :business_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'business_id' => $businessId
        ]);
    }
}