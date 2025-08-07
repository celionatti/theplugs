<?php

declare(strict_types=1);

namespace Plugs\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Plugs\Container\ContainerInterface;
use Plugs\Exceptions\Handler\ExceptionHandler;
use ReflectionUnionType;
use ReflectionIntersectionType;

class Container implements ContainerInterface
{
    protected array $bindings = [];
    protected array $instances = [];
    protected array $aliases = [];
    protected static ?Container $instance = null;

    public static function setInstance(?Container $container = null): ?Container
    {
        return static::$instance = $container;
    }

    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete instanceof Closure ? $concrete : $this->getClosure($abstract, $concrete ?? $abstract),
            'shared' => $shared
        ];
        
        $this->removeAlias($abstract);
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->removeAlias($abstract);
        $this->instances[$abstract] = $instance;
        return $instance;
    }

    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new Exception("[$abstract] cannot be aliased to itself.");
        }
        
        $this->aliases[$alias] = $abstract;
    }

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

    public function get(string $abstract): mixed
    {
        return $this->make($abstract);
    }

    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        try {
            $abstract = $this->getAlias($abstract);

            if (isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }

            $concrete = $this->getConcrete($abstract);
            $object = $this->isBuildable($concrete, $abstract)
                ? $this->build($concrete, $parameters)
                : $this->make($concrete, $parameters);

            if ($this->isShared($abstract)) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        } catch (Exception $e) {
            if (isset($this->instances[ExceptionHandler::class])) {
                return $this->instances[ExceptionHandler::class]->handle($e);
            }
            throw $e;
        }
    }

    protected function getConcrete(string $abstract): mixed
    {
        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

    public function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        return $reflector->newInstanceArgs(
            $this->resolveDependencies($constructor->getParameters(), $parameters)
        );
    }

    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        return array_map(
            fn(ReflectionParameter $dependency) => $this->resolveDependency($dependency, $parameters),
            $dependencies
        );
    }

    protected function resolveDependency(ReflectionParameter $dependency, array $parameters): mixed
    {
        if (array_key_exists($dependency->name, $parameters)) {
            return $parameters[$dependency->name];
        }

        $type = $dependency->getType();

        if ($type === null) {
            return $this->resolvePrimitive($dependency);
        }

        return $this->resolveTypedDependency($dependency, $type);
    }

    protected function resolveTypedDependency(ReflectionParameter $dependency, \ReflectionType $type): mixed
    {
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->resolveClassDependency($dependency, $type);
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return $this->resolveComplexType($dependency, $type);
        }

        return $this->resolvePrimitive($dependency);
    }

    protected function resolveClassDependency(ReflectionParameter $dependency, ReflectionNamedType $type): mixed
    {
        try {
            return $this->make($type->getName());
        } catch (Exception $e) {
            if ($dependency->isDefaultValueAvailable()) {
                return $dependency->getDefaultValue();
            }
            if ($dependency->allowsNull()) {
                return null;
            }
            throw new Exception("Unable to resolve dependency [{$type->getName()}].");
        }
    }

    protected function resolveComplexType(ReflectionParameter $dependency, ReflectionUnionType|ReflectionIntersectionType $type): mixed
    {
        foreach ($type->getTypes() as $subType) {
            if ($subType instanceof ReflectionNamedType && !$subType->isBuiltin()) {
                try {
                    return $this->make($subType->getName());
                } catch (Exception) {
                    continue;
                }
            }
        }

        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }
        
        throw new Exception("Unable to resolve complex type dependency [{$dependency->getName()}].");
    }

    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception("Unable to resolve primitive dependency [{$parameter->getName()}].");
    }

    public function getAlias(string $abstract): string
    {
        while (isset($this->aliases[$abstract])) {
            $abstract = $this->aliases[$abstract];
        }
        return $abstract;
    }

    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || 
              ($this->bindings[$abstract]['shared'] ?? false);
    }

    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return fn(Container $container, array $parameters = []) => $abstract === $concrete
            ? $container->build($concrete, $parameters)
            : $container->resolve($concrete, $parameters);
    }

    protected function removeAlias(string $abstract): void
    {
        unset($this->aliases[$abstract]);
    }
}