<?php

declare(strict_types=1);

namespace Plugs\Http\Router;

use Plugs\Routing\RouteGroup;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Routing\RouteDefinition;

/**
 * Static facade for the Router class
 * 
 * Provides convenient static method access to routing functionality
 */
class Route
{
    /**
     * The Router instance
     */
    private static ?Router $router = null;

    /**
     * Set the router instance
     */
    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    /**
     * Get the router instance, creating one if it doesn't exist
     */
    private static function getRouter(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
        }

        return self::$router;
    }

    /**
     * Register a GET route
     */
    public static function get(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->get($uri, $action);
    }

    /**
     * Register a POST route
     */
    public static function post(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->post($uri, $action);
    }

    /**
     * Register a PUT route
     */
    public static function put(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->put($uri, $action);
    }

    /**
     * Register a PATCH route
     */
    public static function patch(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->patch($uri, $action);
    }

    /**
     * Register a DELETE route
     */
    public static function delete(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->delete($uri, $action);
    }

    /**
     * Register an OPTIONS route
     */
    public static function options(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->options($uri, $action);
    }

    /**
     * Register a route that responds to any HTTP method
     */
    public static function any(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->any($uri, $action);
    }

    /**
     * Register a route that responds to specific HTTP methods
     */
    public static function match(array $methods, string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->match($methods, $uri, $action);
    }

    /**
     * Create a route group with shared attributes
     */
    public static function group(array $attributes, callable $callback): void
    {
        self::getRouter()->group($attributes, $callback);
    }

    /**
     * Create a route group with a prefix
     */
    public static function prefix(string $prefix): RouteGroup
    {
        return self::getRouter()->prefix($prefix);
    }

    /**
     * Create a route group with middleware
     */
    public static function middleware(string|array $middleware): RouteGroup
    {
        return self::getRouter()->middleware($middleware);
    }

    /**
     * Create a route group with a namespace
     */
    public static function namespace(string $namespace): RouteGroup
    {
        return self::getRouter()->namespace($namespace);
    }

    /**
     * Create a route group with a domain
     */
    public static function domain(string $domain): RouteGroup
    {
        return self::getRouter()->domain($domain);
    }

    /**
     * Register a fallback route
     */
    public static function fallback(mixed $action): RouteDefinition
    {
        return self::getRouter()->fallback($action);
    }

    /**
     * Generate a URL for a named route
     */
    public static function url(string $name, array $parameters = []): string
    {
        return self::getRouter()->url($name, $parameters);
    }

    /**
     * Dispatch the request to the appropriate route
     */
    public static function dispatch(Request $request): Response
    {
        return self::getRouter()->dispatch($request);
    }

    /**
     * Get the underlying router instance
     */
    public static function getRouterInstance(): Router
    {
        return self::getRouter();
    }

    /**
     * Set the base path for all routes
     */
    public static function setBasePath(string $basePath): void
    {
        self::getRouter()->setBasePath($basePath);
    }

    /**
     * Get the current base path
     */
    public static function getBasePath(): string
    {
        return self::getRouter()->getBasePath();
    }

    /**
     * Clear the router instance (useful for testing)
     */
    public static function clearRouter(): void
    {
        self::$router = null;
    }
}
