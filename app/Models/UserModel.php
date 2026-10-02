<?php

namespace App\Models;

use PDO;

class UserModel
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function findByEmail(string $email, ?int $businessId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? AND business_id <=> ? LIMIT 1"
        );
        $stmt->execute([$email, $businessId]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
{
    $sql = "INSERT INTO users (
                uuid,
                business_id,
                role_id,
                is_system_admin,
                full_name,
                email,
                username,
                password_hash,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        $data['uuid'],
        $data['business_id'],
        $data['role_id'],
        $data['is_system_admin'],
        $data['full_name'],
        $data['email'],
        $data['username'],
        $data['password_hash'],
    ]);

    return (int) $this->db->lastInsertId();
}

    public function findAllWithDetails(): array
    {
        $sql = "SELECT
                    u.*,
                    b.name AS business_name,
                    r.name AS role_name
                FROM users u
                LEFT JOIN businesses b ON b.id = u.business_id
                LEFT JOIN roles r ON r.id = u.role_id
                ORDER BY u.created_at DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function findForLogin(string $login): ?array
    {
        $sql = "
            SELECT
                u.id,
                u.uuid,
                u.business_id,
                u.role_id,
                u.is_system_admin,
                u.full_name,
                u.email,
                u.username,
                u.password_hash,
                u.is_active,
                u.last_login_at,
                r.name AS role_name
            FROM users u
            LEFT JOIN roles r
                ON r.id = u.role_id
            WHERE u.username = :login_username
               OR u.email = :login_email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'login_username' => $login,
            'login_email' => $login
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function updateLastLogin(int $userId): bool
    {
        $sql = "
            UPDATE users
            SET last_login_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $userId
        ]);
    }
    public function findAllByBusinessId(int $businessId): array
{
    $sql = "
        SELECT
            u.*,
            b.name AS business_name,
            r.name AS role_name
        FROM users u
        LEFT JOIN businesses b
            ON b.id = u.business_id
        LEFT JOIN roles r
            ON r.id = u.role_id
        WHERE u.business_id = :business_id
        ORDER BY u.created_at DESC
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        'business_id' => $businessId
    ]);

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
public function findByUuidWithDetails(string $uuid): ?array
{
    $sql = "
        SELECT
            u.id,
            u.uuid,
            u.business_id,
            u.role_id,
            u.full_name,
            u.email,
            u.username,
            u.is_active,
            u.is_system_admin,
            u.last_login_at,
            u.created_at,
            u.updated_at,

            b.name AS business_name,
            b.email AS business_email,
            b.phone AS business_phone,

            r.name AS role_name

        FROM users u

        LEFT JOIN businesses b
            ON b.id = u.business_id

        LEFT JOIN roles r
            ON r.id = u.role_id

        WHERE u.uuid = :uuid

        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        'uuid' => $uuid
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}
public function findByUuidWithDetailsForBusiness(
    string $uuid,
    int $businessId
): ?array {
    $sql = "
        SELECT
            u.id,
            u.uuid,
            u.business_id,
            u.role_id,
            u.full_name,
            u.email,
            u.username,
            u.is_active,
            u.is_system_admin,
            u.last_login_at,
            u.created_at,
            u.updated_at,

            b.name AS business_name,
            b.email AS business_email,
            b.phone AS business_phone,

            r.name AS role_name

        FROM users u

        LEFT JOIN businesses b
            ON b.id = u.business_id

        LEFT JOIN roles r
            ON r.id = u.role_id

        WHERE u.uuid = :uuid
          AND u.business_id = :business_id

        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        'uuid' => $uuid,
        'business_id' => $businessId
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}
public function deactivateByUuid(string $uuid): bool
{
    $sql = "
        UPDATE users
        SET is_active = 0
        WHERE uuid = :uuid
          AND is_active = 1
        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        'uuid' => $uuid
    ]);
}
public function activateByUuid(string $uuid): bool
{
    $sql = "
        UPDATE users
        SET is_active = 1
        WHERE uuid = :uuid
          AND is_active = 0
        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        'uuid' => $uuid
    ]);
}
}