<?php

namespace App\Helpers;

class PermissionHelper
{
    /**
     * Get the permissions assigned to the currently
     * authenticated user's role.
     */
    public static function getPermissions(): array
    {
        if (!AuthHelper::check()) {
            return [];
        }

        /*
         * System administrators have unrestricted access.
         */
        if (AuthHelper::isSystemAdmin()) {
            return ['*'];
        }

        return $_SESSION['permissions'] ?? [];
    }

    /**
     * Check whether the current user has a permission.
     */
    public static function can(string $permission): bool
    {
        $permissions = self::getPermissions();

        /*
         * System administrator.
         */
        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * Check whether the current user has at least
     * one of the supplied permissions.
     */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the current user has all
     * supplied permissions.
     */
    public static function canAll(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::can($permission)) {
                return false;
            }
        }

        return true;
    }
}