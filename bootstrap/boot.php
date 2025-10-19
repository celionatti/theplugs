<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| The Bootstrap File
|--------------------------------------------------------------------------
|
| This file is responsible for bootstrapping your application. You can use
| this file to set up any necessary configurations or load essential.
*/

use Plugs\Http\ResponseFactory;
use Plugs\Http\Middleware\CorsMiddleware;
use Plugs\Http\Middleware\CsrfMiddleware;
use Plugs\Http\Middleware\RoutingMiddleware;
use Plugs\Http\Middleware\RateLimitMiddleware;
use Plugs\Http\Middleware\SecurityHeadersMiddleware;

/*
 |----------------------------------------------------------------------
 | Define Constants
 |----------------------------------------------------------------------
 */

define('BASE_PATH', dirname(__DIR__) . '/');
define('APP_PATH', BASE_PATH . 'app/');
define('CONFIG_PATH', BASE_PATH . 'config/');
define('PUBLIC_PATH', BASE_PATH . 'public/');
define('STORAGE_PATH', BASE_PATH . 'storage/');
define('VENDOR_PATH', BASE_PATH . 'vendor/');

/*
 |----------------------------------------------------------------------
 | Autoload Dependencies
 |----------------------------------------------------------------------
 |
 | Here we are loading the Composer autoloader to manage our dependencies.
 */
require VENDOR_PATH . 'autoload.php';

/*
 |----------------------------------------------------------------------
 | Load Environment Variables
 |----------------------------------------------------------------------
 |
 | Load environment variables from the .env file if it exists.
 */
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

/*
 |----------------------------------------------------------------------
 | Initialize Application
 |----------------------------------------------------------------------
 |
 | Here we can initialize the application, set up configurations, and
 | prepare any services needed for the application to run.
 */
$app = new Plugs\Plugs();

/*
 |----------------------------------------------------------------------
 | Container Bindings
 |----------------------------------------------------------------------
 |
 | Here we bind interfaces to their implementations in the service container.
 */
$container = \Plugs\Container\Container::getInstance();

/*
 |----------------------------------------------------------------------
 | Load Service Providers
 |----------------------------------------------------------------------
 |
 | Load and register service providers defined in the configuration.
 */
$serviceLoader = config('services');
$serviceLoader($container);

/*
 |----------------------------------------------------------------------
 | Load Session Configuration
 |----------------------------------------------------------------------
 |
 | Load session settings from the configuration and start the session.
 */
$sessionConfig = config('security.session');
$sessionLoader = new \Plugs\Session\SessionManager($sessionConfig);
$sessionLoader->start();

/*
 |----------------------------------------------------------------------
 | Load Security Configuration
 |----------------------------------------------------------------------
 |
 | Load security settings from the configuration.
 */
$securityConfig = config('security');

// Add security headers middleware
if (!empty($securityConfig['headers'])) {
    $app->pipe(new SecurityHeadersMiddleware($securityConfig['headers']));
}

// Add CORS middleware (if enabled)
if ($securityConfig['cors']['enabled'] ?? false) {
    $app->pipe(new CorsMiddleware(
        $securityConfig['cors']['allowed_origins'],
        $securityConfig['cors']['allowed_methods'],
        $securityConfig['cors']['allowed_headers'],
        $securityConfig['cors']['max_age']
    ));
}

// Add rate limiting middleware (if enabled)
if ($securityConfig['rate_limit']['enabled'] ?? false) {
    $app->pipe(new RateLimitMiddleware(
        $securityConfig['rate_limit']['max_requests'],
        $securityConfig['rate_limit']['per_minutes']
    ));
}

// Add CSRF protection middleware (if enabled)
if ($securityConfig['csrf']['enabled'] ?? false) {
    $app->pipe(new CsrfMiddleware($securityConfig['csrf']['except']));
}

/*
 |----------------------------------------------------------------------
 | Register Router in Container & Set Up Route Facade
 |----------------------------------------------------------------------
 |
 | Create the router instance and register it in the container as a singleton.
 | This makes it accessible via both the container and the Route facade.
 */
$router = new \Plugs\Router\Router();

// Register as singleton in container
$container->singleton('router', function() use ($router) {
    return $router;
});

// Also set directly in facade for immediate use (optional but faster)
\Plugs\Facades\Route::setFacadeInstance('router', $router);

/*
 |----------------------------------------------------------------------
 | Load Routes
 |----------------------------------------------------------------------
 |
 | Load the application routes. You can now use Route::get() statically!
 */
require BASE_PATH . 'routes/web.php';

// Add routing middleware
$app->pipe(new RoutingMiddleware($router, $container));

// Set fallback handler for 404 errors
$app->setFallback(function ($request) {
    $acceptHeader = $request->getHeaderLine('Accept');

    // Return JSON for API requests
    if (strpos($acceptHeader, 'application/json') !== false) {
        return ResponseFactory::json([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found',
            'path' => $request->getUri()->getPath(),
        ], 404);
    }

    // Return HTML for browser requests
    return ResponseFactory::html(
        '<html>
        <head>
            <style>
                body {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
                    background-color: #000000;
                    color: #6b7280;
                }
                .container {
                    text-align: center;
                    max-width: 400px;
                    padding: 2rem;
                }
                .message {
                    font-size: 1.125rem;
                    line-height: 1.6;
                    margin: 1.5rem 0;
                }
                .btn {
                    color: #ffffff;
                    text-decoration: none;
                    background: #dc143c;
                    padding: 6px 10px;
                    border-radius: 8px;
                    font-weight: 600;
                }
                .btn:hover {
                    background: #aa0728ff;
                    box-shadow: 0px 0px 10px 0px #f5d1d8ff;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404 | Not Found</h1>
                <div class="message">
                    The page you are looking for might have been removed, 
                    had its name changed, or is temporarily unavailable.
                </div>
                <a href="/" class="btn">Go to Homepage</a>
            </div>
        </body>
    </html>',
        404
    );
});

/*
 |----------------------------------------------------------------------
 | Return the Application Instance
 |----------------------------------------------------------------------
 |
 | Finally, we return the application instance to be used in the entry point.
 */
return $app;
