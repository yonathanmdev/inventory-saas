<?php

namespace App\Models;

use PDO;

class RolePermissionModel
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function getPermissionIdsByRoleId(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT permission_id
            FROM role_permissions
            WHERE role_id = :role_id
            ORDER BY permission_id ASC
        ");

        $stmt->execute([
            'role_id' => $roleId
        ]);

        return array_map(
            'intval',
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function replacePermissions(
        int $roleId,
        array $permissionIds
    ): void {

        $this->db->beginTransaction();

        try {

            /*
             * Remove existing permissions.
             */
            $delete = $this->db->prepare("
                DELETE FROM role_permissions
                WHERE role_id = :role_id
            ");

            $delete->execute([
                'role_id' => $roleId
            ]);


            /*
             * Insert selected permissions.
             */
            if (!empty($permissionIds)) {

                $insert = $this->db->prepare("
                    INSERT INTO role_permissions (
                        role_id,
                        permission_id
                    )
                    VALUES (
                        :role_id,
                        :permission_id
                    )
                ");

                foreach ($permissionIds as $permissionId) {

                    $insert->execute([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId
                    ]);
                }
            }

            $this->db->commit();

        } catch (\Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}