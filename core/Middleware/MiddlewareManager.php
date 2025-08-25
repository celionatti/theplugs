<?php

declare(strict_types=1);

namespace Plugs\Middleware;

use Plugs\Container\Container;
use InvalidArgumentException;

class MiddlewareManager
{
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * The global middleware stack.
     */
    protected array $globalMiddleware = [];

    /**
     * The route middleware aliases.
     */
    protected array $routeMiddleware = [];

    /**
     * The middleware groups.
     */
    protected array $middlewareGroups = [];

    /**
     * Create a new middleware manager instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add middleware to the global stack.
     */
    public function pushGlobal(string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    /**
     * Prepend middleware to the global stack.
     */
    public function prependGlobal(string $middleware): self
    {
        array_unshift($this->globalMiddleware, $middleware);
        return $this;
    }

    /**
     * Register a route middleware alias.
     */
    public function alias(string $name, string $middleware): self
    {
        $this->routeMiddleware[$name] = $middleware;
        return $this;
    }

    /**
     * Register a middleware group.
     */
    public function group(string $name, array $middlewares): self
    {
        $this->middlewareGroups[$name] = $middlewares;
        return $this;
    }

    /**
     * Get the global middleware.
     */
    public function getGlobal(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Get route middleware by alias.
     */
    public function getRouteMiddleware(string $alias): ?string
    {
        return $this->routeMiddleware[$alias] ?? null;
    }

    /**
     * Get all route middleware aliases.
     */
    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddleware;
    }

    /**
     * Get middleware group by name.
     */
    public function getGroup(string $group): array
    {
        return $this->middlewareGroups[$group] ?? [];
    }

    /**
     * Get all middleware groups.
     */
    public function getGroups(): array
    {
        return $this->middlewareGroups;
    }

    /**
     * Resolve middleware from various formats.
     */
    public function resolve(mixed $middleware): array
    {
        if (is_string($middleware)) {
            return $this->parseMiddleware($middleware);
        }

        if (is_array($middleware)) {
            $resolved = [];
            foreach ($middleware as $m) {
                $resolved = array_merge($resolved, $this->resolve($m));
            }
            return $resolved;
        }

        throw new InvalidArgumentException('Middleware must be a string or array.');
    }

    /**
     * Parse middleware string to resolve aliases and groups.
     */
    protected function parseMiddleware(string $middleware): array
    {
        // Check if it's a group
        if (isset($this->middlewareGroups[$middleware])) {
            return $this->resolve($this->middlewareGroups[$middleware]);
        }

        // Check if it's an alias
        if (isset($this->routeMiddleware[$middleware])) {
            return [$this->routeMiddleware[$middleware]];
        }

        // It's a direct class name
        return [$middleware];
    }

    /**
     * Create middleware instance.
     */
    public function make(string $middleware): object
    {
        // Parse middleware with parameters (e.g., 'throttle:60,1')
        [$class, $parameters] = $this->parseMiddlewareParameters($middleware);

        // Resolve the middleware from the container
        $instance = $this->container->get($class);

        // If the middleware has parameters, we might need to configure it
        if (!empty($parameters) && method_exists($instance, 'setParameters')) {
            $instance->setParameters($parameters);
        }

        return $instance;
    }

    /**
     * Parse middleware parameters from string.
     */
    protected function parseMiddlewareParameters(string $middleware): array
    {
        if (strpos($middleware, ':') === false) {
            return [$middleware, []];
        }

        [$class, $parameterString] = explode(':', $middleware, 2);
        $parameters = explode(',', $parameterString);

        return [$class, array_map('trim', $parameters)];
    }

    /**
     * Sort middleware by priority (if priority property exists).
     */
    public function sortMiddleware(array $middleware): array
    {
        return collect($middleware)->sortBy(function ($m) {
            $instance = $this->container->get($m);
            return property_exists($instance, 'priority') ? $instance->priority : 0;
        })->values()->all();
    }

    /**
     * Check if middleware exists.
     */
    public function has(string $middleware): bool
    {
        return isset($this->routeMiddleware[$middleware]) || 
               isset($this->middlewareGroups[$middleware]) ||
               class_exists($middleware);
    }

    /**
     * Remove middleware from global stack.
     */
    public function removeGlobal(string $middleware): self
    {
        $this->globalMiddleware = array_filter(
            $this->globalMiddleware,
            fn($m) => $m !== $middleware
        );
        
        return $this;
    }

    /**
     * Remove route middleware alias.
     */
    public function removeAlias(string $alias): self
    {
        unset($this->routeMiddleware[$alias]);
        return $this;
    }

    /**
     * Remove middleware group.
     */
    public function removeGroup(string $group): self
    {
        unset($this->middlewareGroups[$group]);
        return $this;
    }
}