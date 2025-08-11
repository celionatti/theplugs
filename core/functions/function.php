<?php

declare(strict_types=1);

use Plugs\View\View;
use Plugs\Database\Eloquent\Collection;

if(!function_exists("env")) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory.
     *
     * @param  string|null  $path
     * @return string
     */
    function storage_path(?string $path = null): string
    {
        // Assuming your framework has a BASE_PATH constant or similar
        $storageBase = rtrim(ROOT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'storage';

        return $path
            ? $storageBase . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR)
            : $storageBase;
    }
}


if (!function_exists("view")) {
    function view(string $template, array $data = []): View
    {
        return View::make($template, $data);
    }
}

if (!function_exists('collect')) {
    function collect($value = null) {
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
