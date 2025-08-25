<?php

declare(strict_types=1);

namespace Plugs\Http\Router;

use Closure;
use ReflectionMethod;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Routing\RouteDefinition;
use Plugs\Middleware\MiddlewarePipeline;

class dispatcher
{
    private ?object $container = null;

    public function __construct(?object $container = null)
    {
        $this->container = $container;
    }

    public function dispatch(RouteDefinition $route, Request $request): Response
    {
        // Set route parameters on request
        $request->setParameters($route->getParameters());

        // Create middleware pipeline
        $pipeline = new MiddlewarePipeline($route->getMiddleware());

        // Dispatch through middleware pipeline
        return $pipeline->handle($request, function (Request $req) use ($route) {
            return $this->callAction($route, $req);
        });
    }

    private function callAction(RouteDefinition $route, Request $request): Response
    {
        $action = $route->getAction();

        if ($action instanceof Closure) {
            return $this->callClosure($action, $request);
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            return $this->callController($controller, $method, $request, $route->getNamespace());
        }

        if (is_string($action)) {
            return $this->callControllerString($action, $request, $route->getNamespace());
        }

        throw new \InvalidArgumentException('Invalid route action');
    }

    private function callClosure(Closure $closure, Request $request): Response
    {
        $parameters = $this->resolveParameters($closure, $request);
        $result = $closure(...$parameters);

        return $this->createResponse($result);
    }

    private function callController(string $controller, string $method, Request $request, ?string $namespace = null): Response
    {
        $controllerClass = $namespace ? $namespace . '\\' . $controller : $controller;

        if (!class_exists($controllerClass)) {
            throw new \InvalidArgumentException("Controller {$controllerClass} not found");
        }

        $instance = $this->resolveController($controllerClass);

        if (!method_exists($instance, $method)) {
            throw new \InvalidArgumentException("Method {$method} not found in controller {$controllerClass}");
        }

        // Use ControllerDispatcher if it's a Controller subclass
        if (is_subclass_of($instance, \Plugs\Controller\Controller::class)) {
            $dispatcher = new \Plugs\Controller\ControllerDispatcher();
            return $dispatcher->dispatch($controllerClass, $method, $request->getParameters(), $request);
        }

        // Fallback for non-Controller classes
        $parameters = $this->resolveMethodParameters($controllerClass, $method, $request);
        $result = $instance->$method(...$parameters);

        return $this->createResponse($result);
    }

    private function callControllerString(string $action, Request $request, ?string $namespace = null): Response
    {
        if (!str_contains($action, '@')) {
            throw new \InvalidArgumentException('Controller string must contain @ separator');
        }

        [$controller, $method] = explode('@', $action, 2);
        return $this->callController($controller, $method, $request, $namespace);
    }

    private function resolveController(string $controller): object
    {
        if ($this->container && method_exists($this->container, 'make')) {
            return $this->container->make($controller);
        }

        return new $controller();
    }

    private function resolveParameters(Closure $closure, Request $request): array
    {
        $reflection = new \ReflectionFunction($closure);
        return $this->buildParameters($reflection->getParameters(), $request);
    }

    private function resolveMethodParameters(string $controller, string $method, Request $request): array
    {
        $reflection = new ReflectionMethod($controller, $method);
        return $this->buildParameters($reflection->getParameters(), $request);
    }

    private function buildParameters(array $parameters, Request $request): array
    {
        $resolved = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();

                if ($className === Request::class) {
                    $resolved[] = $request;
                    continue;
                }

                // Try to resolve from container
                if ($this->container && method_exists($this->container, 'make')) {
                    $resolved[] = $this->container->make($className);
                    continue;
                }

                // Try to create instance
                if (class_exists($className)) {
                    $resolved[] = new $className();
                    continue;
                }
            }

            // Try to get route parameter
            $paramName = $parameter->getName();
            if ($request->parameter($paramName) !== null) {
                $resolved[] = $request->parameter($paramName);
                continue;
            }

            // Use default value if available
            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \InvalidArgumentException("Cannot resolve parameter {$paramName}");
        }

        return $resolved;
    }

    private function createResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return new Response($result);
    }
}
