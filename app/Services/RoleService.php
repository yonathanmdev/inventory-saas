<?php

namespace App\Services;

use App\Models\RoleModel;

class RoleService
{
    public function __construct(
        private RoleModel $roleModel,
        private AuditLogger $auditLogger
    ) {
    }

    /**
     * Get all roles.
     */
    public function findAll(): array
    {
        return $this->roleModel->findAll();
    }

    /**
     * Get one role.
     */
    public function findById(int $roleId): ?array
    {
        return $this->roleModel->findById($roleId);
    }

    /**
     * Get roles for a business.
     */
    public function findByBusinessId(int $businessId): array
    {
        return $this->roleModel->findByBusinessId($businessId);
    }

    /**
     * Register a business role.
     */
    public function create(array $data): array
    {
        $businessId = (int) ($data['business_id'] ?? 0);
        $name = trim($data['name'] ?? '');

        if ($businessId <= 0) {
            return [
                'success' => false,
                'message' => 'Please select a business.'
            ];
        }

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Role name is required.'
            ];
        }

        if (mb_strlen($name) > 50) {
            return [
                'success' => false,
                'message' => 'Role name cannot exceed 50 characters.'
            ];
        }

        if (
            $this->roleModel->existsByBusinessAndName(
                $businessId,
                $name
            )
        ) {
            return [
                'success' => false,
                'message' => 'This role already exists for the selected business.'
            ];
        }

        $roleId = $this->roleModel->create(
            $businessId,
            $name
        );

        /*
         * Audit role creation.
         *
         * business_id is explicitly supplied because the role
         * belongs to the selected business.
         */
        $this->auditLogger->success([
            'action' => 'CREATE',
            'module' => 'Roles',
            'table_name' => 'roles',
            'record_id' => $roleId,
            'business_id' => $businessId,
            'description' => 'Role registered successfully',
            'new_values' => [
                'business_id' => $businessId,
                'name' => $name,
                'is_system_role' => 0,
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Role registered successfully.',
            'role_id' => $roleId
        ];
    }

    /**
     * Update a role.
     */
    public function update(
        int $roleId,
        array $data
    ): array {

        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Role name is required.'
            ];
        }

        if (mb_strlen($name) > 50) {
            return [
                'success' => false,
                'message' => 'Role name cannot exceed 50 characters.'
            ];
        }

        /*
         * Retrieve the existing role before making changes.
         * This is also used for tenant ownership.
         */
        $role = $this->roleModel->findById($roleId);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'Role not found.'
            ];
        }

        if ((int) $role['is_system_role'] === 1) {
            return [
                'success' => false,
                'message' => 'System roles cannot be modified here.'
            ];
        }

        $businessId = isset($role['business_id'])
            ? (int) $role['business_id']
            : null;

        /*
         * Capture the old state before updating.
         */
        $oldValues = [
            'business_id' => $businessId,
            'name' => $role['name'] ?? null,
            'is_system_role' => isset($role['is_system_role'])
                ? (int) $role['is_system_role']
                : null,
        ];

        $updated = $this->roleModel->update(
            $roleId,
            $name
        );

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Unable to update role.'
            ];
        }

        /*
         * Audit only after the database update succeeds.
         */
        $this->auditLogger->success([
            'action' => 'UPDATE',
            'module' => 'Roles',
            'table_name' => 'roles',
            'record_id' => $roleId,
            'business_id' => $businessId,
            'description' => 'Role updated successfully',
            'old_values' => $oldValues,
            'new_values' => [
                'business_id' => $businessId,
                'name' => $name,
                'is_system_role' => isset($role['is_system_role'])
                    ? (int) $role['is_system_role']
                    : 0,
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Role updated successfully.'
        ];
    }

    /**
     * Delete a role.
     */
    public function delete(int $roleId): array
    {
        /*
         * Retrieve the role before deletion so its previous
         * state can be preserved in the audit log.
         */
        $role = $this->roleModel->findById($roleId);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'Role not found.'
            ];
        }

        if ((int) $role['is_system_role'] === 1) {
            return [
                'success' => false,
                'message' => 'System roles cannot be deleted.'
            ];
        }

        $businessId = isset($role['business_id'])
            ? (int) $role['business_id']
            : null;

        /*
         * Capture the complete safe role state before deletion.
         */
        $oldValues = [
            'business_id' => $businessId,
            'name' => $role['name'] ?? null,
            'is_system_role' => isset($role['is_system_role'])
                ? (int) $role['is_system_role']
                : null,
        ];

        $deleted = $this->roleModel->delete($roleId);

        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Unable to delete role.'
            ];
        }

        /*
         * Audit only after successful deletion.
         */
        $this->auditLogger->success([
            'action' => 'DELETE',
            'module' => 'Roles',
            'table_name' => 'roles',
            'record_id' => $roleId,
            'business_id' => $businessId,
            'description' => 'Role deleted successfully',
            'old_values' => $oldValues,
        ]);

        return [
            'success' => true,
            'message' => 'Role deleted successfully.'
        ];
    }
}