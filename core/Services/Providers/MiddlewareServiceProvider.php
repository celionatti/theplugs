<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Services\ServiceProvider;
use Plugs\Middleware\MiddlewareManager;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register middleware services.
     */
    public function register(): void
    {
        $this->registerMiddlewareManager();
    }

    /**
     * Register the middleware manager.
     */
    protected function registerMiddlewareManager(): void
    {
        $this->singleton('middleware', function () {
            return new MiddlewareManager($this->container);
        });

        $this->alias('middleware', MiddlewareManager::class);
    }

    /**
     * Bootstrap middleware services.
     */
    public function boot(): void
    {
        $this->registerGlobalMiddleware();
        $this->registerRouteMiddleware();
        $this->registerMiddlewareGroups();
    }

    /**
     * Register global middleware that runs on every request.
     */
    protected function registerGlobalMiddleware(): void
    {
        $middlewareManager = $this->container->get('middleware');
        $globalMiddleware = $this->config('middleware.global', []);

        foreach ($globalMiddleware as $middleware) {
            $middlewareManager->pushGlobal($middleware);
        }
    }

    /**
     * Register route middleware aliases.
     */
    protected function registerRouteMiddleware(): void
    {
        $middlewareManager = $this->container->get('middleware');
        $routeMiddleware = $this->config('middleware.route', []);

        foreach ($routeMiddleware as $key => $middleware) {
            $middlewareManager->alias($key, $middleware);
        }
    }

    /**
     * Register middleware groups.
     */
    protected function registerMiddlewareGroups(): void
    {
        $middlewareManager = $this->container->get('middleware');
        $middlewareGroups = $this->config('middleware.groups', []);

        foreach ($middlewareGroups as $group => $middlewares) {
            $middlewareManager->group($group, $middlewares);
        }
    }

    /**
     * Get default middleware configuration.
     */
    protected function getDefaultMiddlewareConfig(): array
    {
        return [
            'global' => [
                // Global middleware that runs on every request
            ],
            'groups' => [
                'web' => [
                    // Web middleware group
                ],
                'api' => [
                    // API middleware group
                ],
            ],
            'route' => [
                // Route-specific middleware aliases
                'auth' => \Plugs\Http\Middleware\AuthMiddleware::class,
                'guest' => \Plugs\Http\Middleware\GuestMiddleware::class,
                'throttle' => \Plugs\Http\Middleware\ThrottleMiddleware::class,
            ],
        ];
    }
}