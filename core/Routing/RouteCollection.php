<?php

declare(strict_types=1);

namespace Plugs\Routing;

use Plugs\Http\Request\Request;
use Plugs\Exceptions\Routings\RouteNotFoundException;
use Plugs\Exceptions\Routings\MethodNotAllowedException;

class RouteCollection
{
    private array $routes = [];
    private array $namedRoutes = [];
    private ?RouteDefinition $fallbackRoute = null;

    public function add(RouteDefinition $route): void
    {
        $this->routes[] = $route;

        if ($route->getName()) {
            $this->namedRoutes[$route->getName()] = $route;
        }
    }

    public function setFallback(RouteDefinition $route): void
    {
        $this->fallbackRoute = $route;
    }

    public function match(Request $request): RouteDefinition
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            // Check if URI pattern matches but method might be wrong
            $tempRoute = clone $route;
            if ($tempRoute->matchesUri($request->getUri())) {
                $allowedMethods = array_merge($allowedMethods, $route->getMethods());
                
                if ($route->matches($request)) {
                    return $route;
                }
            }
        }

        // If we have allowed methods but current method not allowed
        if (!empty($allowedMethods)) {
            throw new MethodNotAllowedException(
                array_unique($allowedMethods),
                $request->getMethod(),
                $request->getUri()
            );
        }

        // Try fallback route
        if ($this->fallbackRoute) {
            return $this->fallbackRoute;
        }

        throw new RouteNotFoundException($request->getMethod(), $request->getUri());
    }

    public function getByName(string $name): ?RouteDefinition
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function getAllRoutes(): array
    {
        return $this->routes;
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }
}