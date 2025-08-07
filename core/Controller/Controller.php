<?php

declare(strict_types=1);

namespace Plugs\Controller;

use Exception;
use ReflectionMethod;
use ReflectionNamedType;
use Plugs\Container\Container;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Middleware\MiddlewareStack;
use Plugs\Exceptions\Controller\MethodNotFoundException;
use Plugs\Exceptions\Controller\DependencyResolutionException;

abstract class Controller
{
    use ControllerHelpers;

    protected Request $request;
    protected MiddlewareStack $middlewareStack;
    protected array $beforeHooks = [];
    protected array $afterHooks = [];

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? new Request();
        $this->middlewareStack = new MiddlewareStack();

        // Call child constructor logic
        $this->initialize();
    }

    /**
     * Override this method in child controllers for custom initialization
     */
    protected function initialize(): void
    {
        // Default implementation does nothing
    }

    /**
     * Register middleware for the entire controller or specific methods
     */
    protected function middleware(string $middleware, array $options = []): static
    {
        $only = $options['only'] ?? [];
        $except = $options['except'] ?? [];

        $this->middlewareStack->add($middleware, [
            'only' => is_string($only) ? [$only] : $only,
            'except' => is_string($except) ? [$except] : $except,
            'parameters' => $options['parameters'] ?? []
        ]);

        return $this;
    }

    /**
     * Add a before hook
     */
    protected function before(callable $hook, array $methods = []): static
    {
        $this->beforeHooks[] = ['callback' => $hook, 'methods' => $methods];
        return $this;
    }

    /**
     * Add an after hook
     */
    protected function after(callable $hook, array $methods = []): static
    {
        $this->afterHooks[] = ['callback' => $hook, 'methods' => $methods];
        return $this;
    }

    /**
     * Get middleware that should run for the given method
     */
    public function getMiddlewareForMethod(string $method): array
    {
        $applicable = [];

        foreach ($this->middlewareStack->getMiddleware() as $middleware) {
            $params = $this->middlewareStack->getParameters($middleware);
            $only = $params['only'] ?? [];
            $except = $params['except'] ?? [];

            if (!empty($only) && !in_array($method, $only)) {
                continue;
            }

            if (!empty($except) && in_array($method, $except)) {
                continue;
            }

            $applicable[] = ['middleware' => $middleware, 'parameters' => $params['parameters'] ?? []];
        }

        return $applicable;
    }

    /**
     * Execute before hooks for the given method
     */
    public function executeBeforeHooks(string $method): void
    {
        foreach ($this->beforeHooks as $hook) {
            $methods = $hook['methods'];
            if (empty($methods) || in_array($method, $methods)) {
                call_user_func($hook['callback'], $this->request);
            }
        }
    }

    /**
     * Execute after hooks for the given method
     */
    public function executeAfterHooks(string $method, Response $response): void
    {
        foreach ($this->afterHooks as $hook) {
            $methods = $hook['methods'];
            if (empty($methods) || in_array($method, $methods)) {
                call_user_func($hook['callback'], $this->request, $response);
            }
        }
    }

    /**
     * Handle method calls with dependency injection
     */
    public function callAction(string $method, array $parameters = []): Response
    {
        if (!method_exists($this, $method)) {
            throw new MethodNotFoundException(static::class, $method);
        }

        $this->executeBeforeHooks($method);

        $reflection = new ReflectionMethod($this, $method);
        $dependencies = $this->resolveDependencies($reflection, $parameters);

        $result = $reflection->invokeArgs($this, $dependencies);
        $response = $this->formatResponse($result);

        $this->executeAfterHooks($method, $response);

        return $response;
    }

    /**
     * Resolve method dependencies
     */
    protected function resolveDependencies(ReflectionMethod $method, array $parameters = []): array
    {
        $dependencies = [];
        $params = $method->getParameters();
        $container = Container::getInstance();

        foreach ($params as $param) {
            $type = $param->getType();
            $paramName = $param->getName();

            // Check if parameter exists in route parameters first
            if (array_key_exists($paramName, $parameters)) {
                $value = $parameters[$paramName];

                // If we have a type hint, cast the value
                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    $dependencies[] = $this->castToType($value, $type->getName());
                    continue;
                }

                // If no type hint or not built-in, use as-is
                $dependencies[] = $value;
                continue;
            }

            // Handle typed parameters
            if ($type instanceof ReflectionNamedType) {
                $typeName = $type->getName();

                // Special case for Request
                if ($typeName === Request::class) {
                    $dependencies[] = $this->request;
                    continue;
                }

                // Try container resolution
                if ($container->has($typeName)) {
                    $dependencies[] = $container->get($typeName);
                    continue;
                }

                // Try to instantiate if it's a class
                if (class_exists($typeName)) {
                    try {
                        $dependencies[] = $container->get($typeName);
                        continue;
                    } catch (Exception $e) {
                        // Fall through
                    }
                }
            }

            // Default value or null
            if ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $dependencies[] = null;
            } else {
                throw new DependencyResolutionException(
                    $param->getName(),
                    static::class . '::' . $method->getName() . '()'
                );
            }
        }

        return $dependencies;
    }

    private function castToType(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return (array) $value;
            default:
                return $value;
        }
    }

    /**
     * Format method response into a Response object
     */
    protected function formatResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return $this->json($result);
        }

        if (is_string($result) || is_numeric($result) || is_null($result)) {
            return $this->response((string) $result);
        }

        if (is_object($result)) {
            try {
                return $this->json((array) $result);
            } catch (Exception $e) {
                return $this->response(serialize($result));
            }
        }

        return $this->response('');
    }
}
