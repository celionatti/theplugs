<?php

declare(strict_types=1);

namespace Plugs\View\Engines;

use Plugs\View\Engines\PhpEngine;
use Plugs\View\Engines\FileEngine;
use Plugs\View\Engines\PlugsEngine;
use Plugs\Exceptions\View\ViewException;

class EngineResolver
{
    protected array $resolvers = [];
    protected array $resolved = [];

    public function __construct()
    {
        $this->registerDefaultResolvers();
    }

    /**
     * Register a view engine resolver.
     */
    public function register(string $engine, \Closure $resolver): void
    {
        unset($this->resolved[$engine]);
        $this->resolvers[$engine] = $resolver;
    }

    /**
     * Resolve an engine instance.
     */
    public function resolve(string $engine): EngineInterface
    {
        if (isset($this->resolved[$engine])) {
            return $this->resolved[$engine];
        }

        if (!isset($this->resolvers[$engine])) {
            throw new ViewException("Engine [{$engine}] not found.");
        }

        $engineInstance = $this->resolvers[$engine]();

        if (!$engineInstance instanceof EngineInterface) {
            throw new ViewException("Engine [{$engine}] must implement EngineInterface.");
        }

        return $this->resolved[$engine] = $engineInstance;
    }

    /**
     * Get all registered engines.
     */
    public function getEngines(): array
    {
        return array_keys($this->resolvers);
    }

    /**
     * Check if an engine is registered.
     */
    public function hasEngine(string $engine): bool
    {
        return isset($this->resolvers[$engine]);
    }

    /**
     * Remove an engine resolver.
     */
    public function forget(string $engine): void
    {
        unset($this->resolvers[$engine], $this->resolved[$engine]);
    }

    /**
     * Register default engine resolvers.
     */
    protected function registerDefaultResolvers(): void
    {
        // Register PHP engine (for .php files)
        $this->register('php', function () {
            return new PhpEngine();
        });

        // Register Plugs engine (for .plug.php files)
        $this->register('plugs', function () {
            return new PlugsEngine();
        });

        // Register File engine (for static files like .html)
        $this->register('file', function () {
            return new FileEngine();
        });
    }

    /**
     * Get the appropriate engine for a given file path.
     */
    public function getEngineFromPath(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return match ($extension) {
            'php' => str_ends_with($path, '.plug.php') ? 'plugs' : 'php',
            'html', 'htm' => 'file',
            default => 'plugs'
        };
    }

    /**
     * Clear all resolved engine instances.
     */
    public function clearResolved(): void
    {
        $this->resolved = [];
    }
}