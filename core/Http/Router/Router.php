<?php

declare(strict_types=1);

namespace Plugs\Http\Router;

use Plugs\Routing\RouteGroup;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Routing\RouteCollection;
use Plugs\Routing\RouteDefinition;
use InvalidArgumentException;

/**
 * HTTP Router for handling route registration and dispatching
 */
class Router
{
    private RouteCollection $routes;
    private RouteDispatcher $dispatcher;
    private array $currentGroupAttributes = [];
    private string $basePath = '';

    /**
     * @param object|null $container Dependency injection container
     */
    public function __construct(?object $container = null)
    {
        $this->routes = new RouteCollection();
        $this->dispatcher = new RouteDispatcher($container);
    }

    /**
     * Set the base path for all routes
     * 
     * @param string $basePath Base path to prepend to all routes
     * @return self
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = rtrim($basePath, '/');
        return $this;
    }

    /**
     * Get the current base path
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    // ==================== HTTP Method Registration ====================

    /**
     * Register a GET route
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action (closure, controller, etc.)
     * @return RouteDefinition
     */
    public function get(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    /**
     * Register a POST route
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function post(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * Register a PUT route
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function put(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * Register a PATCH route
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function patch(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Register a DELETE route
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function delete(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Register an OPTIONS route
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function options(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * Register a route that responds to any HTTP method
     * 
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function any(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * Register a route for specific HTTP methods
     * 
     * @param array $methods Array of HTTP methods
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function match(array $methods, string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute($methods, $uri, $action);
    }

    // ==================== Route Registration ====================

    /**
     * Add a route to the collection
     * 
     * @param array $methods HTTP methods
     * @param string $uri Route URI pattern
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    private function addRoute(array $methods, string $uri, mixed $action): RouteDefinition
    {
        // Normalize URI - prepend base path if set
        $uri = $this->normalizeUri($uri);

        $route = new RouteDefinition($methods, $uri, $action);

        // Apply current group attributes
        $this->applyGroupAttributes($route);

        $this->routes->add($route);

        return $route;
    }

    /**
     * Normalize the URI by prepending base path
     * 
     * @param string $uri Original URI
     * @return string Normalized URI
     */
    private function normalizeUri(string $uri): string
    {
        // Build the full URI with base path
        if ($this->basePath !== '') {
            $uri = $this->basePath . '/' . ltrim($uri, '/');
        }

        // Normalize slashes and ensure non-empty path becomes '/'
        $uri = '/' . trim($uri, '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    /**
     * Apply group attributes to a route
     * 
     * @param RouteDefinition $route Route to apply attributes to
     * @return void
     */
    private function applyGroupAttributes(RouteDefinition $route): void
    {
        // Apply prefix
        if (isset($this->currentGroupAttributes['prefix'])) {
            $route->setPrefix($this->currentGroupAttributes['prefix']);
        }

        // Apply namespace
        if (isset($this->currentGroupAttributes['namespace'])) {
            $route->setNamespace($this->currentGroupAttributes['namespace']);
        }

        // Apply middleware (group middleware should come first)
        if (isset($this->currentGroupAttributes['middleware'])) {
            $route->addGroupMiddleware($this->currentGroupAttributes['middleware']);
        }

        // Apply domain
        if (isset($this->currentGroupAttributes['domain'])) {
            $route->domain($this->currentGroupAttributes['domain']);
        }
    }

    // ==================== Fallback Route ====================

    /**
     * Register a fallback route for unmatched requests
     * 
     * @param mixed $action Route action
     * @return RouteDefinition
     */
    public function fallback(mixed $action): RouteDefinition
    {
        $route = new RouteDefinition(
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            '*',
            $action
        );

        $this->routes->setFallback($route);

        return $route;
    }

    // ==================== Route Groups ====================

    /**
     * Create a route group with shared attributes
     * 
     * @param array $attributes Group attributes (prefix, middleware, namespace, domain)
     * @param callable $callback Callback to register routes
     * @return void
     */
    public function group(array $attributes, callable $callback): void
    {
        $group = new RouteGroup($this, $attributes);
        $group->group($callback);
    }

    /**
     * Create a route group with a prefix
     * 
     * @param string $prefix URI prefix
     * @return RouteGroup
     */
    public function prefix(string $prefix): RouteGroup
    {
        return new RouteGroup($this, ['prefix' => $prefix]);
    }

    /**
     * Create a route group with middleware
     * 
     * @param string|array $middleware Middleware name(s)
     * @return RouteGroup
     */
    public function middleware(string|array $middleware): RouteGroup
    {
        return new RouteGroup($this, ['middleware' => $middleware]);
    }

    /**
     * Create a route group with a namespace
     * 
     * @param string $namespace Controller namespace
     * @return RouteGroup
     */
    public function namespace(string $namespace): RouteGroup
    {
        return new RouteGroup($this, ['namespace' => $namespace]);
    }

    /**
     * Create a route group with a domain
     * 
     * @param string $domain Domain constraint
     * @return RouteGroup
     */
    public function domain(string $domain): RouteGroup
    {
        return new RouteGroup($this, ['domain' => $domain]);
    }

    // ==================== Group Attribute Management ====================

    /**
     * Get current group attributes
     * 
     * @return array
     */
    public function getCurrentGroupAttributes(): array
    {
        return $this->currentGroupAttributes;
    }

    /**
     * Set current group attributes
     * 
     * @param array $attributes Attributes to set
     * @return void
     */
    public function setCurrentGroupAttributes(array $attributes): void
    {
        $this->currentGroupAttributes = $attributes;
    }

    // ==================== Route Dispatching ====================

    /**
     * Dispatch the request to the matched route
     * 
     * @param Request $request HTTP request
     * @return Response HTTP response
     */
    public function dispatch(Request $request): Response
    {
        $route = $this->routes->match($request);
        return $this->dispatcher->dispatch($route, $request);
    }

    // ==================== URL Generation ====================

    /**
     * Generate a URL for a named route
     * 
     * @param string $name Route name
     * @param array $parameters URL parameters
     * @return string Generated URL
     * @throws InvalidArgumentException If route not found
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);

        if ($route === null) {
            throw new InvalidArgumentException(
                sprintf('Route "%s" not found', $name)
            );
        }

        return $this->buildUrl($route->getUri(), $parameters);
    }

    /**
     * Build URL from pattern and parameters
     * 
     * @param string $pattern Route pattern
     * @param array $parameters URL parameters
     * @return string Built URL
     */
    private function buildUrl(string $pattern, array $parameters): string
    {
        $url = $pattern;

        // Replace required and optional parameters
        foreach ($parameters as $key => $value) {
            $url = str_replace(
                ['{' . $key . '}', '{' . $key . '?}'],
                (string) $value,
                $url
            );
        }

        // Remove remaining optional parameters
        $url = (string) preg_replace('/\{[^}]+\?\}/', '', $url);

        // Clean up multiple slashes
        $url = (string) preg_replace('#/+#', '/', $url);

        return $url;
    }

    // ==================== Getters ====================

    /**
     * Get the route collection
     * 
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Get the route dispatcher
     * 
     * @return RouteDispatcher
     */
    public function getDispatcher(): RouteDispatcher
    {
        return $this->dispatcher;
    }
}
