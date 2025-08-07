<?php

declare(strict_types=1);

namespace Plugs\View\Complier;

class ViewCompiler
{
    protected array $customDirectives = [];
    protected array $compileStack = [];
    protected bool $inVerbatim = false;
    
    /**
     * Register a custom directive
     */
    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }
    
    /**
     * Compile template content into executable PHP
     */
    public function compile(string $template): string
    {
        $this->compileStack = [];
        $this->inVerbatim = false;
        
        // Handle verbatim blocks first
        $template = $this->compileVerbatim($template);
        
        // Compile directives in order
        $template = $this->compileEchos($template);
        $template = $this->compileDirectives($template);
        $template = $this->compileCustomDirectives($template);
        
        return $template;
    }
    
    /**
     * Handle @verbatim blocks
     */
    protected function compileVerbatim(string $template): string
    {
        return preg_replace_callback(
            '/@verbatim(.*?)@endverbatim/s',
            fn($matches) => '<?php echo ' . var_export($matches[1], true) . '; ?>',
            $template
        );
    }
    
    /**
     * Compile echo statements
     */
    protected function compileEchos(string $template): string
    {
        // Raw echo {!! $var !!}
        $template = preg_replace(
            '/\{\!\!\s*(.+?)\s*\!\!\}/',
            '<?php echo $1; ?>',
            $template
        );
        
        // Escaped echo {{ $var }}
        $template = preg_replace(
            '/\{\{\s*(.+?)\s*\}\}/',
            '<?php echo htmlspecialchars($1 ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>',
            $template
        );
        
        return $template;
    }
    
    /**
     * Compile all directives
     */
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
        
        return $template;
    }
    
    /**
     * Compile if statements
     */
    protected function compileIf(string $template): string
    {
        $template = preg_replace('/@if\s*\((.+?)\)/', '<?php if($1): ?>', $template);
        $template = preg_replace('/@elseif\s*\((.+?)\)/', '<?php elseif($1): ?>', $template);
        $template = preg_replace('/@else/', '<?php else: ?>', $template);
        $template = preg_replace('/@endif/', '<?php endif; ?>', $template);
        
        return $template;
    }
    
    /**
     * Compile loop statements
     */
    protected function compileLoops(string $template): string
    {
        // foreach
        $template = preg_replace('/@foreach\s*\((.+?)\)/', '<?php foreach($1): ?>', $template);
        $template = preg_replace('/@endforeach/', '<?php endforeach; ?>', $template);
        
        // for
        $template = preg_replace('/@for\s*\((.+?)\)/', '<?php for($1): ?>', $template);
        $template = preg_replace('/@endfor/', '<?php endfor; ?>', $template);
        
        // while
        $template = preg_replace('/@while\s*\((.+?)\)/', '<?php while($1): ?>', $template);
        $template = preg_replace('/@endwhile/', '<?php endwhile; ?>', $template);
        
        return $template;
    }
    
    /**
     * Compile @extends directive
     */
    protected function compileExtends(string $template): string
    {
        return preg_replace(
            '/@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/',
            '<?php $this->extend(\'$1\'); ?>',
            $template
        );
    }
    
    /**
     * Compile @section directive
     */
    protected function compileSection(string $template): string
    {
        $template = preg_replace(
            '/@section\s*\(\s*[\'"](.+?)[\'"]\s*\)/',
            '<?php $this->startSection(\'$1\'); ?>',
            $template
        );
        
        $template = preg_replace('/@endsection/', '<?php $this->endSection(); ?>', $template);
        
        return $template;
    }
    
    /**
     * Compile @yield directive
     */
    protected function compileYield(string $template): string
    {
        return preg_replace(
            '/@yield\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/',
            '<?php echo $this->yieldContent(\'$1\', $2 ?? \'\'); ?>',
            $template
        );
    }
    
    /**
     * Compile @include directive
     */
    protected function compileInclude(string $template): string
    {
        return preg_replace(
            '/@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/',
            '<?php echo $this->include(\'$1\', $2 ?? []); ?>',
            $template
        );
    }
    
    /**
     * Compile @csrf directive
     */
    protected function compileCsrf(string $template): string
    {
        return preg_replace(
            '/@csrf/',
            '<?php echo $this->csrf(); ?>',
            $template
        );
    }
    
    /**
     * Compile custom directives
     */
    protected function compileCustomDirectives(string $template): string
    {
        foreach ($this->customDirectives as $name => $handler) {
            $pattern = '/@' . $name . '(?:\s*\((.+?)\))?/';
            $template = preg_replace_callback($pattern, function ($matches) use ($handler) {
                $expression = $matches[1] ?? null;
                return $handler($expression);
            }, $template);
        }
        
        return $template;
    }
}