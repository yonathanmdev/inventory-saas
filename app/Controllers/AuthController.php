<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Services\AuthService;

class AuthController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    /**
     * Display login page.
     */
    public function showLogin($request, $response)
    {
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        $oldUsername = $_SESSION['old_username'] ?? '';
        unset($_SESSION['old_username']);

        ob_start();

        require __DIR__ . '/../Views/auth/login.php';

        $html = ob_get_clean();

        $response->getBody()->write($html);

        return $response;
    }

    /**
     * Process login.
     *
     * CSRF validation is handled by CsrfMiddleware.
     */
    public function login($request, $response)
    {
        $data = $request->getParsedBody() ?? [];

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        $result = $this->authService->login(
            $username,
            $password
        );

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            $_SESSION['old_username'] = $username;

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        /*
         * AuthService has already populated the authenticated
         * session. Determine the destination from that session.
         */
        if (AuthHelper::isSystemAdmin()) {
            return $response
                ->withHeader('Location', '/system-admin')
                ->withStatus(302);
        }

        return $response
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }

    /**
     * Logout.
     *
     * CSRF validation is handled by CsrfMiddleware.
     */
    public function logout($request, $response)
    {
        AuthHelper::logout();

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}

