<?php

declare(strict_types=1);

namespace Plugs\View\Compiler;

class ViewCompiler
{
    protected array $customDirectives = [];
    protected array $compileStack = [];
    protected bool $inVerbatim = false;
    protected array $verbatimBlocks = [];

    // Reserved variable names that cannot be used in templates to avoid collisions
    protected array $reservedVariables = [
        'this',
        '__view',
        '__data',
        '__sections',
        '__layout',
        '__output',
        '__temp',
        '__content',
        '__compiler',
        '__template',
        '__path'
    ];

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    public function compile(string $template): string
    {
        $this->compileStack = [];
        $this->inVerbatim = false;
        $this->verbatimBlocks = [];

        // IMPORTANT: Process in the correct order
        // 1. Handle verbatim blocks first (to protect content)
        $template = $this->compileVerbatim($template);

        // 2. Compile structural directives
        $template = $this->compileDirectives($template);

        // 3. Compile echo statements 
        $template = $this->compileEchos($template);

        // 4. Custom directives
        $template = $this->compileCustomDirectives($template);

        // 5. Restore verbatim blocks
        $template = $this->restoreVerbatim($template);

        return $template;
    }

    protected function compileVerbatim(string $template): string
    {
        // Store verbatim content and replace with placeholders
        $template = preg_replace_callback(
            '/@verbatim(.*?)@endverbatim/s',
            function ($matches) {
                $placeholder = '__VERBATIM_' . count($this->verbatimBlocks) . '__';
                $this->verbatimBlocks[$placeholder] = $matches[1];
                return $placeholder;
            },
            $template
        );

        return $template;
    }

    protected function restoreVerbatim(string $template): string
    {
        foreach ($this->verbatimBlocks as $placeholder => $content) {
            $template = str_replace($placeholder, $content, $template);
        }
        return $template;
    }

    protected function compileEchos(string $template): string
    {
        // Raw echo {!! $var !!} - no escaping
        $template = preg_replace(
            '/\{\!\!\s*(.+?)\s*\!\!\}/s',
            '<?php echo $1; ?>',
            $template
        );

        // Escaped echo {{ $var }} - with proper escaping
        $template = preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            function ($matches) {
                $expression = trim($matches[1]);

                // Handle null coalescing operator
                if (strpos($expression, '??') !== false) {
                    return "<?php echo htmlspecialchars(($expression), ENT_QUOTES, 'UTF-8', false); ?>";
                } else {
                    return "<?php echo htmlspecialchars(($expression) ?? '', ENT_QUOTES, 'UTF-8', false); ?>";
                }
            },
            $template
        );

