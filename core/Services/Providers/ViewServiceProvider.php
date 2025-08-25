<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Services\ServiceProvider;
use Plugs\View\View;
use Plugs\View\ViewFinder;
use Plugs\View\Compiler\ViewCompiler;
use Plugs\View\Engines\EngineResolver;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register view services.
     */
    public function register(): void
    {
        $this->registerViewFinder();
        $this->registerEngineResolver();
        $this->registerViewCompiler();
        $this->registerViewFactory();
        $this->registerViewBindings();
    }

    /**
     * Register the view finder implementation.
     */
    protected function registerViewFinder(): void
    {
        $this->singleton(ViewFinder::class, function () {
            return new ViewFinder($this->getViewPaths());
        });

        // Also bind as 'view.finder' for easier access
        $this->alias(ViewFinder::class, 'view.finder');
    }

    /**
     * Register the engine resolver implementation.
     */
    protected function registerEngineResolver(): void
    {
        $this->singleton(EngineResolver::class, function () {
            $resolver = new EngineResolver();
            
            // Configure engines with dependencies
            $this->configureEngines($resolver);
            
            return $resolver;
        });

        // Also bind as 'view.engine.resolver' for easier access
        $this->alias(EngineResolver::class, 'view.engine.resolver');
    }

    /**
     * Register the view compiler implementation.
     */
    protected function registerViewCompiler(): void
    {
        $this->singleton(ViewCompiler::class, function () {
            $compiler = new ViewCompiler();
            // Register built-in directives
            $this->registerDefaultDirectives($compiler);
            return $compiler;
        });

        // Also bind as 'view.compiler' for easier access
        $this->alias(ViewCompiler::class, 'view.compiler');
    }

    /**
     * Register the view factory implementation.
     */
    protected function registerViewFactory(): void
    {
        $this->singleton('view', function () {
            // Set up static dependencies on View class
            View::setFinder($this->container->get(ViewFinder::class));
            View::setEngineResolver($this->container->get(EngineResolver::class));
            View::setCompiler($this->container->get(ViewCompiler::class));
            
            // Return a factory function that creates view instances
            return new class($this->container) {
                private $container;
                
                public function __construct($container)
                {
                    $this->container = $container;
                }
                
                public function make(string $template, array $data = []): View
                {
                    return View::make($template, $data);
                }
                
                public function share(string|array $key, mixed $value = null): void
                {
                    View::share($key, $value);
                }
                
                public function exists(string $template): bool
                {
                    return View::exists($template);
                }
                
                public function __call(string $method, array $parameters)
                {
                    return View::$method(...$parameters);
                }
            };
        });

        // Alias for convenience
        $this->alias('view', View::class);
    }

    /**
     * Register additional view bindings and aliases.
     */
    protected function registerViewBindings(): void
    {
        // These are already registered above, but keep for backward compatibility
        $this->bind('view.finder', function () {
            return $this->container->get(ViewFinder::class);
        });

        $this->singleton('view.engine.resolver', function () {
            return $this->container->get(EngineResolver::class);
        });
    }

    /**
     * Configure rendering engines.
     */
    protected function configureEngines(EngineResolver $resolver): void
    {
        // Configure PlugsEngine with compiler
        $resolver->register('plugs', function () {
            $compiler = $this->container->get(ViewCompiler::class);
            $engine = new \Plugs\View\Engines\PlugsEngine($compiler);
            
            // Configure caching
            $cacheEnabled = $this->config('view.cache', $this->app->environment() === 'production');
            if ($cacheEnabled) {
                $cachePath = $this->config(
                    'view.compiled', 
                    $this->app->storagePath('framework/views')
                );
                $this->ensureDirectoryExists($cachePath);
                $engine->setCaching(true, $cachePath);
            }
            
            return $engine;
        });

        // Other engines are already registered by default in EngineResolver
    }

    /**
     * Register default compiler directives.
     */
    protected function registerDefaultDirectives(ViewCompiler $compiler): void
    {
        // Basic debugging directives
        $compiler->directive('dump', fn($exp) => "<?php dump($exp); ?>");
        $compiler->directive('dd', fn($exp) => "<?php dd($exp); ?>");
        
        // JSON encoding directive
        $compiler->directive('json', fn($exp) => "<?php echo json_encode($exp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>");
        
        // Configuration directive
        $compiler->directive('config', fn($exp) => "<?php echo e(config($exp)); ?>");
        
        // URL generation directives
        $compiler->directive('url', fn($exp) => "<?php echo url($exp); ?>");
        $compiler->directive('asset', fn($exp) => "<?php echo asset($exp); ?>");
        
        // Session directives
        $compiler->directive('session', fn($exp) => "<?php echo session($exp); ?>");
        
        // Environment directive
        $compiler->directive('env', fn($exp) => "<?php echo env($exp); ?>");
        
        // CSRF token directive
        $compiler->directive('csrf', fn($exp) => "<?php echo csrf_token(); ?>");
        
        // Conditional directives
        $compiler->directive('if', fn($exp) => "<?php if($exp): ?>");
        $compiler->directive('elseif', fn($exp) => "<?php elseif($exp): ?>");
        $compiler->directive('else', fn($exp) => "<?php else: ?>");
        $compiler->directive('endif', fn($exp) => "<?php endif; ?>");
        
        // Loop directives
        $compiler->directive('foreach', fn($exp) => "<?php foreach($exp): ?>");
        $compiler->directive('endforeach', fn($exp) => "<?php endforeach; ?>");
        $compiler->directive('for', fn($exp) => "<?php for($exp): ?>");
        $compiler->directive('endfor', fn($exp) => "<?php endfor; ?>");
        $compiler->directive('while', fn($exp) => "<?php while($exp): ?>");
        $compiler->directive('endwhile', fn($exp) => "<?php endwhile; ?>");
        
        // Include directives
        $compiler->directive('include', function ($exp) {
            return "<?php echo \$__view->include($exp); ?>";
        });
        
        // Yield and section directives for layout system
        $compiler->directive('yield', fn($exp) => "<?php echo \$__view->yieldContent($exp); ?>");
        $compiler->directive('section', fn($exp) => "<?php \$__view->startSection($exp); ?>");
        $compiler->directive('endsection', fn($exp) => "<?php \$__view->endSection(); ?>");
        $compiler->directive('show', fn($exp) => "<?php echo \$__view->endSectionAndShow(); ?>");
        $compiler->directive('extends', fn($exp) => "<?php \$__view->extend($exp); ?>");
        
        // Authentication directives
        $compiler->directive('auth', fn($exp) => "<?php if(\$__view->isAuthenticated()): ?>");
        $compiler->directive('endauth', fn($exp) => "<?php endif; ?>");
        $compiler->directive('guest', fn($exp) => "<?php if(!\$__view->isAuthenticated()): ?>");
        $compiler->directive('endguest', fn($exp) => "<?php endif; ?>");

        // Stack directives
        $compiler->directive('push', fn($exp) => "<?php \$__view->startPush($exp); ?>");
        $compiler->directive('endpush', fn($exp) => "<?php \$__view->endPush(); ?>");
        $compiler->directive('stack', fn($exp) => "<?php echo \$__view->stack($exp); ?>");

        // Component directives
        $compiler->directive('component', fn($exp) => "<?php \$__view->component($exp); ?>");
        $compiler->directive('endcomponent', fn($exp) => "<?php \$__view->endComponent(); ?>");
        $compiler->directive('slot', fn($exp) => "<?php echo \$__view->slot($exp); ?>");

        // Fragment directives
        $compiler->directive('fragment', fn($exp) => "<?php \$__view->startFragment($exp); ?>");
        $compiler->directive('endfragment', fn($exp) => "<?php \$__view->endFragment(); ?>");

        // Form directives
        $compiler->directive('form', function ($exp) {
            $parts = explode(',', $exp, 2);
            $action = trim($parts[0]);
            $method = isset($parts[1]) ? trim($parts[1]) : "'POST'";
            return "<?php echo \$__view->formOpen($action, $method); ?>";
        });
        $compiler->directive('endform', fn($exp) => "<?php echo \$__view->formClose(); ?>");

        // Once directive
        $compiler->directive('once', fn($exp) => "<?php if(\$__view->once($exp)): ?>");
        $compiler->directive('endonce', fn($exp) => "<?php endif; ?>");
    }

    /**
     * Bootstrap view services.
     */
    public function boot(): void
    {
        $this->configureViewPaths();
        $this->configureViewCaching();
        $this->shareGlobalVariables();
        $this->registerConfigurableDirectives();
        $this->registerViewNamespaces();
    }

    /**
     * Configure view paths in the View class.
     */
    protected function configureViewPaths(): void
    {
        $finder = $this->container->get(ViewFinder::class);
        
        // Add additional paths from config
        $additionalPaths = $this->config('view.additional_paths', []);
        if (is_array($additionalPaths)) {
            foreach ($additionalPaths as $path) {
                $finder->addPath($path);
            }
        }
        
        // Set asset path
        $assetPath = $this->getAssetPath();
        View::setAssetPath($assetPath);
        
        // Configure asset versioning
        if ($version = $this->config('view.asset_version')) {
            View::setAssetVersion($version);
        }
        
        if ($this->config('view.asset_auto_version', false)) {
            View::enableAutoVersioning(true);
        }
    }

    /**
     * Get view paths from configuration.
     */
    protected function getViewPaths(): array
    {
        $paths = [];
        
        // Add default view path
        $paths[] = $this->app->resourcePath('views');
        
        // Add additional paths from config
        $configPaths = $this->config('view.paths', []);
        if (is_array($configPaths)) {
            $paths = array_merge($paths, $configPaths);
        }
        
        return array_unique($paths);
    }

    /**
     * Get asset path from configuration.
     */
    protected function getAssetPath(): string
    {
        $configAssetPath = $this->config('view.asset_path');
        
        if ($configAssetPath) {
            return $configAssetPath;
        }
        
        // Default asset path
        return rtrim($this->app->urlPath(), '/') . '/assets';
    }

    /**
     * Configure view caching based on environment.
     */
    protected function configureViewCaching(): void
    {
        // Get cache setting from config
        $cacheEnabled = $this->config('view.cache', $this->app->environment() === 'production');
        
        if ($cacheEnabled) {
            $cachePath = $this->config(
                'view.compiled', 
                $this->app->storagePath('framework/views')
            );
            
            // Ensure cache directory exists
            $this->ensureDirectoryExists($cachePath);
            
            View::setCaching(true, $cachePath);
        }
    }

    /**
     * Share variables with all views.
     */
    protected function shareGlobalVariables(): void
    {
        // Share app instance
        View::share('app', $this->app);
        
        // Share configuration values
        View::share('appName', $this->config('app.name', 'Plugs Framework'));
        View::share('appEnv', $this->config('app.env', 'production'));
        View::share('appDebug', $this->config('app.debug', false));
        View::share('appUrl', $this->config('app.url', '/'));
        View::share('appVersion', $this->config('app.version', '1.0.0'));
        
        // Share request instance if available
        if ($this->container->has('request')) {
            View::share('request', $this->container->get('request'));
        }
        
        // Share session instance if available
        if ($this->container->has('session')) {
            View::share('session', $this->container->get('session'));
        }
        
        // Share auth user if available
        if ($this->container->has('auth')) {
            $auth = $this->container->get('auth');
            View::share('user', $auth->user() ?? null);
        }
        
        // Share a config helper
        View::share('config', $this->createConfigHelper());
        
        // Share global view data from config
        $globalData = $this->config('view.global_data', []);
        if (is_array($globalData)) {
            View::share($globalData);
        }
    }

    /**
     * Create a config helper for views.
     */
    protected function createConfigHelper(): object
    {
        return new class($this->app) {
            private $app;
            
            public function __construct($app)
            {
                $this->app = $app;
            }
            
            public function get(string $key, mixed $default = null): mixed
            {
                return $this->app->config($key, $default);
            }
            
            public function has(string $key): bool
            {
                try {
                    return $this->app->config($key) !== null;
                } catch (\Exception $e) {
                    return false;
                }
            }
        };
    }

    /**
     * Register additional view directives based on configuration.
     */
    protected function registerConfigurableDirectives(): void
    {
        $customDirectives = $this->config('view.directives', []);
        
        if (is_array($customDirectives) && !empty($customDirectives)) {
            $compiler = $this->container->get(ViewCompiler::class);
            
            foreach ($customDirectives as $name => $callback) {
                if (is_string($name) && is_callable($callback)) {
                    $compiler->directive($name, $callback);
                }
            }
        }
    }

    /**
     * Register view namespaces from configuration.
     */
    protected function registerViewNamespaces(): void
    {
        $namespaces = $this->config('view.namespaces', []);
        
        if (is_array($namespaces) && !empty($namespaces)) {
            foreach ($namespaces as $namespace => $paths) {
                View::addNamespace($namespace, $paths);
            }
        }
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'view',
            'view.compiler',
            'view.finder',
            'view.engine.resolver',
            View::class,
            ViewCompiler::class,
            ViewFinder::class,
            EngineResolver::class,
        ];
    }
}