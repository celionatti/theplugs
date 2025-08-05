<?php

declare(strict_types=1);

namespace Plugs\Routing;

use Plugs\Http\Router\Router;

class RouteGroup
{
    private array $attributes;
    private Router $router;

    public function __construct(Router $router, array $attributes = [])
    {
        $this->router = $router;
        $this->attributes = $attributes;
    }

    public function group(callable $callback): void
    {
        // Store current group attributes
        $previousAttributes = $this->router->getCurrentGroupAttributes();
        
        // Merge with current attributes
        $this->router->setCurrentGroupAttributes(
            $this->mergeAttributes($previousAttributes, $this->attributes)
        );

        // Execute the group callback
        $callback($this->router);

        // Restore previous attributes
        $this->router->setCurrentGroupAttributes($previousAttributes);
    }

    private function mergeAttributes(array $current, array $new): array
    {
        $merged = $current;

        // Merge middleware arrays
        if (isset($new['middleware'])) {
            $merged['middleware'] = array_merge(
                $current['middleware'] ?? [],
                is_array($new['middleware']) ? $new['middleware'] : [$new['middleware']]
            );
        }

        // Concatenate prefixes
        if (isset($new['prefix'])) {
            $merged['prefix'] = ($current['prefix'] ?? '') . '/' . ltrim($new['prefix'], '/');
        }

        // Set namespace (new overrides current)
        if (isset($new['namespace'])) {
            $merged['namespace'] = $new['namespace'];
        }

        // Set domain (new overrides current)
        if (isset($new['domain'])) {
            $merged['domain'] = $new['domain'];
        }

        return $merged;
    }
}