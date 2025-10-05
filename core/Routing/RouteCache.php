<?php

declare(strict_types=1);

namespace Plugs\Routing;

class RouteCache
{
    private string|null $cacheFile = null;

    public function __construct(string|null $cacheFile)
    {
        $this->cacheFile = $cacheFile ?: ROOT_PATH . '/cache/routes.php';
    }

    public function isCached(): bool
    {
        return file_exists($this->cacheFile);
    }

    public function cache(RouteCollection $routes): void
    {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $serialized = serialize($routes);
        file_put_contents($this->cacheFile, "<?php\nreturn unserialize(" . var_export($serialized, true) . ");");
    }

    public function load(): RouteCollection
    {
        if (!$this->isCached()) {
            throw new \RuntimeException('Route cache file does not exist');
        }

        return require $this->cacheFile;
    }

    public function clear(): void
    {
        if ($this->isCached()) {
            unlink($this->cacheFile);
        }
    }
}