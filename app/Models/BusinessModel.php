<?php

namespace App\Models;

use PDO;

class BusinessModel
{
    public function __construct(
        private PDO $db
    ) {
    }
public function create(array $data): int
{
    $sql = "
        INSERT INTO businesses (
            uuid,
            name,
            email,
            phone,
            address,
            logo_path,
            description,
            is_active
        )
        VALUES (
            :uuid,
            :name,
            :email,
            :phone,
            :address,
            :logo_path,
            :description,
            :is_active
        )
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        'uuid'        => $data['uuid'],
        'name'        => $data['name'],
        'email'       => $data['email'],
        'phone'       => $data['phone'],
        'address'     => $data['address'],
        'logo_path'   => $data['logo_path'] ?? null,
        'description' => $data['description'],
        'is_active'   => $data['is_active'],
    ]);

    return (int) $this->db->lastInsertId();
}


public function update(int $id, array $data): bool
{
    $sql = "
        UPDATE businesses
        SET
            name = :name,
            email = :email,
            phone = :phone,
            address = :address,
            logo_path = :logo_path,
            description = :description
        WHERE id = :id
    ";

    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        'id'          => $id,
        'name'        => $data['name'],
        'email'       => $data['email'],
        'phone'       => $data['phone'],
        'address'     => $data['address'],
        'logo_path'   => $data['logo_path'] ?? null,
        'description' => $data['description'],
    ]);
}



    public function findByUUID(string $businessUUID): ?array
    {
        $sql = "
            SELECT *
            FROM businesses
            WHERE uuid = :uuid
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'uuid' => $businessUUID
        ]);

        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        return $business ?: null;
    }


    public function findAll(): array
    {
        $sql = "
            SELECT *
            FROM businesses
            ORDER BY id DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }



    public function findByEmail(string $email): ?array
    {
        $sql = "
            SELECT *
            FROM businesses
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'email' => $email
        ]);

        $business = $stmt->fetch(PDO::FETCH_ASSOC);

        return $business ?: null;
    }

}