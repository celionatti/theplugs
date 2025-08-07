<?php

declare(strict_types=1);

namespace Plugs\Services;

use Plugs\View\View;
use Plugs\View\Complier\ViewCompiler;

class ViewServiceProvider
{
    public function register($container): void
    {
        // Register ViewCompiler
        $container->singleton(ViewCompiler::class, function() {
            $compiler = new ViewCompiler();
            
            // Register some built-in custom directives
            $compiler->directive('dump', function($expression) {
                return "<?php var_dump($expression); ?>";
            });
            
            $compiler->directive('dd', function($expression) {
                return "<?php var_dump($expression); die(); ?>";
            });
            
            $compiler->directive('json', function($expression) {
                return "<?php echo json_encode($expression, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>";
            });
            
            return $compiler;
        });
        
        // Register View with compiler
        $container->singleton('view', function($container) {
            $compiler = $container->make(ViewCompiler::class);
            View::setCompiler($compiler);
            
            // Set default paths (customize as needed)
            View::addPath(__DIR__ . '/../../resources/views');
            
            // Enable caching if cache directory exists
            $cachePath = __DIR__ . '/../../storage/cache/views';
            if (is_dir(dirname($cachePath))) {
                View::setCaching(true, $cachePath);
            }
            
            return View::class;
        });
    }
}