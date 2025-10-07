<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Debugger\PlugDebugger;
use Plugs\Services\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register debugger as singleton
        $this->app->container->singleton(PlugDebugger::class, function () {
            return PlugDebugger::getInstance();
        });
        
        // Create alias for easier access
        $this->app->container->alias('debugger', PlugDebugger::class);
    }
    
    /**
     * Boot services
     */
    public function boot(): void
    {
        $debugger = PlugDebugger::getInstance();
        
        // Enable/disable based on environment
        $env = $this->app->environment();
        $debugEnabled = $env !== 'production';
        
        // Allow override from config
        if ($this->app->config('app.debug') !== null) {
            $debugEnabled = (bool) $this->app->config('app.debug');
        }
        
        $debugger->setEnabled($debugEnabled);
        
        if ($debugEnabled) {
            $debugger->markPerformance('Application Booted');
        }
    }
}