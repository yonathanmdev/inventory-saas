<?php

namespace App\Middleware;

use App\Helpers\Csp;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private bool $production,
        private bool $cspReportOnly = false
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $isHttps = $this->isHttps($request);

        // 1. Force HTTPS in production. Use the configured host, not the Host header.
        if ($this->production && !$isHttps) {
            $host = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_HOST);

            if ($host) {
                return (new SlimResponse(301))->withHeader(
                    'Location',
                    'https://' . $host . $request->getUri()->getPath()
                        . ($request->getUri()->getQuery() !== ''
                            ? '?' . $request->getUri()->getQuery() : '')
                );
            }
        }

        $nonce    = Csp::nonce();
        $response = $handler->handle($request);

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if ($this->production) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $cspHeader = $this->cspReportOnly
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response = $response
            ->withHeader($cspHeader, implode('; ', $directives))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->withHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->withoutHeader('X-Powered-By');

        if ($isHttps) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Private pages and exports must never be cached.
        // Static files are served by the web server and never reach this code.
        if (!$response->hasHeader('Cache-Control')) {
            $response = $response
                ->withHeader('Cache-Control', 'no-store, max-age=0')
                ->withHeader('Pragma', 'no-cache');
        }

        return $response;
    }

    private function isHttps(Request $request): bool
    {
        $server = $request->getServerParams();

        if (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') {
            return true;
        }

        return ($_ENV['TRUST_PROXY'] ?? '0') === '1'
            && ($server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}