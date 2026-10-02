<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\PermissionModel;
use App\Helpers\Csp;

class AuthService
{
    public function __construct(
        private UserModel $userModel,
        private PermissionModel $permissionModel,
        private AuditLogger $auditLogger
    ) {
    }

    /**
     * Authenticate a user.
     */
    public function login(
        string $login,
        string $password
    ): array {

        $login = trim($login);

        /*
        |--------------------------------------------------------------------------
        | Validate credentials
        |--------------------------------------------------------------------------
        */

        if ($login === '' || $password === '') {

            $this->auditLogger->failed([
                'action' => 'LOGIN_FAILED',
                'module' => 'Authentication',
                'table_name' => 'users',
                'description' => 'Login attempt with missing credentials',
            ]);

            return [
                'success' => false,
                'message' => 'Username and password are required.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Find User
        |--------------------------------------------------------------------------
        */

        $user = $this->userModel->findForLogin($login);

        /*
        |--------------------------------------------------------------------------
        | User Not Found
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            $this->auditLogger->failed([
                'action' => 'LOGIN_FAILED',
                'module' => 'Authentication',
                'table_name' => 'users',
                'description' => 'Invalid username or email during login',
            ]);

            return [
                'success' => false,
                'message' => 'Invalid username or password.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Account Status
        |--------------------------------------------------------------------------
        */

        if (!(bool) $user['is_active']) {

            $this->auditLogger->failed([
                'action' => 'LOGIN_FAILED',
                'module' => 'Authentication',
                'table_name' => 'users',
                'record_id' => (int) $user['id'],
                'record_uuid' => $user['uuid'] ?? null,
                'business_id' => $user['business_id'] !== null
                    ? (int) $user['business_id']
                    : null,
                'description' => 'Login attempt for inactive account',
            ]);

            return [
                'success' => false,
                'message' => 'Your account is inactive. Please contact the administrator.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Verify Password
        |--------------------------------------------------------------------------
        */

        if (!password_verify($password, $user['password_hash'])) {

            $this->auditLogger->failed([
                'action' => 'LOGIN_FAILED',
                'module' => 'Authentication',
                'table_name' => 'users',
                'record_id' => (int) $user['id'],
                'record_uuid' => $user['uuid'] ?? null,
                'business_id' => $user['business_id'] !== null
                    ? (int) $user['business_id']
                    : null,
                'description' => 'Invalid password during login',
            ]);

            return [
                'success' => false,
                'message' => 'Invalid username/email or password.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Session Fixation Protection
        |--------------------------------------------------------------------------
        */

        session_regenerate_id(true);

        /*
        |--------------------------------------------------------------------------
        | Clear Previous Authentication State
        |--------------------------------------------------------------------------
        */

        unset(
            $_SESSION['user_id'],
            $_SESSION['user_uuid'],
            $_SESSION['business_id'],
            $_SESSION['role_id'],
            $_SESSION['role_name'],
            $_SESSION['full_name'],
            $_SESSION['username'],
            $_SESSION['email'],
            $_SESSION['is_system_admin'],
            $_SESSION['permissions']
        );

        /*
        |--------------------------------------------------------------------------
        | Establish Authenticated Session
        |--------------------------------------------------------------------------
        */

        $_SESSION['user_id'] = (int) $user['id'];

        $_SESSION['user_uuid'] = $user['uuid'];

        $_SESSION['business_id'] = $user['business_id'] !== null
            ? (int) $user['business_id']
            : null;

        $_SESSION['role_id'] = (int) $user['role_id'];

        $_SESSION['role_name'] = $user['role_name'];

        $_SESSION['full_name'] = $user['full_name'];

        $_SESSION['username'] = $user['username'];

        $_SESSION['email'] = $user['email'];

        $_SESSION['is_system_admin'] = (bool) $user['is_system_admin'];

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        |
        | System Admin has unrestricted access and therefore does not
        | need permissions stored in role_permissions.
        |
        */

        if ($_SESSION['is_system_admin']) {

            $_SESSION['permissions'] = [];

        } else {

            $_SESSION['permissions'] =
                $this->permissionModel->findKeysByRoleId(
                    (int) $user['role_id']
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Regenerate CSRF Token
        |--------------------------------------------------------------------------
        |
        | The authenticated session gets a fresh CSRF token.
        |
        */

        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );

        /*
        |--------------------------------------------------------------------------
        | Refresh CSP Nonce
        |--------------------------------------------------------------------------
        |
        | A new authenticated request should not reuse an old nonce.
        |
        */

        unset($_SESSION['csp_nonce']);

        /*
        |--------------------------------------------------------------------------
        | Update Last Login
        |--------------------------------------------------------------------------
        */

        $this->userModel->updateLastLogin(
            (int) $user['id']
        );

        /*
        |--------------------------------------------------------------------------
        | Audit Successful Login
        |--------------------------------------------------------------------------
        |
        | Never store the password or password hash.
        |
        */

        $this->auditLogger->success([
            'role_name_at_time' => $user['role_name'] ?? null,
            'action' => 'LOGIN',
            'module' => 'Authentication',
            'table_name' => 'users',
            'record_id' => (int) $user['id'],
            'record_uuid' => $user['uuid'] ?? null,
            'business_id' => $user['business_id'] !== null
                ? (int) $user['business_id']
                : null,
            'description' => 'User logged in successfully',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            'success' => true,
            'user' => $user,
        ];
    }
}
