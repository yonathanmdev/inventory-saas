<?php

use App\Middleware\CsrfMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Environment
|--------------------------------------------------------------------------
*/

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$production = ($_ENV['APP_ENV'] ?? 'production') !== 'local';

/*
|--------------------------------------------------------------------------
| PHP Error / Security Settings
|--------------------------------------------------------------------------
*/

ini_set('display_errors', $production ? '0' : '1');
ini_set('log_errors', '1');
ini_set('expose_php', '0');

header_remove('X-Powered-By');

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../config/session.php';

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
|
| container.php uses getPDO(), which is defined by database.php.
|
*/

require __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Container
|--------------------------------------------------------------------------
*/

$container = require __DIR__ . '/../config/container.php';

AppFactory::setContainer($container);

/*
|--------------------------------------------------------------------------
| Create Slim Application
|--------------------------------------------------------------------------
*/

$app = AppFactory::create();

/*
|--------------------------------------------------------------------------
| Routing Middleware
|--------------------------------------------------------------------------
*/

$app->addRoutingMiddleware();

/*
|--------------------------------------------------------------------------
| Body Parsing Middleware
|--------------------------------------------------------------------------
|
| Must be available before CSRF reads parsed request data.
|
*/

$app->addBodyParsingMiddleware();

/*
|--------------------------------------------------------------------------
| CSRF Middleware
|--------------------------------------------------------------------------
*/

$app->add(new CsrfMiddleware());

/*
|--------------------------------------------------------------------------
| Error Middleware
|--------------------------------------------------------------------------
*/

$errorMiddleware = $app->addErrorMiddleware(
    !$production,
    true,
    true
);

/*
|--------------------------------------------------------------------------
| Production Error Handler
|--------------------------------------------------------------------------
*/

if ($production) {
    $errorMiddleware->setDefaultErrorHandler(
        function ($request, Throwable $e) use ($app) {

            // Log the complete exception server-side.
            error_log((string) $e);

            $code = $e instanceof HttpException
                ? $e->getCode()
                : 500;

            $response = $app
                ->getResponseFactory()
                ->createResponse($code);

            $message = match ($code) {
                404 => 'Page not found.',
                403 => 'You do not have permission to access this page.',
                401 => 'Authentication is required.',
                default => 'Something went wrong.',
            };

            $response->getBody()->write($message);

            return $response;
        }
    );
}

/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
*/

$app->add(
    new SecurityHeadersMiddleware(
        $production,
        cspReportOnly: false
    )
);

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

(require __DIR__ . '/../routes/web.php')($app);

/*
|--------------------------------------------------------------------------
| Run Application
|--------------------------------------------------------------------------
*/

$app->run();