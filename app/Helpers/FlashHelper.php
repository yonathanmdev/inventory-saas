<?php

namespace App\Helpers;

class FlashHelper
{
    public static function success(string $message): void
    {
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => $message
        ];
    }

    public static function error(string $message): void
    {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => $message
        ];
    }

    public static function warning(string $message): void
    {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => $message
        ];
    }

    public static function info(string $message): void
    {
        $_SESSION['flash'] = [
            'type' => 'info',
            'message' => $message
        ];
    }

    public static function get(): ?array
    {
        if (empty($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $flash;
    }
}