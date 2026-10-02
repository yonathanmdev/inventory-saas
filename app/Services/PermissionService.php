<?php

namespace App\Services;

use App\Models\PermissionModel;

class PermissionService
{
    public function __construct(
        private PermissionModel $permissionModel,
        private AuditLogger $auditLogger
    ) {
    }

    public function findAll(): array
    {
        return $this->permissionModel->findAll();
    }

    public function create(array $data): array
    {
        $keyName = trim($data['key_name'] ?? '');
        $description = trim($data['description'] ?? '');

        if ($keyName === '') {
            return [
                'success' => false,
                'message' => 'Permission key is required.'
            ];
        }

        if (!preg_match(
            '/^[a-z0-9]+(\.[a-z0-9_]+)+$/',
            $keyName
        )) {
            return [
                'success' => false,
                'message' => 'Invalid permission key format.'
            ];
        }

        if ($this->permissionModel->existsByKeyName($keyName)) {
            return [
                'success' => false,
                'message' => 'This permission already exists.'
            ];
        }

        /*
         * Create permission.
         */
        $permissionId = $this->permissionModel->create(
            $keyName,
            $description !== '' ? $description : null
        );

        /*
         * Audit permission creation.
         *
         * Permissions are global system configuration,
         * therefore business_id is explicitly NULL.
         */
        $this->auditLogger->success([
            'action' => 'CREATE',
            'module' => 'Permissions',
            'table_name' => 'permissions',
            'record_id' => $permissionId,
            'business_id' => null,
            'description' => 'Permission created successfully',
            'new_values' => [
                'key_name' => $keyName,
                'description' =>
                    $description !== ''
                        ? $description
                        : null,
            ],
        ]);

        return [
            'success' => true,
            'message' => 'Permission created successfully.'
        ];
    }
}