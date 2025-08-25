<?php

declare(strict_types=1);

namespace Plugs\View;

use Plugs\View\View;
use Plugs\View\EngineResolver;
use Plugs\View\Contracts\ViewInterface;
use Plugs\View\Contracts\EngineInterface;
use Plugs\View\Contracts\ViewFactoryInterface;

class ViewFactory implements ViewFactoryInterface
{
    protected EngineResolver $engines;
    protected ViewFinder $finder;
    protected array $shared = [];
    protected array $extensions = [];

    public function __construct(EngineResolver $engines, ViewFinder $finder)
    {
        $this->engines = $engines;
        $this->finder = $finder;
    }

    public function make(string $view, array $data = []): ViewInterface
    {
        $path = $this->finder->find($view);
        $engine = $this->getEngineFromPath($path);

        return new View($this, $engine, $view, $path, $data);
    }

    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    public function addLocation(string $location): void
    {
        $this->finder->addLocation($location);
    }

    public function addExtension(string $extension, string $engine): void
    {
        $this->finder->addExtension($extension);
        $this->extensions[$extension] = $engine;
    }

    public function addNamespace(string $namespace, string $hints): void
    {
        $this->finder->addNamespace($namespace, $hints);
    }

    public function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
    }

    public function getShared(): array
    {
        return $this->shared;
    }

    protected function getEngineFromPath(string $path): EngineInterface
    {
        $extension = $this->getExtension($path);
        
        $engineName = $this->extensions[$extension] ?? $this->guessEngineFromExtension($extension);
        
        return $this->engines->resolve($engineName);
    }

    protected function getExtension(string $path): string
    {
        $pathInfo = pathinfo($path);
        $extension = $pathInfo['extension'] ?? '';
        
        // Handle compound extensions like .plug.php
        if ($extension === 'php') {
            $basename = $pathInfo['filename'] ?? '';
            if (str_ends_with($basename, '.plug')) {
                return 'plug.php';
            }
        }
        
        return $extension;
    }

    protected function guessEngineFromExtension(string $extension): string
    {
        return match ($extension) {
            'plug.php' => 'plug',
            'php' => 'php',
            default => 'php',
        };
    }

    public function getFinder(): ViewFinder
    {
        return $this->finder;
    }

    public function getEngineResolver(): EngineResolver
    {
        return $this->engines;
    }
}