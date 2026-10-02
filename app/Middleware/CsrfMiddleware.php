<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function process(Request $request, Handler $handler): Response
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        // Defence in depth: if the browser sends Origin, it must be our host
        $origin = $request->getHeaderLine('Origin');
        $host   = $request->getUri()->getHost();

        if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== $host) {
            return $this->reject();
        }

        $sent = (string) (
            ((array) $request->getParsedBody())['csrf_token']
            ?? $request->getHeaderLine('X-CSRF-Token')
        );

        $expected = (string) ($_SESSION['csrf_token'] ?? '');

        if ($expected === '' || !hash_equals($expected, $sent)) {
            return $this->reject();
        }

        return $handler->handle($request);
    }

    private function reject(): Response
    {
        $res = new SlimResponse(419);
        $res->getBody()->write('Your session expired or the request was invalid. Go back, refresh the page and try again.');

        return $res;
    }
}