<?php

declare(strict_types=1);

namespace Plugs\Routing;

use Plugs\Http\Router\Router;

/**
 * Manages route groups with shared attributes
 */
class RouteGroup
{
    private array $attributes;
    private Router $router;

    /**
     * @param Router $router Router instance
     * @param array $attributes Group attributes (prefix, middleware, namespace, domain)
     */
    public function __construct(Router $router, array $attributes = [])
    {
        $this->router = $router;
        $this->attributes = $attributes;
    }

    /**
     * Execute the group callback with merged attributes
     * 
     * @param callable $callback Callback to register routes
     * @return void
     */
    public function group(callable $callback): void
    {
        // Store current group attributes
        $previousAttributes = $this->router->getCurrentGroupAttributes();

        // Merge with current attributes
        $mergedAttributes = $this->mergeAttributes($previousAttributes, $this->attributes);
        $this->router->setCurrentGroupAttributes($mergedAttributes);

        // Execute the group callback
        $callback($this->router);

        // Restore previous attributes
        $this->router->setCurrentGroupAttributes($previousAttributes);
    }

    /**
     * Merge parent and current group attributes
     * 
     * @param array $parent Parent group attributes
     * @param array $new New group attributes
     * @return array Merged attributes
     */
    private function mergeAttributes(array $parent, array $new): array
    {
        $merged = $parent;

        // Merge middleware arrays (parent middleware comes first)
        if (isset($new['middleware'])) {
            $newMiddleware = is_array($new['middleware'])
                ? $new['middleware']
                : [$new['middleware']];

            $merged['middleware'] = array_merge(
                $parent['middleware'] ?? [],
                $newMiddleware
            );
        }

        // Concatenate prefixes
        if (isset($new['prefix'])) {
            $parentPrefix = $parent['prefix'] ?? '';
            $newPrefix = $new['prefix'];

            // Normalize slashes
            $merged['prefix'] = rtrim($parentPrefix, '/') . '/' . ltrim($newPrefix, '/');
            $merged['prefix'] = rtrim($merged['prefix'], '/');
        }

        // Concatenate namespaces with backslash separator
        if (isset($new['namespace'])) {
            if (isset($parent['namespace'])) {
                $merged['namespace'] = rtrim($parent['namespace'], '\\')
                    . '\\'
                    . ltrim($new['namespace'], '\\');
            } else {
                $merged['namespace'] = $new['namespace'];
            }
        }

        // Domain (new overrides parent)
        if (isset($new['domain'])) {
            $merged['domain'] = $new['domain'];
        }

        return $merged;
    }

    /**
     * Add another group level with additional attributes
     * 
     * @param array $attributes Additional attributes
     * @param callable $callback Callback to register routes
     * @return void
     */
    public function nest(array $attributes, callable $callback): void
    {
        $nestedGroup = new self($this->router, array_merge($this->attributes, $attributes));
        $nestedGroup->group($callback);
    }

    /**
     * Get the router instance
     * 
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the group attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
