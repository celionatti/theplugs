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
use Plugs\Middleware\MiddlewareManager;

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
     * The middleware manager instance.
     */
    protected ?MiddlewareManager $middlewareManager = null;

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

        // Get middleware from MiddlewareManager
        $middleware = $this->gatherMiddleware($request);

        return (new Pipeline($this->container))
            ->send($request)
            ->through($this->app->shouldSkipMiddleware($request) ? [] : $middleware)
            ->then($this->dispatchToRouter());
    }

    /**
     * Gather all middleware for the request.
     */
    protected function gatherMiddleware(Request $request): array
    {
        // Lazy load the middleware manager
        if ($this->middlewareManager === null) {
            $this->middlewareManager = $this->container->get(MiddlewareManager::class);
        }

        // Get global middleware from MiddlewareManager
        return $this->middlewareManager->getGlobal();
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
     * Get the middleware groups from MiddlewareManager.
     */
    public function getMiddlewareGroups(): array
    {
        if ($this->middlewareManager === null) {
            $this->middlewareManager = $this->container->get(MiddlewareManager::class);
        }
        
        return $this->middlewareManager->getGroups();
    }

    /**
     * Get the route middleware from MiddlewareManager.
     */
    public function getRouteMiddleware(): array
    {
        if ($this->middlewareManager === null) {
            $this->middlewareManager = $this->container->get(MiddlewareManager::class);
        }
        
        return $this->middlewareManager->getRouteMiddlewares();
    }
}