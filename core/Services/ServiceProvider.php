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
    protected Container $app;

    /**
     * Create a new service provider instance.
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
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
}