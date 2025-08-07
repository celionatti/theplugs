<?php

declare(strict_types=1);

namespace Plugs\View;

use Exception;
use Plugs\View\ViewInterface;
use Plugs\View\Complier\ViewCompiler;
use Plugs\Exceptions\View\ViewException;

class View implements ViewInterface
{
    protected static array $sharedData = [];
    protected static array $paths = [];
    protected static ?ViewCompiler $compiler = null;
    protected static ?string $cachePath = null;
    protected static bool $cacheEnabled = false;
    
    protected string $template;
    protected array $data;
    protected ?string $layout = null;
    protected array $sections = [];
    protected array $sectionStack = [];
    
    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = array_merge(static::$sharedData, $data);
    }
    
    /**
     * Create a new view instance
     */
    public static function make(string $template, array $data = []): self
    {
        return new static($template, $data);
    }
    
    /**
     * Share data with all views
     */
    public static function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            static::$sharedData = array_merge(static::$sharedData, $key);
        } else {
            static::$sharedData[$key] = $value;
        }
    }
    
    /**
     * Add a view path
     */
    public static function addPath(string $path): void
    {
        static::$paths[] = rtrim($path, '/');
    }
    
    /**
     * Set the compiler instance
     */
    public static function setCompiler(ViewCompiler $compiler): void
    {
        static::$compiler = $compiler;
    }
    
    /**
     * Enable/disable caching and set cache path
     */
    public static function setCaching(bool $enabled, ?string $cachePath = null): void
    {
        static::$cacheEnabled = $enabled;
        if ($cachePath) {
            static::$cachePath = $cachePath;
        }
    }
    
    /**
     * Get the compiler instance
     */
    protected static function getCompiler(): ViewCompiler
    {
        if (static::$compiler === null) {
            static::$compiler = new ViewCompiler();
        }
        
        return static::$compiler;
    }
    
    /**
     * Register a custom directive
     */
    public static function directive(string $name, callable $handler): void
    {
        static::getCompiler()->directive($name, $handler);
    }
    
    /**
     * Render the view
     */
    public function render(): string
    {
        $templatePath = $this->findTemplate($this->template);
        $compiledPath = $this->getCompiledPath($templatePath);
        
        // Check if we need to recompile
        if ($this->needsRecompilation($templatePath, $compiledPath)) {
            $this->compile($templatePath, $compiledPath);
        }
        
        // Render the compiled template
        return $this->renderCompiled($compiledPath);
    }
    
    /**
     * Find template file
     */
    protected function findTemplate(string $name): string
    {
        $name = str_replace('.', '/', $name);
        
        foreach (static::$paths as $path) {
            $templatePath = $path . '/' . $name . '.plug.php';
            if (file_exists($templatePath)) {
                return $templatePath;
            }
        }
        
        throw new ViewException("Template '{$name}' not found in paths: " . implode(', ', static::$paths));
    }
    
    /**
     * Get compiled template path
     */
    protected function getCompiledPath(string $templatePath): string
    {
        if (!static::$cacheEnabled || !static::$cachePath) {
            return $templatePath;
        }
        
        $hash = hash('sha256', $templatePath);
        return static::$cachePath . '/' . $hash . '.php';
    }
    
    /**
     * Check if template needs recompilation
     */
    protected function needsRecompilation(string $templatePath, string $compiledPath): bool
    {
        if (!static::$cacheEnabled) {
            return true;
        }
        
        if (!file_exists($compiledPath)) {
            return true;
        }
        
        return filemtime($templatePath) > filemtime($compiledPath);
    }
    
    /**
     * Compile template
     */
    protected function compile(string $templatePath, string $compiledPath): void
    {
        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new ViewException("Unable to read template file: {$templatePath}");
        }
        
        try {
            $compiled = static::getCompiler()->compile($content);
            
            if (static::$cacheEnabled && static::$cachePath) {
                $dir = dirname($compiledPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($compiledPath, $compiled);
            }
        } catch (Exception $e) {
            throw new ViewException("Template compilation failed for '{$templatePath}': " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Render compiled template
     */
    protected function renderCompiled(string $compiledPath): string
    {
        if (static::$cacheEnabled && file_exists($compiledPath)) {
            $__path = $compiledPath;
        } else {
            // Create temporary file for non-cached compilation
            $content = file_get_contents($this->findTemplate($this->template));
            $compiled = static::getCompiler()->compile($content);
            $__path = tempnam(sys_get_temp_dir(), 'view_') . '.php';
            file_put_contents($__path, $compiled);
        }
        
        // Extract data to local scope
        extract($this->data, EXTR_SKIP);
        
        try {
            ob_start();
            include $__path;
            $content = ob_get_clean();
            
            // Clean up temporary file
            if (!static::$cacheEnabled && file_exists($__path)) {
                unlink($__path);
            }
            
            // If we have a layout, render it with the content
            if ($this->layout) {
                $layoutView = new static($this->layout, array_merge($this->data, [
                    'content' => $content
                ]));
                $layoutView->sections = $this->sections;
                return $layoutView->render();
            }
            
            return $content ?: '';
        } catch (Exception $e) {
            // Clean up on error
            if (!static::$cacheEnabled && file_exists($__path)) {
                unlink($__path);
            }
            throw new ViewException("Template rendering failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Extend a layout
     */
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }
    
    /**
     * Start a section
     */
    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }
    
    /**
     * End current section
     */
    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new ViewException("No section to end");
        }
        
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }
    
    /**
     * Yield section content
     */
    public function yieldContent(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }
    
    /**
     * Include a partial template
     */
    public function include(string $template, array $data = []): string
    {
        $includeView = new static($template, array_merge($this->data, $data));
        return $includeView->render();
    }
    
    /**
     * Generate CSRF token field
     */
    public function csrf(): string
    {
        // In a real application, this would generate/retrieve an actual CSRF token
        $token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Convert view to string
     */
    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return "View Error: " . $e->getMessage();
        }
    }
}