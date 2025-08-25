<?php

declare(strict_types=1);

namespace Plugs\View\Compiler;

class AdvancedPlugCompiler extends PlugCompiler
{
    protected array $componentStack = [];
    protected array $componentData = [];

    public function __construct(string $cachePath)
    {
        parent::__construct($cachePath);
        $this->registerAdvancedDirectives();
    }

    protected function registerAdvancedDirectives(): void
    {
        // Component directives
        $this->directive('component', function ($matches) {
            $component = trim($matches[1], "()\"'");
            return "<?php \$this->startComponent('{$component}'); ?>";
        });

        $this->directive('endcomponent', function ($matches) {
            return '<?php echo $this->renderComponent(); ?>';
        });

        // Slot directives
        $this->directive('slot', function ($matches) {
            $slot = trim($matches[1], "()\"'");
            return "<?php \$this->slot('{$slot}'); ?>";
        });

        $this->directive('endslot', function ($matches) {
            return '<?php $this->endSlot(); ?>';
        });

        // Stack directives for assets
        $this->directive('stack', function ($matches) {
            $stack = trim($matches[1], "()\"'");
            return "<?php echo \$this->yieldStack('{$stack}'); ?>";
        });

        $this->directive('push', function ($matches) {
            $stack = trim($matches[1], "()\"'");
            return "<?php \$this->startPush('{$stack}'); ?>";
        });

        $this->directive('endpush', function ($matches) {
            return '<?php $this->stopPush(); ?>';
        });

        // Include with data
        $this->directive('includeWhen', function ($matches) {
            $params = $this->parseDirectiveParameters($matches[1]);
            $condition = $params[0] ?? 'false';
            $view = $params[1] ?? "''";
            $data = $params[2] ?? '[]';
            
            return "<?php if({$condition}) echo \$this->make({$view}, {$data})->render(); ?>";
        });

        $this->directive('includeUnless', function ($matches) {
            $params = $this->parseDirectiveParameters($matches[1]);
            $condition = $params[0] ?? 'true';
            $view = $params[1] ?? "''";
            $data = $params[2] ?? '[]';
            
            return "<?php if(!({$condition})) echo \$this->make({$view}, {$data})->render(); ?>";
        });

        // JSON directive
        $this->directive('json', function ($matches) {
            $data = trim($matches[1], "()");
            return "<?php echo json_encode({$data}); ?>";
        });

        // CSRF directive
        $this->directive('csrf', function ($matches) {
            return "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"' . csrf_token() . '\">'; ?>";
        });

        // Method directive for forms
        $this->directive('method', function ($matches) {
            $method = trim($matches[1], "()\"'");
            return "<?php echo '<input type=\"hidden\" name=\"_method\" value=\"{$method}\">'; ?>";
        });

        // Error handling
        $this->directive('error', function ($matches) {
            $field = trim($matches[1], "()\"'");
            return "<?php if(\$errors && \$errors->has('{$field}')): ?>";
        });

        $this->directive('enderror', function ($matches) {
            return '<?php endif; ?>';
        });

        // Old input directive
        $this->directive('old', function ($matches) {
            $params = $this->parseDirectiveParameters($matches[1]);
            $key = $params[0] ?? "''";
            $default = $params[1] ?? "''";
            return "<?php echo old({$key}, {$default}); ?>";
        });
    }

    protected function parseDirectiveParameters(string $parameters): array
    {
        $parameters = trim($parameters, '()');
        
        if (empty($parameters)) {
            return [];
        }

        // Simple parameter parsing - could be enhanced
        return array_map('trim', explode(',', $parameters));
    }

    protected function compileString(string $value): string
    {
        $result = parent::compileString($value);
        
        // Compile components
        $result = $this->compileComponents($result);
        
        return $result;
    }

    protected function compileComponents(string $value): string
    {
        return preg_replace_callback(
            '/\<x\-([a-z\-\.]+)([^>]*?)\/?\>/i',
            function ($matches) {
                $component = $matches[1];
                $attributes = $this->parseComponentAttributes($matches[2]);
                
                $attributesStr = $this->compileComponentAttributes($attributes);
                
                if (str_ends_with($matches[0], '/>')) {
                    // Self-closing component
                    return "<?php echo \$this->renderComponent('{$component}', {$attributesStr}); ?>";
                } else {
                    // Component with content
                    return "<?php \$this->startComponent('{$component}', {$attributesStr}); ?>";
                }
            },
            $value
        );
    }

    protected function parseComponentAttributes(string $attributeString): array
    {
        $attributes = [];
        
        if (preg_match_all('/([a-z\-]+)=["\']([^"\']*)["\']/', $attributeString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }
        
        return $attributes;
    }

    protected function compileComponentAttributes(array $attributes): string
    {
        if (empty($attributes)) {
            return '[]';
        }

        $compiled = [];
        foreach ($attributes as $key => $value) {
            $compiled[] = "'{$key}' => '{$value}'";
        }

        return '[' . implode(', ', $compiled) . ']';
    }
}