<?php

namespace App\Services;

use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;

class RolePermissionService
{
    public function __construct(
        private RolePermissionModel $rolePermissionModel,
        private PermissionModel $permissionModel,
        private RoleModel $roleModel,
        private AuditLogger $auditLogger
    ) {
    }

    public function getAssignmentData(int $roleId): array
    {
        $role = $this->roleModel->findById($roleId);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'Role not found.'
            ];
        }

        $permissions = $this->permissionModel->findAll();

        $assignedPermissionIds =
            $this->rolePermissionModel
                ->getPermissionIdsByRoleId($roleId);

        return [
            'success' => true,
            'role' => $role,
            'permissions' => $permissions,
            'assignedPermissionIds' => $assignedPermissionIds
        ];
    }

    public function assign(
        int $roleId,
        array $permissionIds
    ): array {

        $role = $this->roleModel->findById($roleId);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'Role not found.'
            ];
        }

        $permissionIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $permissionIds)
                )
            )
        );

        /*
         * Verify that every submitted permission exists.
         */
        foreach ($permissionIds as $permissionId) {

            if (!$this->permissionModel->findById($permissionId)) {
                return [
                    'success' => false,
                    'message' => 'One or more selected permissions are invalid.'
                ];
            }
        }

        /*
         * Capture the permissions currently assigned
         * before replacing them.
         */
        $oldPermissionIds =
            $this->rolePermissionModel
                ->getPermissionIdsByRoleId($roleId);

        /*
         * Determine the business associated with the role.
         *
         * Tenant roles should carry their business ID.
         * System/global roles remain NULL.
         */
        $businessId = null;

        if (
            isset($role['business_id']) &&
            $role['business_id'] !== null &&
            $role['business_id'] !== ''
        ) {
            $businessId = (int) $role['business_id'];
        }

        /*
         * Replace role permissions.
         */
        $this->rolePermissionModel->replacePermissions(
            $roleId,
            $permissionIds
        );

        /*
         * Audit the complete authorization change.
         *
         * We record both states so the audit log can answer:
         *
         *   What permissions did this role have before?
         *   What permissions does it have now?
         */
        $this->auditLogger->success([
            'action' => 'UPDATE',
            'module' => 'Role Permissions',
            'table_name' => 'role_permissions',
            'record_id' => $roleId,
            'business_id' => $businessId,
            'description' =>
                'Role permissions updated successfully',
            'old_values' => [
                'role_id' => $roleId,
                'permission_ids' => $oldPermissionIds,
            ],
            'new_values' => [
                'role_id' => $roleId,
                'permission_ids' => $permissionIds,
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Permissions assigned successfully.'
        ];
    }
}