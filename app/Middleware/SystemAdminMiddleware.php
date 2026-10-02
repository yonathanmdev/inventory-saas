<?php

namespace App\Middleware;

use App\Helpers\AuthHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SystemAdminMiddleware
{
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (!AuthHelper::check()) {
            return AuthMiddleware::denyAccess($request, '/login', 'authentication_required');
        }

        if ((int) ($_SESSION['is_system_admin'] ?? 0) !== 1) {
            return AuthMiddleware::denyAccess($request, '/dashboard', 'access_denied');
        }

        return $handler->handle($request);
    }
}