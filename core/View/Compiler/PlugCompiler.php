<?php

declare(strict_types=1);

namespace Plugs\View\Compiler;

use Plugs\View\Compiler\BaseCompiler;
use Plugs\Exceptions\View\ViewCompilationException;

class PlugCompiler extends BaseCompiler
{
    protected array $directives = [];
    protected array $conditions = [];
    protected array $customDirectives = [];

    public function __construct(string $cachePath)
    {
        parent::__construct($cachePath);
        $this->registerDefaultDirectives();
    }

    public function compile(string $path): string
    {
        try {
            $contents = file_get_contents($path);
            
            if ($contents === false) {
                throw new ViewCompilationException('Unable to read view file', basename($path), $path);
            }

            $compiled = $this->compileString($contents);
            
            $compiledPath = $this->getCompiledPath($path);
            $this->ensureCacheDirectoryExists();
            
            file_put_contents($compiledPath, $compiled);
            
            return $compiledPath;
        } catch (\Exception $e) {
            throw new ViewCompilationException(
                $e->getMessage(), 
                basename($path), 
                $path, 
                0, 
                $e
            );
        }
    }

    protected function compileString(string $value): string
    {
        $result = $this->compileExtends($value);
        $result = $this->compileIncludes($result);
        $result = $this->compileEchos($result);
        $result = $this->compileStatements($result);
        
        return $result;
    }

    protected function compileExtends(string $value): string
    {
        return preg_replace_callback(
            '/\@extends\s*\(\s*[\'"](.+?)[\'"]\s*\)/',
            function ($matches) {
                return "<?php \$this->extend('{$matches[1]}'); ?>";
            },
            $value
        );
    }

    protected function compileIncludes(string $value): string
    {
        return preg_replace_callback(
            '/\@include\s*\(\s*[\'"](.+?)[\'"]\s*(?:,\s*(.+?))?\s*\)/',
            function ($matches) {
                $view = $matches[1];
                $data = $matches[2] ?? '[]';
                return "<?php echo \$this->make('{$view}', {$data})->render(); ?>";
            },
            $value
        );
    }

    protected function compileEchos(string $value): string
    {
        // Escaped echoes {{{ }}}
        $value = preg_replace('/\{\{\{\s*(.+?)\s*\}\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>', $value);
        
        // Unescaped echoes {{ }}
        $value = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo $1; ?>', $value);
        
        return $value;
    }

    protected function compileStatements(string $value): string
    {
        foreach ($this->directives as $directive => $callback) {
            $value = preg_replace_callback(
                "/\@{$directive}(\s*\(.*?\))?/",
                $callback,
                $value
            );
        }

        return $value;
    }

    protected function registerDefaultDirectives(): void
    {
        // Conditional directives
        $this->directive('if', function ($matches) {
            return "<?php if{$matches[1]}: ?>";
        });

        $this->directive('elseif', function ($matches) {
            return "<?php elseif{$matches[1]}: ?>";
        });

        $this->directive('else', function ($matches) {
            return '<?php else: ?>';
        });

        $this->directive('endif', function ($matches) {
            return '<?php endif; ?>';
        });

        // Loop directives
        $this->directive('foreach', function ($matches) {
            return "<?php foreach{$matches[1]}: ?>";
        });

        $this->directive('endforeach', function ($matches) {
            return '<?php endforeach; ?>';
        });

        $this->directive('for', function ($matches) {
            return "<?php for{$matches[1]}: ?>";
        });

        $this->directive('endfor', function ($matches) {
            return '<?php endfor; ?>';
        });

        $this->directive('while', function ($matches) {
            return "<?php while{$matches[1]}: ?>";
        });

        $this->directive('endwhile', function ($matches) {
            return '<?php endwhile; ?>';
        });

        // Utility directives
        $this->directive('php', function ($matches) {
            return '<?php ';
        });

        $this->directive('endphp', function ($matches) {
            return ' ?>';
        });

        $this->directive('section', function ($matches) {
            $section = trim($matches[1], "()\"'");
            return "<?php \$this->startSection('{$section}'); ?>";
        });

        $this->directive('endsection', function ($matches) {
            return '<?php $this->stopSection(); ?>';
        });

        $this->directive('yield', function ($matches) {
            $section = trim($matches[1], "()\"'");
            return "<?php echo \$this->yieldSection('{$section}'); ?>";
        });
    }

    public function directive(string $name, callable $handler): void
    {
        $this->directives[$name] = $handler;
    }

    public function getDirectives(): array
    {
        return $this->directives;
    }
}