<?php

declare(strict_types=1);

namespace Plugs\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;
use Plugs\Container\ContainerInterface;
use Plugs\Exceptions\Handler\ExceptionHandler;

class Container implements ContainerInterface
{
    /**
     * The container's bindings.
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     */
    protected array $instances = [];

    /**
     * The registered type aliases.
     */
    protected array $aliases = [];

    /**
     * The container's singleton instance.
     */
    protected static ?Container $instance = null;

    /**
     * Set the globally available instance of the container.
     */
    public static function setInstance(?Container $container = null): ?Container
    {
        return static::$instance = $container;
    }

    /**
     * Get the globally available instance of the container.
     */
    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->removeAbstractAlias($abstract);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Alias a type to a different name.
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new Exception("$abstract is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            isset($this->aliases[$abstract]);
    }

    /**
     * Get an instance from the container.
     * This is an alias for the make() method.
     */
    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    /**
     * Resolve the given type from the container.
     */
    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        try {
            $abstract = $this->getAlias($abstract);

            // If an instance already exists, return it
            if (isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }

            $concrete = $this->getConcrete($abstract);

            // If we're building a concrete class, we can just build it
            if ($this->isBuildable($concrete, $abstract)) {
                $object = $this->build($concrete, $parameters);
            } else {
                $object = $this->make($concrete, $parameters);
            }

            // If the binding is shared, store the instance
            if ($this->isShared($abstract)) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        } catch (Exception $e) {
            // If we have an exception handler registered, use it
            if (isset($this->instances[ExceptionHandler::class])) {
                return $this->instances[ExceptionHandler::class]->handle($e);
            }

            throw $e;
        }
    }

    /**
     * Get the concrete type for a given abstract.
     */
    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Instantiate a concrete instance of the given type.
     */
    public function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class $concrete is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve all dependencies for the given parameters.
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->name, $parameters)) {
                $results[] = $parameters[$dependency->name];
                continue;
            }

            $result = is_null($dependency->getType())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Resolve a class-based dependency from the container.
     */
    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        try {
            return $this->make($parameter->getType()->getName());
        } catch (Exception $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Resolve a primitive dependency.
     */
    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception("Unable to resolve primitive [{$parameter->name}].");
    }

    /**
     * Get the alias for an abstract if available.
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * Determine if the given concrete is buildable.
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Determine if a given type is shared.
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Get the Closure to be used when building a type.
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function (Container $container, array $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete, $parameters);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     */
    protected function removeAbstractAlias(string $searched): void
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->aliases as $alias => $abstract) {
            if ($abstract == $searched) {
                unset($this->aliases[$alias]);
            }
        }
    }
}
