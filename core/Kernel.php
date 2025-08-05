<?php

declare(strict_types=1);

namespace Plugs;

use Closure;
use Plugs\Container\Container;
use Throwable;
use Plugs\Pipeline;
use Plugs\Http\Router\Router;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

class Kernel
{
    /**
     * The application instance.
     */
    protected Container $container;
    /**
     * The base path of the application.
     */
    protected Plugs $app;

    /**
     * The router instance.
     */
    protected Router $router;

    /**
     * The application's global HTTP middleware stack.
     */
    protected array $middleware = [
        // Add global middleware here
        // \App\Http\Middleware\TrustProxies::class,
        // \App\Http\Middleware\HandleCors::class,
    ];

    /**
     * The application's route middleware groups.
     */
    protected array $middlewareGroups = [
        'web' => [
            // Add web middleware here
            // \App\Http\Middleware\EncryptCookies::class,
            // \App\Http\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            // Add API middleware here
            // 'throttle:api',
            // \App\Http\Middleware\ForceJsonResponse::class,
        ],
    ];

    /**
     * The application's route middleware.
     */
    protected array $routeMiddleware = [
        // 'auth' => \App\Http\Middleware\Authenticate::class,
        // 'guest' => \App\Http\Middlesware\RedirectIfAuthenticated::class,
        // Add more route middleware here
    ];

    /**
     * Create a new HTTP kernel instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->app = $container->make(Plugs::class);
        $this->router = $this->container->make(Router::class);
    }

    /**
     * Handle an incoming HTTP request.
     */
    public function handle(Request $request): Response
    {
        try {
            $request->enableHttpMethodParameterOverride();

            $response = $this->sendRequestThroughRouter($request);
        } catch (Throwable $e) {
            $response = $this->app->handleException($request, $e);
        }

        return $response;
    }

    /**
     * Send the given request through the middleware / router.
     */
    protected function sendRequestThroughRouter(Request $request): Response
    {
        $this->container->instance('request', $request);

        return (new Pipeline($this->container))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware($request) ? [] : $this->middleware)
            ->then($this->dispatchToRouter());
    }

    /**
     * Get the route dispatcher callback.
     */
    protected function dispatchToRouter(): Closure
    {
        return function (Request $request) {
            $this->container->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }

    /**
     * Get the middleware groups.
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    /**
     * Get the route middleware.
     */
    public function getRouteMiddleware(): array
    {
        return $this->routeMiddleware;
    }
}