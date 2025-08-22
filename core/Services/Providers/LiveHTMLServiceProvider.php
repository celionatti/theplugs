<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Plugs;
use Plugs\Container\Container;
use Plugs\LiveHTML\LiveHTMLHandler;
use Plugs\Services\ServiceProvider;
use Plugs\LiveHTML\ComponentManager;
use Plugs\LiveHTML\Middleware\LiveHTMLMiddleware;
use Plugs\View\Compiler\ViewCompiler;

class LiveHTMLServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerLiveHTMLHandler();
        $this->registerComponentManager();
        $this->registerLiveHTMLMiddleware();
    }

    /**
     * Register the LiveHTML handler implementation.
     */
    protected function registerLiveHTMLHandler(): void
    {
        $this->app->singleton('livehtml', function (Container $app) {
            return new LiveHTMLHandler(
                $app,
                $app->get('session'),
                $this->loadLiveHTMLConfig($app->get(Plugs::class))
            );
        });

        // Alias for convenience
        $this->app->alias('livehtml', LiveHTMLHandler::class);
    }

    /**
     * Register component manager.
     */
    protected function registerComponentManager(): void
    {
        $this->app->singleton(ComponentManager::class, function (Container $app) {
            return new ComponentManager($app);
        });
    }

    /**
     * Register LiveHTML middleware.
     */
    protected function registerLiveHTMLMiddleware(): void
    {
        $this->app->singleton(LiveHTMLMiddleware::class, function (Container $app) {
            return new LiveHTMLMiddleware($app->get('livehtml'));
        });
    }

    /**
     * Load LiveHTML configuration from file.
     */
    protected function loadLiveHTMLConfig(Plugs $plugs): array
    {
        $configFile = $plugs->basePath('config/livehtml.php');
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            return is_array($config) ? $config : [];
        }
        
        return $this->getDefaultConfig();
    }

    /**
     * Get default LiveHTML configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'auto_discover' => true,
            'component_path' => 'app/LiveHTML/Components',
            'view_path' => 'resources/views/livehtml',
            'asset_path' => 'assets/livehtml',
            'cache_enabled' => true,
            'cache_path' => 'storage/cache/livehtml',
            'debug' => false,
            'csrf_protection' => true,
            'session_key' => 'livehtml_components',
            'websocket' => [
                'enabled' => false,
                'host' => 'localhost',
                'port' => 6001,
            ],
            'update_uri' => '/livehtml/update',
            'asset_url' => '/assets/livehtml',
        ];
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        $this->configureViewPaths();
        $this->registerViewDirectives();
        $this->discoverComponents();
    }

    /**
     * Configure LiveHTML view paths.
     */
    protected function configureViewPaths(): void
    {
        if (!$this->app->has(Plugs::class)) {
            return;
        }

        $plugs = $this->app->get(Plugs::class);
        $config = $this->loadLiveHTMLConfig($plugs);
        
        // Add LiveHTML views path
        $livehtmlViewPath = $plugs->basePath($config['view_path'] ?? 'resources/views/livehtml');
        
        // Also add the internal views path
        $internalViewPath = __DIR__ . '/../../LiveHTML/views';
        
        // Add paths to view if the method exists
        if (method_exists($plugs, 'addViewPath')) {
            $plugs->addViewPath($livehtmlViewPath);
            if (is_dir($internalViewPath)) {
                $plugs->addViewPath($internalViewPath);
            }
        }
    }

    /**
     * Register view directives for LiveHTML components.
     */
    protected function registerViewDirectives(): void
    {
        if (!$this->app->has(ViewCompiler::class)) {
            return;
        }

        try {
            $compiler = $this->app->get(ViewCompiler::class);
            
            // @livehtml directive for rendering components
            $compiler->directive('livehtml', function ($expression) {
                return "<?php echo app('livehtml')->render($expression); ?>";
            });

            // @livehtmlScripts directive for including JavaScript
            $compiler->directive('livehtmlScripts', function () {
                return "<?php echo app('livehtml')->scripts(); ?>";
            });

            // @livehtmlStyles directive for including CSS
            $compiler->directive('livehtmlStyles', function () {
                return "<?php echo app('livehtml')->styles(); ?>";
            });

            // @this directive for referencing component in JavaScript
            $compiler->directive('this', function ($expression) {
                return "<?php echo \$__livehtml_component->jsReference($expression); ?>";
            });

            // @entangle directive for two-way data binding
            $compiler->directive('entangle', function ($expression) {
                return "<?php echo \$__livehtml_component->entangle($expression); ?>";
            });

        } catch (\Exception $e) {
            // Silently fail if directives can't be registered
        }
    }

    /**
     * Discover and register components automatically.
     */
    protected function discoverComponents(): void
    {
        if (!$this->app->has(Plugs::class) || !$this->app->has(ComponentManager::class)) {
            return;
        }

        $plugs = $this->app->get(Plugs::class);
        $config = $this->loadLiveHTMLConfig($plugs);
        
        // Skip if auto discovery is disabled
        if (!($config['auto_discover'] ?? true)) {
            return;
        }

        $componentPath = $plugs->basePath($config['component_path'] ?? 'app/LiveHTML/Components');
        
        if (!is_dir($componentPath)) {
            return;
        }

        try {
            $componentManager = $this->app->get(ComponentManager::class);
            
            // Scan for component classes
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($componentPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace([$componentPath . '/', '.php'], '', $file->getPathname());
                    $className = 'App\\LiveHTML\\Components\\' . str_replace('/', '\\', $relativePath);
                    
                    if (class_exists($className)) {
                        $componentName = $this->getComponentName($className);
                        $componentManager->register($componentName, $className);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if component discovery fails
        }
    }

    /**
     * Get component name from class name.
     */
    protected function getComponentName(string $className): string
    {
        $baseName = class_basename($className);
        
        // Convert PascalCase to kebab-case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $baseName));
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'livehtml',
            LiveHTMLHandler::class,
            ComponentManager::class,
            LiveHTMLMiddleware::class,
        ];
    }
}