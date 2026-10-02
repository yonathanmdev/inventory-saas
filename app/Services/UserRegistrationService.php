<?php

namespace App\Services;

use App\Models\UserModel;
use Ramsey\Uuid\Uuid;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
class UserRegistrationService
{
    public function __construct(
        private UserModel $userModel,
        private RoleService $roleService,
        private AuditLogger $auditLogger,
        private FileUploadService $fileUploadService
    ) {
    }

    public function register(array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $username = trim($data['username'] ?? '');

        $password = $data['password'] ?? '';
        $confirm = $data['password_confirm'] ?? '';

        $businessId = isset($data['business_id'])
            && $data['business_id'] !== ''
            ? (int) $data['business_id']
            : null;

        $roleId = isset($data['role_id'])
            && $data['role_id'] !== ''
            ? (int) $data['role_id']
            : null;

        /*
         * Business users are not system administrators.
         */
        $isSystemAdmin = 0;

        if ($fullName === '') {
            return [
                'success' => false,
                'message' => 'Full name is required.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'A valid email is required.'
            ];
        }

        if ($username === '') {
            return [
                'success' => false,
                'message' => 'Username is required.'
            ];
        }

        if (!$businessId) {
            return [
                'success' => false,
                'message' => 'Business is required.'
            ];
        }

        if (!$roleId) {
            return [
                'success' => false,
                'message' => 'Role is required.'
            ];
        }

        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters.'
            ];
        }

        if ($password !== $confirm) {
            return [
                'success' => false,
                'message' => 'Passwords do not match.'
            ];
        }

        /*
         * Make sure the selected role exists.
         */
        $role = $this->roleService->findById($roleId);

        if (!$role) {
            return [
                'success' => false,
                'message' => 'Selected role was not found.'
            ];
        }

        /*
         * Critical tenant validation:
         * the role must belong to the selected business.
         */
        if ((int) $role['business_id'] !== $businessId) {
            return [
                'success' => false,
                'message' =>
                    'The selected role does not belong to the selected business.'
            ];
        }

        /*
         * Business-scoped email uniqueness.
         */
        if ($this->userModel->findByEmail($email, $businessId)) {
            return [
                'success' => false,
                'message' =>
                    'This email is already registered for this business.'
            ];
        }

        /*
         * Username remains globally unique.
         */
        if ($this->userModel->findByUsername($username)) {
            return [
                'success' => false,
                'message' =>
                    'This username is already taken.'
            ];
        }

        /*
         * Generate UUID once so the same UUID is used
         * by both the database record and the audit record.
         */
        $userUuid = Uuid::uuid7()->toString();

        $userId = $this->userModel->create([
            'uuid' => $userUuid,
            'business_id' => $businessId,
            'role_id' => $roleId,
            'is_system_admin' => $isSystemAdmin,
            'full_name' => $fullName,
            'email' => $email,
            'username' => $username,
            'password_hash' =>
                password_hash($password, PASSWORD_DEFAULT),
        ]);

        /*
         * Audit user creation.
         *
         * IMPORTANT:
         * Never include:
         * - password
         * - password_confirm
         * - password_hash
         */
        $this->auditLogger->success([
            'role_name_at_time' => $_SESSION['role_name'] ?? null,
            'action' => 'CREATE',
            'module' => 'Users',
            'table_name' => 'users',
            'record_id' => $userId,
            'record_uuid' => $userUuid,
            'business_id' => $businessId,
            'description' => 'User registered successfully',
            'new_values' => [
                'uuid' => $userUuid,
                'business_id' => $businessId,
                'role_id' => $roleId,
                'is_system_admin' => $isSystemAdmin,
                'full_name' => $fullName,
                'email' => $email,
                'username' => $username,
            ],
        ]);

        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'User registered successfully.'
        ];
    }

    public function findAllWithDetails(): array
    {
        return $this->userModel->findAllWithDetails();
    }

    public function findAllByBusinessId(int $businessId): array
    {
        return $this->userModel->findAllByBusinessId($businessId);
    }
   public function findByUuidWithDetails(string $uuid): ?array
{
    return $this->userModel->findByUuidWithDetails($uuid);
}

public function findByUuidWithDetailsForBusiness(
    string $uuid,
    int $businessId
): ?array {
    return $this->userModel->findByUuidWithDetailsForBusiness(
        $uuid,
        $businessId
    );
}
public function deactivateByUuid(
    string $uuid,
    ?int $businessId = null
): array {
    $user = $this->userModel->findByUuidWithDetails($uuid);

    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found.'
        ];
    }

    /*
     * Business users may only deactivate users
     * belonging to their own business.
     */
    if ($businessId !== null) {
        if ((int) $user['business_id'] !== $businessId) {
            return [
                'success' => false,
                'message' => 'You do not have permission to deactivate this user.'
            ];
        }
    }

    /*
     * Prevent deactivating a System Admin.
     */
    if ((int) $user['is_system_admin'] === 1) {
        return [
            'success' => false,
            'message' => 'System Administrator accounts cannot be deactivated.'
        ];
    }

    if ((int) $user['is_active'] === 0) {
        return [
            'success' => false,
            'message' => 'User is already inactive.'
        ];
    }

    $success = $this->userModel->deactivateByUuid($uuid);

    if (!$success) {
        return [
            'success' => false,
            'message' => 'Unable to deactivate the user.'
        ];
    }

    $this->auditLogger->success([
        'role_name_at_time' => $_SESSION['role_name'] ?? null,
        'action' => 'DEACTIVATE',
        'module' => 'Users',
        'table_name' => 'users',
        'record_id' => (int) $user['id'],
        'record_uuid' => $user['uuid'],
        'business_id' => $user['business_id'],
        'description' => 'User account deactivated',
        'old_values' => [
            'is_active' => 1
        ],
        'new_values' => [
            'is_active' => 0
        ],
    ]);

    return [
        'success' => true,
        'message' => 'User deactivated successfully.'
    ];
}
public function activateByUuid(
    string $uuid,
    ?int $businessId = null
): array {
    $user = $this->userModel->findByUuidWithDetails($uuid);

    if (!$user) {
        return [
            'success' => false,
            'message' => 'User not found.'
        ];
    }

    if ($businessId !== null) {
        if ((int) $user['business_id'] !== $businessId) {
            return [
                'success' => false,
                'message' => 'You do not have permission to activate this user.'
            ];
        }
    }

    if ((int) $user['is_system_admin'] === 1) {
        return [
            'success' => false,
            'message' => 'System Administrator accounts cannot be activated or deactivated here.'
        ];
    }

    if ((int) $user['is_active'] === 1) {
        return [
            'success' => false,
            'message' => 'User is already active.'
        ];
    }

    $success = $this->userModel->activateByUuid($uuid);

    if (!$success) {
        return [
            'success' => false,
            'message' => 'Unable to activate the user.'
        ];
    }

    $this->auditLogger->success([
        'role_name_at_time' => $_SESSION['role_name'] ?? null,
        'action' => 'ACTIVATE',
        'module' => 'Users',
        'table_name' => 'users',
        'record_id' => (int) $user['id'],
        'record_uuid' => $user['uuid'],
        'business_id' => $user['business_id'],
        'description' => 'User account activated',
        'old_values' => [
            'is_active' => 0
        ],
        'new_values' => [
            'is_active' => 1
        ],
    ]);

    return [
        'success' => true,
        'message' => 'User activated successfully.'
    ];
}
}