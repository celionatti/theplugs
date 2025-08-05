<?php

declare(strict_types=1);

namespace Plugs\Http\Router;

use Plugs\Routing\RouteGroup;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Routing\RouteDefinition;

class Route
{
    private static ?Router $router = null;

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    private static function getRouter(): Router
    {
        if (!self::$router) {
            self::$router = new Router();
        }
        return self::$router;
    }

    // HTTP method shortcuts

    public static function get(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->get($uri, $action);
    }

    public static function post(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->post($uri, $action);
    }

    public static function put(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->put($uri, $action);
    }

    public static function patch(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->patch($uri, $action);
    }

    public static function delete(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->delete($uri, $action);
    }

    public static function options(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->options($uri, $action);
    }

    public static function any(string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->any($uri, $action);
    }

    public static function match(array $methods, string $uri, mixed $action): RouteDefinition
    {
        return self::getRouter()->match($methods, $uri, $action);
    }

    // Route groups

    public static function group(array $attributes, callable $callback): void
    {
        self::getRouter()->group($attributes, $callback);
    }

    public static function prefix(string $prefix): RouteGroup
    {
        return self::getRouter()->prefix($prefix);
    }

    public static function middleware(string|array $middleware): RouteGroup
    {
        return self::getRouter()->middleware($middleware);
    }

    public static function namespace(string $namespace): RouteGroup
    {
        return self::getRouter()->namespace($namespace);
    }

    public static function domain(string $domain): RouteGroup
    {
        return self::getRouter()->domain($domain);
    }

    // Fallback

    public static function fallback(mixed $action): RouteDefinition
    {
        return self::getRouter()->fallback($action);
    }

    // URL generation

    public static function url(string $name, array $parameters = []): string
    {
        return self::getRouter()->url($name, $parameters);
    }

    // Dispatch

    public static function dispatch(Request $request): Response
    {
        return self::getRouter()->dispatch($request);
    }

    // Get router instance

    public static function getRouterInstance(): Router
    {
        return self::getRouter();
    }
}