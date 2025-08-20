<?php

declare(strict_types=1);

/**
 * * Plugs Framework
 * * @package Plugs
 * * @author Plugs Team
 * * This file is part of the Plugs Framework.
 * * For the full copyright and license information, please view the LICENSE
 */

if (!function_exists('app_path')) {
    /**
     * Get the path to the application directory.
     *
     * @param string $path
     * @return string
     */
    function app_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->basePath('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base directory.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->basePath($path);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the path to the configuration directory.
     *
     * @param string $path
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->configPath($path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory.
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->storagePath($path);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources directory.
     *
     * @param string $path
     * @return string
     */
    function resource_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->basePath('resources' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public directory.
     *
     * @param string $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->basePath('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('routes_path')) {
    /**
     * Get the path to the routes directory.
     *
     * @param string $path
     * @return string
     */
    function routes_path(string $path = ''): string
    {
        return \Plugs\Plugs::getInstance()->routesPath($path);
    }
}