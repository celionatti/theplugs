<?php

declare(strict_types=1);

namespace Plugs\Http\Router;

use Plugs\Routing\RouteGroup;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Routing\RouteCollection;
use Plugs\Routing\RouteDefinition;

class Router
{
    private RouteCollection $routes;
    private RouteDispatcher $dispatcher;
    private array $currentGroupAttributes = [];

    public function __construct(?object $container = null)
    {
        $this->routes = new RouteCollection();
        $this->dispatcher = new RouteDispatcher($container);
    }

    // HTTP method registration methods

    public function get(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function options(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    public function any(string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    public function match(array $methods, string $uri, mixed $action): RouteDefinition
    {
        return $this->addRoute($methods, $uri, $action);
    }

    // Route registration

    private function addRoute(array $methods, string $uri, mixed $action): RouteDefinition
    {
        $route = new RouteDefinition($methods, $uri, $action);

        // Apply current group attributes
        $this->applyGroupAttributes($route);

        $this->routes->add($route);

        return $route;
    }

    private function applyGroupAttributes(RouteDefinition $route): void
    {
        if (isset($this->currentGroupAttributes['prefix'])) {
            $route->setPrefix($this->currentGroupAttributes['prefix']);
        }

        if (isset($this->currentGroupAttributes['namespace'])) {
            $route->setNamespace($this->currentGroupAttributes['namespace']);
        }

        if (isset($this->currentGroupAttributes['middleware'])) {
            $route->addGroupMiddleware($this->currentGroupAttributes['middleware']);
        }

        if (isset($this->currentGroupAttributes['domain'])) {
            $route->domain($this->currentGroupAttributes['domain']);
        }
    }

    // Fallback route

    public function fallback(mixed $action): RouteDefinition
    {
        $route = new RouteDefinition(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '*', $action);
        $this->routes->setFallback($route);
        return $route;
    }

    // Route groups

    public function group(array $attributes, callable $callback): void
    {
        $group = new RouteGroup($this, $attributes);
        $group->group($callback);
    }

    public function prefix(string $prefix): RouteGroup
    {
        return new RouteGroup($this, ['prefix' => $prefix]);
    }

    public function middleware(string|array $middleware): RouteGroup
    {
        return new RouteGroup($this, ['middleware' => $middleware]);
    }

    public function namespace(string $namespace): RouteGroup
    {
        return new RouteGroup($this, ['namespace' => $namespace]);
    }

    public function domain(string $domain): RouteGroup
    {
        return new RouteGroup($this, ['domain' => $domain]);
    }

    // Group attribute management

    public function getCurrentGroupAttributes(): array
    {
        return $this->currentGroupAttributes;
    }

    public function setCurrentGroupAttributes(array $attributes): void
    {
        $this->currentGroupAttributes = $attributes;
    }

    // Route dispatching

    public function dispatch(Request $request): Response
    {
        $route = $this->routes->match($request);
        return $this->dispatcher->dispatch($route, $request);
    }

    // URL generation

    public function url(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);
        
        if (!$route) {
            throw new \InvalidArgumentException("Route {$name} not found");
        }

        return $this->buildUrl($route->getUri(), $parameters);
    }

    private function buildUrl(string $pattern, array $parameters): string
    {
        $url = $pattern;

        foreach ($parameters as $key => $value) {
            $url = str_replace(['{' . $key . '}', '{' . $key . '?}'], $value, $url);
        }

        // Remove optional parameters that weren't provided
        $url = preg_replace('/\{[^}]+\?\}/', '', $url);

        return $url;
    }

    // Getters

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}