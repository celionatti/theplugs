<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Plugs;
use Plugs\Container\Container;
use Plugs\LiveHTML\LiveHTMLHandler;
use Plugs\Services\ServiceProvider;
use Plugs\LiveHTML\ComponentManager;
use Plugs\LiveHTML\Middleware\LiveHTMLMiddleware;

class LiveHTMLServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register the LiveHTML handler as singleton
        $this->app->singleton(LiveHTMLHandler::class, function (Container $container) {
            return new LiveHTMLHandler(
                $container,
                $container->make('session'),
                $container->make('config')
            );
        });

        // Alias for easier access
        $this->app->alias('livehtml', LiveHTMLHandler::class);

        // Register component manager
        $this->app->singleton(ComponentManager::class, function (Container $container) {
            return new ComponentManager($container);
        });

        // Register middleware
        $this->app->bind(LiveHTMLMiddleware::class, function (Container $container) {
            return new LiveHTMLMiddleware($container->make(LiveHTMLHandler::class));
        });

        // Register view directives
        $this->registerViewDirectives();
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        // Add component paths to view
        $app = Plugs::getInstance();
        
        // Add LiveHTML views path
        $viewPaths = $app->config('view.paths', [$app->resourcePath('views')]);
        $viewPaths[] = __DIR__ . '/../../LiveHTML/views';
        
        // Register component namespace for includes
        if (method_exists($app, 'addViewPath')) {
            foreach ($viewPaths as $path) {
                $app->addViewPath($path);
            }
        }

        // Boot component discovery if enabled
        if ($app->config('livehtml.auto_discover', true)) {
            $this->discoverComponents();
        }
    }

    /**
     * Register view directives for LiveHTML components
     */
    protected function registerViewDirectives(): void
    {
        // Get the view compiler from container
        $compiler = $this->app->make('view.compiler', function() {
            return \Plugs\View\View::getCompiler();
        });

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
    }

    /**
     * Discover and register components automatically
     */
    protected function discoverComponents(): void
    {
        $app = Plugs::getInstance();
        $componentPath = $app->appPath('LiveHTML/Components');
        
        if (!is_dir($componentPath)) {
            return;
        }

        $componentManager = $this->app->make(ComponentManager::class);
        
        // Scan for component classes
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($componentPath)
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
    }

    /**
     * Get component name from class name
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
            LiveHTMLHandler::class,
            ComponentManager::class,
            LiveHTMLMiddleware::class,
            'livehtml'
        ];
    }
}