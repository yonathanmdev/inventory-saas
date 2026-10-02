<?php

namespace App\Models;

use PDO;
use RuntimeException;

class AuditLogModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new audit log record.
     */
   public function create(array $data): int
{
    $sql = "
        INSERT INTO audit_logs (
            uuid,
            user_id,
            role_name_at_time,
            business_id,
            action,
            module,
            table_name,
            record_id,
            record_uuid,
            description,
            old_values,
            new_values,
            ip_address,
            user_agent,
            status
        ) VALUES (
            :uuid,
            :user_id,
            :role_name_at_time,
            :business_id,
            :action,
            :module,
            :table_name,
            :record_id,
            :record_uuid,
            :description,
            :old_values,
            :new_values,
            :ip_address,
            :user_agent,
            :status
        )
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        ':uuid'        => $data['uuid'],
        ':user_id'     => $data['user_id'],
        ':role_name_at_time' => $data['role_name_at_time'],
        ':business_id' => $data['business_id'],
        ':action'      => $data['action'],
        ':module'      => $data['module'],
        ':table_name'  => $data['table_name'],
        ':record_id'   => $data['record_id'],
        ':record_uuid' => $data['record_uuid'],
        ':description' => $data['description'],
        ':old_values'  => $data['old_values'],
        ':new_values'  => $data['new_values'],
        ':ip_address'  => $data['ip_address'],
        ':user_agent'  => $data['user_agent'],
        ':status'      => $data['status'],
    ]);

    return (int) $this->db->lastInsertId();
}

    /**
     * Find an audit log by ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                al.*,
                u.full_name AS user_name,
                u.username,
                b.name AS business_name
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
            LEFT JOIN businesses b
                ON b.id = al.business_id
            WHERE al.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get audit logs.
     *
     * The business_id filter is intentionally explicit.
     * System Admin can omit it to access all businesses.
     */
    public function getAll(
        ?int $businessId = null,
        array $filters = [],
        int $limit = 50,
        int $offset = 0
    ): array {
        $conditions = [];
        $params = [];

        /*
         * Tenant isolation.
         *
         * When a business_id is supplied, only that business'
         * audit records are returned.
         */
        if ($businessId !== null) {
            $conditions[] = 'al.business_id = :business_id';
            $params[':business_id'] = $businessId;
        }

        /*
         * User filter.
         */
        if (!empty($filters['user_id'])) {
            $conditions[] = 'al.user_id = :filter_user_id';
            $params[':filter_user_id'] = (int) $filters['user_id'];
        }

        /*
         * Business filter.
         *
         * This is mainly useful for System Admin.
         */
        if (!empty($filters['filter_business_id'])) {
            $conditions[] = 'al.business_id = :filter_business_id';
            $params[':filter_business_id'] = (int) $filters['filter_business_id'];
        }

        /*
         * Action filter.
         */
        if (!empty($filters['action'])) {
            $conditions[] = 'al.action = :action';
            $params[':action'] = $filters['action'];
        }

        /*
         * Module filter.
         */
        if (!empty($filters['module'])) {
            $conditions[] = 'al.module = :module';
            $params[':module'] = $filters['module'];
        }

        /*
         * Status filter.
         */
        if (!empty($filters['status'])) {
            $conditions[] = 'al.status = :status';
            $params[':status'] = $filters['status'];
        }

        /*
         * Date range.
         */
        if (!empty($filters['date_from'])) {
            $conditions[] = 'al.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'al.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = '';

        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $sql = "
            SELECT
                al.*,
                u.full_name AS user_name,
                u.username,
                b.name AS business_name
            FROM audit_logs al
            LEFT JOIN users u
                ON u.id = al.user_id
            LEFT JOIN businesses b
                ON b.id = al.business_id
            {$where}
            ORDER BY al.created_at DESC, al.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count audit logs.
     */
    public function count(
        ?int $businessId = null,
        array $filters = []
    ): int {
        $conditions = [];
        $params = [];

        if ($businessId !== null) {
            $conditions[] = 'al.business_id = :business_id';
            $params[':business_id'] = $businessId;
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = 'al.user_id = :filter_user_id';
            $params[':filter_user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['filter_business_id'])) {
            $conditions[] = 'al.business_id = :filter_business_id';
            $params[':filter_business_id'] = (int) $filters['filter_business_id'];
        }

        if (!empty($filters['action'])) {
            $conditions[] = 'al.action = :action';
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['module'])) {
            $conditions[] = 'al.module = :module';
            $params[':module'] = $filters['module'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'al.status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'al.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'al.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $where = '';

        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $sql = "
            SELECT COUNT(*)
            FROM audit_logs al
            {$where}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get available actions.
     */
    public function getActions(?int $businessId = null): array
    {
        $sql = "
            SELECT DISTINCT action
            FROM audit_logs
        ";

        $params = [];

        if ($businessId !== null) {
            $sql .= " WHERE business_id = :business_id";
            $params[':business_id'] = $businessId;
        }

        $sql .= " ORDER BY action ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get available modules.
     */
    public function getModules(?int $businessId = null): array
    {
        $sql = "
            SELECT DISTINCT module
            FROM audit_logs
            WHERE module IS NOT NULL
        ";

        $params = [];

        if ($businessId !== null) {
            $sql .= " AND business_id = :business_id";
            $params[':business_id'] = $businessId;
        }

        $sql .= " ORDER BY module ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}