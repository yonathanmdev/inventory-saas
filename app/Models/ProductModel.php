<?php

namespace App\Models;

use PDO;

class ProductModel
{
    public function __construct(
        private PDO $db
    ) {
    }

    /**
     * Create product
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO products (
                uuid,
                business_id,
                category_id,
                name,
                description
            ) VALUES (
                :uuid,
                :business_id,
                :category_id,
                :name,
                :description
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'uuid'        => $data['uuid'],
            'business_id' => $data['business_id'],
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }


    /**
     * Find all products belonging to a business
     */
    public function findAllByBusinessId(int $businessId): array
    {
        $sql = "
            SELECT
                p.id,
                p.uuid,
                p.business_id,
                p.category_id,
                p.name,
                p.description,
                p.is_active,
                p.created_at,
                p.updated_at,

                c.name AS category_name

            FROM products p

            LEFT JOIN categories c
                ON c.id = p.category_id
                AND c.business_id = p.business_id

            WHERE p.business_id = :business_id
            AND p.is_active = 1

            ORDER BY p.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'business_id' => $businessId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

private const FROM_SQL = "
    FROM products p
    LEFT JOIN categories c
        ON c.id = p.category_id
        AND c.business_id = p.business_id
";

/**
 * Build WHERE clause + params shared by count, page and export.
 */
private function buildFilter(int $businessId, string $search, string $status = 'active'): array
{
    $where  = 'p.business_id = :business_id';
    $params = ['business_id' => $businessId];

    if ($status === 'active') {
        $where .= ' AND p.is_active = 1';
    } elseif ($status === 'inactive') {
        $where .= ' AND p.is_active = 0';
    }
    if ($search !== '') {
        // Distinct placeholder names: safe even with emulated prepares off
        $where .= '
            AND (
                p.name LIKE :s1
                OR p.description LIKE :s2
                OR c.name LIKE :s3
            )';

        // Escape LIKE wildcards typed by the user
        $like = '%' . addcslashes($search, '\\%_') . '%';

        $params['s1'] = $like;
        $params['s2'] = $like;
        $params['s3'] = $like;
    }

    return [$where, $params];
}

public function countByBusinessId(int $businessId, string $search = '', string $status = 'active'): int
{
    [$where, $params] = $this->buildFilter($businessId, $search, $status);

    $stmt = $this->db->prepare(
        "SELECT COUNT(*) " . self::FROM_SQL . " WHERE {$where}"
    );
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

public function findPageByBusinessId(
    int $businessId,
    string $search,
    int $limit,
    int $offset,
    string $status = 'active'
): array {
    [$where, $params] = $this->buildFilter($businessId, $search, $status);
    $sql = "
        SELECT
            p.id, p.uuid, p.business_id, p.category_id,
            p.name, p.description, p.is_active,
            p.created_at, p.updated_at,
            c.name AS category_name
        " . self::FROM_SQL . "
        WHERE {$where}
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $this->db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            $key,
            $value,
            is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
        );
    }

    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * All matching rows, no pagination (for export).
 */
public function findAllForExport(int $businessId, string $search = '', string $status = 'active'): array
{
    [$where, $params] = $this->buildFilter($businessId, $search, $status);

    $sql = "
        SELECT
            p.id, p.name, p.description, p.is_active,
            p.created_at, p.updated_at,
            c.name AS category_name
        " . self::FROM_SQL . "
        WHERE {$where}
        ORDER BY p.created_at DESC, p.id DESC
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /**
     * Find product by UUID within a business
     */
    public function findByUuid(
        string $uuid,
        int $businessId
    ): ?array {
        $sql = "
            SELECT
                p.id,
                p.uuid,
                p.business_id,
                p.category_id,
                p.name,
                p.description,
                p.is_active,
                p.created_at,
                p.updated_at,

                c.name AS category_name

            FROM products p

            LEFT JOIN categories c
                ON c.id = p.category_id
                AND c.business_id = p.business_id

            WHERE p.uuid = :uuid
              AND p.business_id = :business_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'uuid'        => $uuid,
            'business_id' => $businessId
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        return $product ?: null;
    }


  


    /**
     * Check whether a category belongs to the business
     */
    public function categoryBelongsToBusiness(
        int $categoryId,
        int $businessId
    ): bool {
        $sql = "
            SELECT id
            FROM categories
            WHERE id = :category_id
              AND business_id = :business_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'category_id' => $categoryId,
            'business_id' => $businessId
        ]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Update product
     */
    public function update(
        string $uuid,
        int $businessId,
        array $data
    ): bool {
        $sql = "
            UPDATE products
            SET
                category_id = :category_id,
                name = :name,
                description = :description
            WHERE uuid = :uuid
              AND business_id = :business_id
              AND is_active = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'uuid'        => $uuid,
            'business_id' => $businessId
        ]);
    }


    /**
     * Activate product
     */
    public function activate(
        string $uuid,
        int $businessId
    ): bool {
        $sql = "
            UPDATE products
            SET is_active = 1
            WHERE uuid = :uuid
              AND business_id = :business_id
              AND is_active = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'uuid'        => $uuid,
            'business_id' => $businessId
        ]);
    }


    /**
     * Deactivate product
     */
    public function deactivate(
        string $uuid,
        int $businessId
    ): bool {
        $sql = "
            UPDATE products
            SET is_active = 0
            WHERE uuid = :uuid
              AND business_id = :business_id
              AND is_active = 1
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'uuid'        => $uuid,
            'business_id' => $businessId
        ]);
    }
}