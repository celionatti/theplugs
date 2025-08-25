<?php

declare(strict_types=1);

namespace Plugs\View;

use Plugs\Exceptions\View\ViewNotFoundException;

class ViewFinder
{
    protected array $paths = [];
    protected array $views = [];
    protected array $extensions = ['plug.php', 'php'];
    protected array $namespaces = [];

    public function __construct(array $paths = [])
    {
        $this->paths = array_map([$this, 'resolvePath'], $paths);
    }

    public function find(string $name): string
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        if ($this->hasHintInformation($name)) {
            return $this->views[$name] = $this->findNamespacedView($name);
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

    protected function findNamespacedView(string $name): string
    {
        [$namespace, $view] = $this->parseNamespace($name);

        return $this->findInPaths($view, $this->namespaces[$namespace]);
    }

    protected function parseNamespace(string $name): array
    {
        $segments = explode('::', $name);
        
        if (count($segments) !== 2) {
            throw new \InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        if (!isset($this->namespaces[$segments[0]])) {
            throw new \InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    protected function findInPaths(string $name, array $paths): string
    {
        foreach ($paths as $path) {
            foreach ($this->getPossibleViewFiles($name) as $file) {
                if (file_exists($viewPath = $path . DIRECTORY_SEPARATOR . $file)) {
                    return $viewPath;
                }
            }
        }

        throw new ViewNotFoundException($name, $paths);
    }

    protected function getPossibleViewFiles(string $name): array
    {
        return array_map(function ($extension) use ($name) {
            return str_replace('.', DIRECTORY_SEPARATOR, $name) . '.' . $extension;
        }, $this->extensions);
    }

    protected function hasHintInformation(string $name): bool
    {
        return strpos($name, '::') > 0;
    }

    public function addLocation(string $location): void
    {
        $this->paths[] = $this->resolvePath($location);
    }

    public function prependLocation(string $location): void
    {
        array_unshift($this->paths, $this->resolvePath($location));
    }

    public function addNamespace(string $namespace, string|array $hints): void
    {
        $hints = (array) $hints;
        
        if (isset($this->namespaces[$namespace])) {
            $hints = array_merge($this->namespaces[$namespace], $hints);
        }

        $this->namespaces[$namespace] = $hints;
    }

    public function prependNamespace(string $namespace, string|array $hints): void
    {
        $hints = (array) $hints;
        
        if (isset($this->namespaces[$namespace])) {
            $hints = array_merge($hints, $this->namespaces[$namespace]);
        }

        $this->namespaces[$namespace] = $hints;
    }

    public function addExtension(string $extension): void
    {
        if (($index = array_search($extension, $this->extensions)) !== false) {
            unset($this->extensions[$index]);
        }

        array_unshift($this->extensions, $extension);
    }

    public function exists(string $view): bool
    {
        try {
            $this->find($view);
            return true;
        } catch (ViewNotFoundException) {
            return false;
        }
    }

    public function flush(): void
    {
        $this->views = [];
    }

    protected function resolvePath(string $path): string
    {
        return realpath($path) ?: $path;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
}