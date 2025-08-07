<?php

declare(strict_types=1);

namespace Plugs\Controller;

use Plugs\Http\Request\Request;
use Plugs\Controller\Controller;
use Plugs\Http\Response\Response;
use Plugs\Middleware\MiddlewareRegistry;
use Plugs\Exceptions\Controller\ControllerException;

class ControllerDispatcher
{
    private array $controllerCache = [];

    /**
     * Dispatch a controller action with middleware support
     */
    public function dispatch(string $controller, string $method, array $parameters = [], ?Request $request = null): Response
    {
        $controllerInstance = $this->resolveController($controller, $request);

        // Execute before hooks
        $controllerInstance->executeBeforeHooks($method);

        $middleware = $controllerInstance->getMiddlewareForMethod($method);

        $pipeline = $this->createMiddlewarePipeline($middleware, function () use ($controllerInstance, $method, $parameters) {
            return $controllerInstance->callAction($method, $parameters);
        });

        $response = $pipeline($request ?? new Request());

        // Execute after hooks
        $controllerInstance->executeAfterHooks($method, $response);

        return $response;
    }

    /**
     * Resolve a controller instance
     */
    protected function resolveController(string $controller, ?Request $request = null): Controller
    {
        $cacheKey = $controller . '_' . spl_object_id($request ?? new Request());

        if (isset($this->controllerCache[$cacheKey])) {
            return $this->controllerCache[$cacheKey];
        }

        if (!class_exists($controller)) {
            throw new ControllerException("Controller class '{$controller}' not found");
        }

        if (!is_subclass_of($controller, Controller::class)) {
            throw new ControllerException("Controller '{$controller}' must extend " . Controller::class);
        }

        $instance = new $controller($request);
        $this->controllerCache[$cacheKey] = $instance;

        return $instance;
    }

    /**
     * Create middleware pipeline
     */
    protected function createMiddlewarePipeline(array $middleware, callable $core): callable
    {
        return array_reduce(
            array_reverse($middleware),
            function ($next, $middlewareData) {
                return function (Request $request) use ($next, $middlewareData) {
                    $middlewareInstance = MiddlewareRegistry::resolve($middlewareData['middleware']);
                    return $middlewareInstance->handle($request, $next);
                };
            },
            $core
        );
    }
}
