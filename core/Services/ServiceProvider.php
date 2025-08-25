<?php

declare(strict_types=1);

namespace Plugs\Services;

use Plugs\Container\Container;
use Plugs\Plugs;

abstract class ServiceProvider
{
    /**
     * The application instance.
     */
    protected Plugs $app;

    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * Indicates if loading of the provider is deferred.
     */
    protected bool $defer = false;

    /**
     * Create a new service provider instance.
     */
    public function __construct(Plugs $app)
    {
        $this->app = $app;
        $this->container = $app->container;
    }

    /**
     * Register any application services.
     */
    abstract public function register(): void;

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override in child classes if needed
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     */
    public function isDeferred(): bool
    {
        return $this->defer;
    }

    /**
     * Get a configuration value.
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->app->config($key, $default);
    }

    /**
     * Load configuration from a file.
     */
    protected function loadConfig(string $configFile, array $default = []): array
    {
        $fullPath = $this->app->configPath($configFile);
        
        if (file_exists($fullPath)) {
            $config = require $fullPath;
            return is_array($config) ? $config : $default;
        }
        
        return $default;
    }

    /**
     * Merge configuration arrays recursively.
     */
    protected function mergeConfig(array $original, array $merge): array
    {
        return array_merge_recursive($original, $merge);
    }

    /**
     * Register a singleton binding in the container.
     */
    protected function singleton(string $abstract, callable|string $concrete = null): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    /**
     * Register a binding in the container.
     */
    protected function bind(string $abstract, callable|string $concrete = null): void
    {
        $this->container->bind($abstract, $concrete);
    }

    /**
     * Register an alias in the container.
     */
    protected function alias(string $abstract, string $alias): void
    {
        $this->container->alias($abstract, $alias);
    }

    /**
     * Register an instance in the container.
     */
    protected function instance(string $abstract, mixed $instance): void
    {
        $this->container->instance($abstract, $instance);
    }
}