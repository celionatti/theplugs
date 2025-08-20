<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Plugs;
use Plugs\View\View;
use Plugs\Config;
use Plugs\Container\Container;
use Plugs\Services\ServiceProvider;
use Plugs\View\Compiler\ViewCompiler;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register view services.
     */
    public function register(): void
    {
        $this->registerViewCompiler();
        $this->registerViewFactory();
        $this->registerViewBindings();
    }

    /**
     * Register the view compiler implementation.
     */
    protected function registerViewCompiler(): void
    {
        $this->app->singleton(ViewCompiler::class, function (Container $app) {
            $compiler = new ViewCompiler();
            // Register built-in directives
            $this->registerDefaultDirectives($compiler);
            return $compiler;
        });
    }

    /**
     * Register the view factory implementation.
     */
    protected function registerViewFactory(): void
    {
        $this->app->bind('view', function (Container $app) {
            return new View(''); // Template will be set when actually used
        });
    }

    /**
     * Register additional view bindings and aliases.
     */
    protected function registerViewBindings(): void
    {
        // Alias for convenience
        $this->app->alias('view', View::class);
        // Share the container instance with views
        View::share('app', $this->app);
    }

    /**
     * Register default compiler directives.
     */
    protected function registerDefaultDirectives(ViewCompiler $compiler): void
    {
        $compiler->directive('dump', fn($exp) => "<?php dump($exp); ?>");
        $compiler->directive('dd', fn($exp) => "<?php dd($exp); ?>");
        $compiler->directive('json', fn($exp) => "<?php echo json_encode($exp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>");
    }

    /**
     * Bootstrap view services.
     */
    public function boot(): void
    {
        $this->configureViewPaths();
        $this->configureViewCaching();
        $this->shareGlobalVariables();
    }

    /**
     * Configure view paths.
     */
    protected function configureViewPaths(): void
    {
        $app = $this->app->get(Plugs::class);
        
        // Add default view path
        View::addPath($app->basePath('resources/views'));
        
        // Set asset path
        $assetPath = rtrim($app->urlPath(), '/') . '/assets';
        View::setAssetPath($assetPath);

        // Add additional paths from config if available
        try {
            // Use the Config class directly instead of array access
            $viewPaths = Config::get('view.paths', []);
            
            if (is_array($viewPaths)) {
                foreach ($viewPaths as $path) {
                    View::addPath($path);
                }
            }
        } catch (\Exception $e) {
            // Fallback to legacy config access if Config class fails
            if ($this->app->has('config')) {
                $config = $this->app->get('config');
                
                // Check if it's the old array format
                if (is_array($config) && isset($config['view']['paths'])) {
                    foreach ($config['view']['paths'] as $path) {
                        View::addPath($path);
                    }
                }
                // Check if it's the new config wrapper object
                elseif (is_object($config) && method_exists($config, 'get')) {
                    $viewPaths = $config->get('view.paths', []);
                    if (is_array($viewPaths)) {
                        foreach ($viewPaths as $path) {
                            View::addPath($path);
                        }
                    }
                }
            }
        }
    }

    /**
     * Configure view caching based on environment.
     */
    protected function configureViewCaching(): void
    {
        $app = $this->app->get(Plugs::class);
        
        // Get cache setting from config, defaulting to environment-based logic
        $cacheEnabled = Config::get('view.cache', $app->environment() === 'production');
        
        if ($cacheEnabled) {
            $cachePath = Config::get('view.compiled', $app->basePath('storage/cache/views'));
            View::setCaching(true, $cachePath);
        }
    }

    /**
     * Share variables with all views.
     */
    protected function shareGlobalVariables(): void
    {
        // Share app instance
        View::share('app', $this->app->get(Plugs::class));
        
        // Share configuration values that might be useful in views
        try {
            View::share('appName', Config::get('app.name', 'Plugs Framework'));
            View::share('appEnv', Config::get('app.env', 'production'));
            View::share('appDebug', Config::get('app.debug', false));
            View::share('appUrl', Config::get('app.url', '/'));
            
            // Share the entire config for advanced usage (create a view-safe wrapper)
            View::share('config', new class {
                public function get(string $key, mixed $default = null): mixed {
                    return Config::get($key, $default);
                }
                
                public function has(string $key): bool {
                    return Config::has($key);
                }
            });
            
        } catch (\Exception $e) {
            // Fallback: share basic app information if Config fails
            $app = $this->app->get(Plugs::class);
            View::share('appName', 'Plugs Framework');
            View::share('appEnv', $app->environment());
            View::share('appDebug', $app->environment() !== 'production');
        }
    }

    /**
     * Register additional view directives based on configuration.
     */
    protected function registerConfigurableDirectives(): void
    {
        try {
            $customDirectives = Config::get('view.directives', []);
            
            if (is_array($customDirectives) && $this->app->has(ViewCompiler::class)) {
                $compiler = $this->app->get(ViewCompiler::class);
                
                foreach ($customDirectives as $name => $callback) {
                    if (is_callable($callback)) {
                        $compiler->directive($name, $callback);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if custom directives can't be loaded
        }
    }
}