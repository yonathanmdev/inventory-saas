<?php

namespace App\Middleware;

use App\Helpers\AuthHelper;
use App\Helpers\PermissionHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class PermissionMiddleware
{
    public function __construct(
        private string $permission
    ) {
    }

    public function __invoke(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {

    if (!AuthHelper::check()) {
        return (new Response())
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }

    /*
     * System Admin has unrestricted access.
     */
    if (AuthHelper::isSystemAdmin()) {
        return $handler->handle($request);
    }

    /*
     * Normal users must have the required permission.
     */
    if (!PermissionHelper::can($this->permission)) {
        $response = new Response();

        $response->getBody()->write(
            '403 - You do not have permission to access this page.'
        );

        return $response->withStatus(403);
    }

    return $handler->handle($request);
}
}