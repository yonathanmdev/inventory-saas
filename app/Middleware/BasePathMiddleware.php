<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Stream;

final class BasePathMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $response = $handler->handle($request);
        $base = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/');

        if ($base === '') {
            return $response;
        }

        // Redirects: /login -> /inventory/login
        $location = $response->getHeaderLine('Location');
        if (
            $location !== ''
            && str_starts_with($location, '/')
            && !str_starts_with($location, '//')
            && !str_starts_with($location, $base . '/')
        ) {
            $response = $response->withHeader('Location', $base . $location);
        }

        // HTML pages only (skip images, downloads, JSON)
        $type = $response->getHeaderLine('Content-Type');
        if ($type !== '' && stripos($type, 'text/html') === false) {
            return $response;
        }

        $skip = preg_quote(ltrim($base, '/'), '~');
        $html = preg_replace(
            '~\b(href|src|action)=(["\'])/(?!/|' . $skip . '(?:/|["\'?#]))~i',
            '$1=$2' . $base . '/',
            (string) $response->getBody()
        );

        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($html);
        $stream->rewind();

        return $response->withBody($stream);
    }
}