<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\View\View;
use Plugs\View\ViewFinder;
use Plugs\View\ViewFactory;
use Plugs\View\EngineResolver;
use Plugs\View\Engines\PhpEngine;
use Plugs\Services\ServiceProvider;
use Plugs\View\Compiler\PlugCompiler;
use Plugs\View\Engines\CompilerEngine;
use Plugs\View\ViewFactoryWithLayouts;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerViewFinder();
        $this->registerEngineResolver();
        $this->registerViewFactory();
    }

    protected function registerViewFinder(): void
    {
        $this->bind('view.finder', function () {
            $paths = $this->getViewPaths();
            return new ViewFinder($paths);
        });
    }

    protected function registerEngineResolver(): void
    {
        $this->bind('view.engine.resolver', function () {
            $resolver = new EngineResolver();
            
            // Register PHP engine
            $resolver->register('php', function () {
                return new PhpEngine();
            });
            
            // Register Plug compiler engine
            $resolver->register('plug', function () {
                $compiler = $this->createPlugCompiler();
                return new CompilerEngine($compiler);
            });
            
            return $resolver;
        });
    }

    protected function registerViewFactory(): void
    {
        $this->singleton('view', function () {
            $resolver = $this->container->get('view.engine.resolver');
            $finder = $this->container->get('view.finder');
            
            $factory = new ViewFactoryWithLayouts($resolver, $finder);
            
            // Add default extensions
            $factory->addExtension('plug.php', 'plug');
            $factory->addExtension('php', 'php');
            
            return $factory;
        });

        $this->alias('view', ViewFactoryWithLayouts::class);
    }

    public function boot(): void
    {
        $this->loadViewConfiguration();
        $this->registerCustomDirectives();
    }

    protected function createPlugCompiler(): PlugCompiler
    {
        $cachePath = $this->getCompilerCachePath();
        $compiler = new PlugCompiler($cachePath);
        
        // Register custom directives
        $this->registerPlugDirectives($compiler);
        
        return $compiler;
    }

    protected function registerPlugDirectives(PlugCompiler $compiler): void
    {
        // Add custom directives specific to your framework
        $compiler->directive('asset', function ($matches) {
            $asset = trim($matches[1], "()\"'");
            return "<?php echo asset('{$asset}'); ?>";
        });

        $compiler->directive('url', function ($matches) {
            $url = trim($matches[1], "()\"'");
            return "<?php echo url('{$url}'); ?>";
        });

        $compiler->directive('route', function ($matches) {
            $route = trim($matches[1], "()\"'");
            return "<?php echo route('{$route}'); ?>";
        });

        $compiler->directive('csrf', function ($matches) {
            return "<?php echo csrf_token(); ?>";
        });

        $compiler->directive('method', function ($matches) {
            $method = trim($matches[1], "()\"'");
            return "<?php echo method_field('{$method}'); ?>";
        });
    }

    protected function registerCustomDirectives(): void
    {
        // This method can be overridden to add more custom directives
        // or you can add them via configuration
    }

    protected function getViewPaths(): array
    {
        $config = $this->getViewConfig();
        $paths = $config['paths'] ?? [$this->app->resourcePath('views')];
        
        // Ensure all paths exist
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        return $paths;
    }

    protected function getCompilerCachePath(): string
    {
        $config = $this->getViewConfig();
        $cachePath = $config['compiled'] ?? $this->app->storagePath('framework/views');
        
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        
        return $cachePath;
    }

    protected function loadViewConfiguration(): void
    {
        $view = $this->container->get('view');
        $config = $this->getViewConfig();
        
        // Add additional view paths
        if (isset($config['paths'])) {
            foreach ($config['paths'] as $path) {
                $view->addLocation($path);
            }
        }
        
        // Add view namespaces
        if (isset($config['namespaces'])) {
            foreach ($config['namespaces'] as $namespace => $paths) {
                $view->addNamespace($namespace, $paths);
            }
        }

        // Share global variables
        if (isset($config['shared'])) {
            foreach ($config['shared'] as $key => $value) {
                $view->share($key, $value);
            }
        }
    }

    protected function getViewConfig(): array
    {
        $config = $this->config('view', []);
        
        if (empty($config)) {
            $config = $this->loadConfig('view.php', $this->getDefaultConfig());
        }
        
        return $config;
    }

    protected function getDefaultConfig(): array
    {
        return [
            'paths' => [
                $this->app->resourcePath('views'),
            ],
            'compiled' => $this->app->storagePath('framework/views'),
            'namespaces' => [],
            'shared' => [
                'app' => $this->app,
            ],
        ];
    }
}