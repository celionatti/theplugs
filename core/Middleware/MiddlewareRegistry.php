<?php

declare(strict_types=1);

namespace Plugs\Middleware;

use Plugs\Container\Container;
use Plugs\Exceptions\Controller\MiddlewareNotFoundException;

class MiddlewareRegistry
{
    private static array $aliases = [];
    private static Container $container;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    public static function alias(string $alias, string $className): void
    {
        self::$aliases[$alias] = $className;
    }

    public static function resolve(string $middleware): MiddlewareInterface
    {
        $className = self::$aliases[$middleware] ?? $middleware;
        
        if (!class_exists($className)) {
            throw new MiddlewareNotFoundException($middleware);
        }

        if (isset(self::$container) && self::$container->has($className)) {
            return self::$container->get($className);
        }

        return new $className();
    }
}