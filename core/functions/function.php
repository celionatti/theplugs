<?php

declare(strict_types=1);

use Plugs\View\View;
use Plugs\Database\Eloquent\Collection;

if (!function_exists('env')) {
    /**
     * Get an environment variable value with optional default and type casting.
     *
     * @param string $key The environment variable key
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the application.
     *
     * @param string $path
     * @param array $parameters
     * @param bool|null $secure
     * @return string
     */
    function url(string $path = '', array $parameters = [], ?bool $secure = null): string
    {
        $app = \Plugs\Plugs::getInstance();
        $baseUrl = config('app.url', 'http://localhost');
        $urlPath = $app->urlPath($path);

        $url = rtrim($baseUrl, '/') . '/' . ltrim($urlPath, '/');

        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for an asset.
     *
     * @param string $path
     * @param bool|null $secure
     * @return string
     */
    function asset(string $path, ?bool $secure = null): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance or resolve a binding.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\Plugs\Plugs
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        $app = \Plugs\Plugs::getInstance();

        if ($abstract === null) {
            return $app;
        }

        return $app->container->make($abstract, $parameters);
    }
}

if (!function_exists('resolve')) {
    /**
     * Resolve a service from the container.
     *
     * @param string $name
     * @param array $parameters
     * @return mixed
     */
    function resolve(string $name, array $parameters = []): mixed
    {
        return app($name, $parameters);
    }
}

if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return mixed
     */
    function session(array|string|null $key = null, mixed $default = null): mixed
    {
        if (!session_id()) {
            session_start();
        }

        if ($key === null) {
            return $_SESSION;
        }

        if (is_array($key)) {
            foreach ($key as $sessionKey => $sessionValue) {
                $_SESSION[$sessionKey] = $sessionValue;
            }
            return null;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('collect')) {
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object/class.
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('cache')) {
    /**
     * Get / set the specified cache value.
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return mixed
     */
    function cache(array|string|null $key = null, mixed $default = null): mixed
    {
        // This would need to be implemented based on your cache implementation
        // For now, using a simple file-based cache
        static $cache = [];

        if ($key === null) {
            return $cache;
        }

        if (is_array($key)) {
            foreach ($key as $cacheKey => $cacheValue) {
                $cache[$cacheKey] = $cacheValue;
            }
            return null;
        }

        return $cache[$key] ?? $default;
    }
}
