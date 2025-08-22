<?php

declare(strict_types=1);

namespace Plugs\LiveHTML;

use Plugs\Container\Container;
use Plugs\Exceptions\LiveHTML\LiveHTMLException;

class ComponentManager
{
    protected Container $container;
    protected array $registered = [];
    protected array $aliases = [];
    protected array $instances = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a component
     */
    public function register(string $name, string $class): void
    {
        if (!class_exists($class)) {
            throw new LiveHTMLException("Component class '{$class}' does not exist");
        }

        if (!is_subclass_of($class, Component::class)) {
            throw new LiveHTMLException("Component class '{$class}' must extend " . Component::class);
        }

        $this->registered[$name] = $class;
    }

    /**
     * Create component alias
     */
    public function alias(string $alias, string $name): void
    {
        if (!isset($this->registered[$name])) {
            throw new LiveHTMLException("Cannot create alias '{$alias}' for unregistered component '{$name}'");
        }

        $this->aliases[$alias] = $name;
    }

    /**
     * Create a new component instance
     */
    public function create(string $name, array $parameters = []): Component
    {
        $className = $this->resolve($name);
        
        if (!$className) {
            throw new LiveHTMLException("Component '{$name}' is not registered");
        }

        // Create component instance through container for dependency injection
        try {
            $component = $this->container->make($className, ['parameters' => $parameters]);
        } catch (\Exception $e) {
            // Fallback to direct instantiation if container resolution fails
            $component = new $className($parameters);
        }

        return $component;
    }

    /**
     * Get singleton component instance
     */
    public function singleton(string $name, array $parameters = []): Component
    {
        $key = $name . ':' . md5(serialize($parameters));
        
        if (!isset($this->instances[$key])) {
            $this->instances[$key] = $this->create($name, $parameters);
        }

        return $this->instances[$key];
    }

    /**
     * Resolve component name to class name
     */
    protected function resolve(string $name): ?string
    {
        // Check aliases first
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        // Check registered components
        if (isset($this->registered[$name])) {
            return $this->registered[$name];
        }

        // Try auto-discovery
        if ($this->canAutoDiscover()) {
            $className = $this->autoDiscover($name);
            if ($className) {
                $this->register($name, $className);
                return $className;
            }
        }

        return null;
    }

    /**
     * Auto-discover component class
     */
    protected function autoDiscover(string $name): ?string
    {
        // Convert kebab-case to PascalCase
        $className = str_replace('-', '', ucwords($name, '-'));
        
        // Try common namespaces
        $namespaces = [
            'App\\LiveHTML\\Components\\',
            'App\\Components\\',
            'Components\\',
        ];

        foreach ($namespaces as $namespace) {
            $fullClassName = $namespace . $className;
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, Component::class)) {
                return $fullClassName;
            }
        }

        return null;
    }

    /**
     * Check if auto-discovery is enabled
     */
    protected function canAutoDiscover(): bool
    {
        return true; // Could be configurable
    }

    /**
     * Get all registered components
     */
    public function getRegistered(): array
    {
        return $this->registered;
    }

    /**
     * Get all aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Check if component is registered
     */
    public function isRegistered(string $name): bool
    {
        return isset($this->registered[$name]) || isset($this->aliases[$name]);
    }

    /**
     * Unregister a component
     */
    public function unregister(string $name): void
    {
        unset($this->registered[$name]);
        
        // Remove aliases pointing to this component
        $this->aliases = array_filter($this->aliases, function ($aliasTarget) use ($name) {
            return $aliasTarget !== $name;
        });
        
        // Clear instances
        $this->instances = array_filter($this->instances, function ($key) use ($name) {
            return !str_starts_with($key, $name . ':');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Clear all registrations
     */
    public function clear(): void
    {
        $this->registered = [];
        $this->aliases = [];
        $this->instances = [];
    }

    /**
     * Batch register components
     */
    public function registerBatch(array $components): void
    {
        foreach ($components as $name => $class) {
            $this->register($name, $class);
        }
    }

    /**
     * Register components from directory
     */
    public function registerFromDirectory(string $directory, string $namespace = 'App\\LiveHTML\\Components\\'): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace([$directory . '/', '.php'], '', $file->getPathname());
                $className = $namespace . str_replace('/', '\\', $relativePath);
                
                if (class_exists($className) && is_subclass_of($className, Component::class)) {
                    $componentName = $this->getComponentNameFromClass($className);
                    $this->register($componentName, $className);
                }
            }
        }
    }

    /**
     * Get component name from class name
     */
    protected function getComponentNameFromClass(string $className): string
    {
        $baseName = class_basename($className);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $baseName));
    }

    /**
     * Get component reflection information
     */
    public function getComponentInfo(string $name): array
    {
        $className = $this->resolve($name);
        
        if (!$className) {
            throw new LiveHTMLException("Component '{$name}' is not registered");
        }

        $reflection = new \ReflectionClass($className);
        $properties = [];
        $methods = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $properties[] = [
                    'name' => $property->getName(),
                    'type' => $property->getType()?->getName(),
                    'hasDefaultValue' => $property->hasDefaultValue(),
                    'defaultValue' => $property->hasDefaultValue() ? $property->getDefaultValue() : null,
                ];
            }
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isStatic() && !$method->isConstructor() && !$method->isDestructor()) {
                $methods[] = [
                    'name' => $method->getName(),
                    'parameters' => array_map(function (\ReflectionParameter $param) {
                        return [
                            'name' => $param->getName(),
                            'type' => $param->getType()?->getName(),
                            'hasDefaultValue' => $param->isDefaultValueAvailable(),
                            'defaultValue' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                        ];
                    }, $method->getParameters()),
                ];
            }
        }

        return [
            'name' => $name,
            'class' => $className,
            'properties' => $properties,
            'methods' => $methods,
        ];
    }
}