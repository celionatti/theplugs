<?php

declare(strict_types=1);

namespace Plugs\View;

use Plugs\Exceptions\View\ViewException;

class ViewFinder
{
    protected array $paths = [];
    protected array $extensions = ['.plug.php', '.php', '.html'];
    protected array $viewCache = [];

    public function __construct(array $paths = [])
    {
        $this->paths = array_map(fn($path) => rtrim($path, '/'), $paths);
    }

    /**
     * Add a new view path.
     */
    public function addPath(string $path): void
    {
        $path = rtrim($path, '/');
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
    }

    /**
     * Prepend a view path to the beginning.
     */
    public function prependPath(string $path): void
    {
        $path = rtrim($path, '/');
        array_unshift($this->paths, $path);
    }

    /**
     * Get all registered paths.
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Set the view file extensions.
     */
    public function addExtension(string $extension): void
    {
        if (!in_array($extension, $this->extensions)) {
            $this->extensions[] = $extension;
        }
    }

    /**
     * Get registered extensions.
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Find a view file by name.
     */
    public function find(string $name): string
    {
        // Check cache first
        if (isset($this->viewCache[$name])) {
            return $this->viewCache[$name];
        }

        // Normalize view name (convert dots to slashes)
        $name = $this->normalizeName($name);

        // Search through paths and extensions
        foreach ($this->paths as $path) {
            foreach ($this->extensions as $extension) {
                $viewPath = $path . '/' . $name . $extension;
                
                if (file_exists($viewPath)) {
                    return $this->viewCache[$name] = $viewPath;
                }
            }
        }

        throw new ViewException(
            "View '{$name}' not found. Searched in paths: " . implode(', ', $this->paths) . 
            " with extensions: " . implode(', ', $this->extensions)
        );
    }

    /**
     * Check if a view exists.
     */
    public function exists(string $name): bool
    {
        try {
            $this->find($name);
            return true;
        } catch (ViewException $e) {
            return false;
        }
    }

    /**
     * Get the fully qualified location of the view.
     */
    public function getViewLocation(string $name): array
    {
        $path = $this->find($name);
        
        return [
            'path' => $path,
            'name' => $name,
            'extension' => $this->getFileExtension($path),
            'directory' => dirname($path),
            'filename' => basename($path)
        ];
    }

    /**
     * Clear the view cache.
     */
    public function clearCache(): void
    {
        $this->viewCache = [];
    }

    /**
     * Get all views in the registered paths.
     */
    public function getAllViews(): array
    {
        $views = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = '.' . $file->getExtension();
                    
                    if (in_array($extension, $this->extensions)) {
                        $relativePath = $this->getRelativePath($file->getPathname(), $path);
                        $viewName = $this->pathToViewName($relativePath, $extension);
                        $views[$viewName] = $file->getPathname();
                    }
                }
            }
        }

        return $views;
    }

    /**
     * Get views that match a pattern.
     */
    public function getViewsMatching(string $pattern): array
    {
        $allViews = $this->getAllViews();
        $matchingViews = [];

        foreach ($allViews as $name => $path) {
            if ($this->matchesPattern($name, $pattern)) {
                $matchingViews[$name] = $path;
            }
        }

        return $matchingViews;
    }

    /**
     * Normalize view name (convert dots to directory separators).
     */
    protected function normalizeName(string $name): string
    {
        return str_replace('.', '/', $name);
    }

    /**
     * Get file extension from path.
     */
    protected function getFileExtension(string $path): string
    {
        foreach ($this->extensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return $extension;
            }
        }

        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get relative path from base path.
     */
    protected function getRelativePath(string $fullPath, string $basePath): string
    {
        return ltrim(str_replace($basePath, '', $fullPath), '/\\');
    }

    /**
     * Convert file path to view name.
     */
    protected function pathToViewName(string $path, string $extension): string
    {
        $name = str_replace($extension, '', $path);
        return str_replace(['/', '\\'], '.', $name);
    }

    /**
     * Check if view name matches pattern.
     */
    protected function matchesPattern(string $name, string $pattern): bool
    {
        // Simple exact match
        if ($pattern === $name) {
            return true;
        }

        // Wildcard matching
        if (str_contains($pattern, '*')) {
            $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));
            return (bool) preg_match("/^{$regex}$/", $name);
        }

        // Prefix matching (ends with *)
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');
            return str_starts_with($name, $prefix);
        }

        return false;
    }

    /**
     * Get view namespace from name.
     */
    public function getNamespace(string $name): ?string
    {
        if (str_contains($name, '::')) {
            return explode('::', $name, 2)[0];
        }

        return null;
    }

    /**
     * Parse namespaced view name.
     */
    public function parseNamespaceView(string $name): array
    {
        if (str_contains($name, '::')) {
            $segments = explode('::', $name, 2);
            return ['namespace' => $segments[0], 'view' => $segments[1]];
        }

        return ['namespace' => null, 'view' => $name];
    }

    /**
     * Add a namespace hint.
     */
    public function addNamespace(string $namespace, string|array $hints): void
    {
        $hints = (array) $hints;
        
        foreach ($hints as $hint) {
            $this->addPath($hint);
        }
    }

    /**
     * Flush the view cache and recompile all views.
     */
    public function flush(): void
    {
        $this->clearCache();
    }
}