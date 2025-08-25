<?php

declare(strict_types=1);

namespace Plugs\View\Engines;

use Exception;
use Plugs\View\Compiler\ViewCompiler;
use Plugs\Exceptions\View\ViewException;

class PlugsEngine implements EngineInterface
{
    protected ViewCompiler $compiler;
    protected ?string $cachePath = null;
    protected bool $cacheEnabled = false;

    public function __construct(?ViewCompiler $compiler = null)
    {
        $this->compiler = $compiler ?? new ViewCompiler();
    }

    /**
     * Set the view compiler instance.
     */
    public function setCompiler(ViewCompiler $compiler): void
    {
        $this->compiler = $compiler;
    }

    /**
     * Enable or disable view caching.
     */
    public function setCaching(bool $enabled, ?string $cachePath = null): void
    {
        $this->cacheEnabled = $enabled;
        if ($cachePath) {
            $this->cachePath = rtrim($cachePath, '/');
            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, 0755, true);
            }
        }
    }

    /**
     * Get the evaluated contents of the view.
     */
    public function get(string $path, array $data = []): string
    {
        // Check if we should use cache
        if ($this->cacheEnabled && $this->cachePath) {
            return $this->getWithCaching($path, $data);
        }

        return $this->getWithoutCaching($path, $data);
    }

    /**
     * Get the view contents with caching enabled.
     */
    protected function getWithCaching(string $path, array $data): string
    {
        $cacheKey = $this->getCacheKey($path);
        $cacheFile = $this->cachePath . '/' . $cacheKey . '.php';

        // Check if cache exists and is fresh
        if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($path)) {
            return $this->evaluateCompiledFile($cacheFile, $data);
        }

        // Compile and cache
        $compiled = $this->compileView($path);
        file_put_contents($cacheFile, $compiled);

        return $this->evaluateCompiledFile($cacheFile, $data);
    }

    /**
     * Get the view contents without caching.
     */
    protected function getWithoutCaching(string $path, array $data): string
    {
        $compiled = $this->compileView($path);
        return $this->evaluateCompiled($compiled, $data);
    }

    /**
     * Compile a view file.
     */
    protected function compileView(string $path): string
    {
        $content = file_get_contents($path);
        
        if ($content === false) {
            throw new ViewException("Unable to read view file: {$path}");
        }

        return $this->compiler->compile($content);
    }

    /**
     * Evaluate compiled PHP code.
     */
    protected function evaluateCompiled(string $compiled, array $data): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'plugs_view_') . '.php';
        
        if (file_put_contents($tempFile, $compiled) === false) {
            throw new ViewException("Failed to write compiled view");
        }

        try {
            $result = $this->evaluateCompiledFile($tempFile, $data);
            unlink($tempFile);
            return $result;
        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }

    /**
     * Evaluate a compiled view file.
     */
    protected function evaluateCompiledFile(string $file, array $data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        // Extract data safely
        $__path = $file;
        $__data = $data;
        extract($data, EXTR_SKIP);

        try {
            include $__path;
        } catch (Exception $e) {
            $this->handleViewException($e, $obLevel);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * Generate a cache key for the given path.
     */
    protected function getCacheKey(string $path): string
    {
        return md5($path . filemtime($path));
    }

    /**
     * Handle a view exception.
     */
    protected function handleViewException(Exception $e, int $obLevel): void
    {
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
        }

        throw new ViewException("Error rendering Plugs template: " . $e->getMessage(), 0, $e);
    }

    /**
     * Clear the view cache.
     */
    public function clearCache(): void
    {
        if ($this->cachePath && is_dir($this->cachePath)) {
            $files = glob($this->cachePath . '/*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Get the compiler instance.
     */
    public function getCompiler(): ViewCompiler
    {
        return $this->compiler;
    }

    /**
     * Check if caching is enabled.
     */
    public function isCachingEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Get the cache path.
     */
    public function getCachePath(): ?string
    {
        return $this->cachePath;
    }
}