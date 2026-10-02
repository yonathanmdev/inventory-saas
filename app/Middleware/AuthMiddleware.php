<?php

namespace App\Middleware;

use App\Helpers\AuthHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (!AuthHelper::check()) {
            return $this->denyAccess($request, '/login', 'authentication_required');
        }

        return $handler->handle($request);
    }

    public static function denyAccess(
        ServerRequestInterface $request,
        string $redirectTo,
        string $reason
    ): ResponseInterface {

        $acceptHeader = $request->getHeaderLine('Accept');

        if (str_contains($acceptHeader, 'application/json')) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'status'   => 'error',
                'message'  => $reason,
                'redirect' => $redirectTo,
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        return (new Response())
            ->withHeader('Location', $redirectTo)
            ->withStatus(302);
    }
}