<?php

declare(strict_types=1);

/**
 * The Plugs Framework
 *
 * @package ThePlugs
 * @author  ThePlugs Team
 * @license https://opensource.org/licenses/MIT MIT License
 */

if (!function_exists('file_get_contents_safe')) {
    /**
     * Get file contents with error handling.
     *
     * @param string $filename
     * @param mixed $default
     * @return mixed
     */
    function file_get_contents_safe(string $filename, mixed $default = null): mixed
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return $default;
        }

        $contents = file_get_contents($filename);
        return $contents !== false ? $contents : $default;
    }
}

if (!function_exists('ensure_directory_exists')) {
    /**
     * Ensure a directory exists, create it if it doesn't.
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     */
    function ensure_directory_exists(string $path, int $permissions = 0755): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, $permissions, true);
    }
}