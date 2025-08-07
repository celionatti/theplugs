<?php

declare(strict_types=1);

namespace Plugs\Services;

use Plugs\Plugs;
use Plugs\View\View;
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
        // View::setAssetPath($app->urlPath('assets'));
        $assetPath = rtrim($app->urlPath(), '/') . '/assets';
        View::setAssetPath($assetPath);

        // Add additional paths from config if available
        if ($this->app->has('config') && isset($this->app->get('config')['view']['paths'])) {
            foreach ($this->app->get('config')['view']['paths'] as $path) {
                View::addPath($path);
            }
        }
    }

    /**
     * Configure view caching based on environment.
     */
    protected function configureViewCaching(): void
    {
        $app = $this->app->get(Plugs::class);

        if ($app->environment() === 'production') {
            $cachePath = $app->basePath('storage/cache/views');
            View::setCaching(true, $cachePath);
        }
    }

    /**
     * Share variables with all views.
     */
    protected function shareGlobalVariables(): void
    {
        // Share config if available
        if ($this->app->has('config')) {
            View::share('config', $this->app->get('config'));
        }
    }
}
