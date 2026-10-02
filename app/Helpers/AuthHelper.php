<?php

namespace App\Helpers;

class AuthHelper
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : null;
    }

    public static function userUuid(): ?string
    {
        return $_SESSION['user_uuid'] ?? null;
    }

    public static function businessId(): ?int
    {
        return isset($_SESSION['business_id'])
            ? (int) $_SESSION['business_id']
            : null;
    }

    public static function roleId(): ?int
    {
        return isset($_SESSION['role_id'])
            ? (int) $_SESSION['role_id']
            : null;
    }

    public static function roleName(): ?string
    {
        return $_SESSION['role_name'] ?? null;
    }

    public static function fullName(): ?string
    {
        return $_SESSION['full_name'] ?? null;
    }

    public static function isSystemAdmin(): bool
    {
        return !empty($_SESSION['is_system_admin']);
    }

  public static function logout(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Clear all session data
    $_SESSION = [];

    // Remove the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy the session
    session_destroy();
}
}