        return $template;
    }

    protected function compileDirectives(string $template): string
    {
        // Control structures
        $template = $this->compileIf($template);
        $template = $this->compileLoops($template);

        // Layout and sections  
        $template = $this->compileExtends($template);
        $template = $this->compileSection($template);
        $template = $this->compileYield($template);

        // Includes
        $template = $this->compileInclude($template);

        // CSRF
        $template = $this->compileCsrf($template);

        // Assets
        $template = $this->compileAssets($template);

        // Additional directives
        $template = $this->compileEmpty($template);
        $template = $this->compileIsset($template);
        $template = $this->compileAuth($template);

        return $template;
    }

    protected function compileIf(string $template): string
    {
        $template = preg_replace('/@if\s*\((.+?)\)/', '<?php if($1): ?>', $template);
        $template = preg_replace('/@elseif\s*\((.+?)\)/', '<?php elseif($1): ?>', $template);
        $template = preg_replace('/@else/', '<?php else: ?>', $template);
        $template = preg_replace('/@endif/', '<?php endif; ?>', $template);
        return $template;
    }

    protected function compileLoops(string $template): string
    {
        // foreach with better variable isolation
        $template = preg_replace('/@foreach\s*\((.+?)\)/', '<?php foreach($1): ?>', $template);
        $template = preg_replace('/@endforeach/', '<?php endforeach; ?>', $template);

        // for
        $template = preg_replace('/@for\s*\((.+?)\)/', '<?php for($1): ?>', $template);
        $template = preg_replace('/@endfor/', '<?php endfor; ?>', $template);

        // while
        $template = preg_replace('/@while\s*\((.+?)\)/', '<?php while($1): ?>', $template);
        $template = preg_replace('/@endwhile/', '<?php endwhile; ?>', $template);

        // forelse (foreach with empty fallback)
        $template = preg_replace('/@forelse\s*\((.+?)\)/', '<?php $__empty = true; foreach($1): $__empty = false; ?>', $template);
        $template = preg_replace('/@empty/', '<?php endforeach; if($__empty): ?>', $template);
        $template = preg_replace('/@endforelse/', '<?php endif; ?>', $template);

        return $template;
    }

    protected function compileExtends(string $template): string
    {
        return preg_replace(
            '/@extends\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*\)/',
            '<?php $__view->extend(\'$1\'); ?>',
            $template
        );
    }

    protected function compileSection(string $template): string
    {
        // Fix variable collision by using $__view instead of $this
        $template = preg_replace(
            '/@section\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*\)/',
            '<?php $__view->startSection(\'$1\'); ?>',
            $template
        );

        $template = preg_replace('/@endsection/', '<?php $__view->endSection(); ?>', $template);

        // Handle @show directive (end section and immediately yield)
        $template = preg_replace('/@show/', '<?php echo $__view->endSectionAndShow(); ?>', $template);

        return $template;
    }

    protected function compileYield(string $template): string
    {
        return preg_replace_callback(
            '/@yield\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*(?:,\s*(.+?))?\s*\)/',
            function ($matches) {
                $section = $matches[1];
                $default = $matches[2] ?? "''";
                return "<?php echo \$__view->yieldContent('$section', $default); ?>";
            },
            $template
        );
    }

    protected function compileInclude(string $template): string
    {
        return preg_replace_callback(
            '/@include\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*(?:,\s*(.+?))?\s*\)/',
            function ($matches) {
                $templateName = $matches[1];
                $data = $matches[2] ?? '[]';
                return "<?php echo \$__view->include('$templateName', $data); ?>";
            },
            $template
        );
    }

    protected function compileCsrf(string $template): string
    {
        return preg_replace('/@csrf/', '<?php echo $__view->csrf(); ?>', $template);
    }

    // Additional useful directives
    protected function compileEmpty(string $template): string
    {
        return preg_replace('/@empty\s*\((.+?)\)/', '<?php if(empty($1)): ?>', $template);
    }

    protected function compileIsset(string $template): string
    {
        return preg_replace('/@isset\s*\((.+?)\)/', '<?php if(isset($1)): ?>', $template);
    }

    protected function compileAuth(string $template): string
    {
        $template = preg_replace('/@auth/', '<?php if($__view->isAuthenticated()): ?>', $template);
        $template = preg_replace('/@guest/', '<?php if(!$__view->isAuthenticated()): ?>', $template);
        $template = preg_replace('/@endauth/', '<?php endif; ?>', $template);
        $template = preg_replace('/@endguest/', '<?php endif; ?>', $template);
        return $template;
    }

    protected function compileAssets(string $template): string
    {
        // @asset('path/to/file')
        $template = preg_replace(
            '/@asset\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '<?php echo $__view->asset(\'$1\'); ?>',
            $template
        );

        // @css('path/to/file', ['attr' => 'value'])
        $template = preg_replace_callback(
            '/@css\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+))?\s*\)/',
            function ($matches) {
                $path = $matches[1];
                $attrs = $matches[2] ?? '[]';
                return "<?php echo \$__view->css('$path', $attrs); ?>";
            },
            $template
        );

        // @js('path/to/file', ['attr' => 'value'])
        $template = preg_replace_callback(
            '/@js\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+))?\s*\)/',
            function ($matches) {
                $path = $matches[1];
                $attrs = $matches[2] ?? '[]';
                return "<?php echo \$__view->js('$path', $attrs); ?>";
            },
            $template
        );

        // @img('path/to/file', ['attr' => 'value'])
        $template = preg_replace_callback(
            '/@img\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(.+))?\s*\)/',
            function ($matches) {
                $path = $matches[1];
                $attrs = $matches[2] ?? '[]';
                return "<?php echo \$__view->img('$path', $attrs); ?>";
            },
            $template
        );

        return $template;
    }

    protected function compileClassDirective(string $template): string
    {
        return preg_replace_callback(
            '/@class\(\s*(.+?)\s*\)/',
            function ($matches) {
                return "<?php echo 'class=\"' . implode(' ', array_filter($matches[1])) . '\"'; ?>";
            },
            $template
        );
    }

    protected function compileCustomDirectives(string $template): string
    {
        foreach ($this->customDirectives as $name => $handler) {
            $pattern = '/@' . preg_quote($name) . '(?:\s*\((.+?)\))?/';
            $template = preg_replace_callback($pattern, function ($matches) use ($handler) {
                $expression = $matches[1] ?? null;
                return $handler($expression);
            }, $template);
        }

        return $template;
    }

    /**
     * Validate that template variables don't collide with reserved names
     */
    public function validateVariables(array $data): array
    {
        $conflicts = array_intersect_key($data, array_flip($this->reservedVariables));

        if (!empty($conflicts)) {
            throw new \InvalidArgumentException(
                'Template data contains reserved variable names: ' .
                    implode(', ', array_keys($conflicts)) . '. ' .
                    'Reserved names are: ' . implode(', ', $this->reservedVariables)
            );
        }

        return $data;
    }

    /**
     * Get reserved variable names
     */
    public function getReservedVariables(): array
    {
        return $this->reservedVariables;
    }
}
