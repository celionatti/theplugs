<?php

declare(strict_types=1);

namespace Plugs;

use RuntimeException;
use InvalidArgumentException;

/**
 * Configuration management class for the framework
 * 
 * Provides methods to load, get, set, and manage configuration values
 * with support for dot notation and caching for performance.
 */
class Config
{
    /**
     * Cached configuration data
     */
    private static array $config = [];

    /**
     * Base path for configuration files
     */
    private static string $configPath = '';

    /**
     * Track which files have been loaded
     */
    private static array $loadedFiles = [];

    /**
     * Initialize the configuration system
     */
    public static function initialize(string $configPath): void
    {
        self::$configPath = rtrim($configPath, '/');
        
        if (!is_dir(self::$configPath)) {
            throw new InvalidArgumentException("Configuration directory does not exist: " . self::$configPath);
        }
    }

    /**
     * Get a configuration value using dot notation
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Parse the key to extract file and nested keys
        $segments = explode('.', $key);
        $file = array_shift($segments);

        // Load the config file if not already loaded
        if (!isset(self::$loadedFiles[$file])) {
            self::load($file);
        }

        // Start with the file's config data
        $value = self::$config[$file] ?? null;

        // Navigate through nested keys
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value ?? $default;
    }

    /**
     * Set a configuration value using dot notation
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        // Ensure the file config exists
        if (!isset(self::$config[$file])) {
            self::$config[$file] = [];
        }

        // Navigate to the correct position and set the value
        $current = &self::$config[$file];
        
        foreach ($segments as $segment) {
            if (!is_array($current)) {
                $current = [];
            }
            
            if (!array_key_exists($segment, $current)) {
                $current[$segment] = [];
            }
            
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * Check if a configuration key exists
     */
    public static function has(string $key): bool
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        // Load the config file if not already loaded
        if (!isset(self::$loadedFiles[$file])) {
            self::load($file);
        }

        // Check if the file exists in config
        if (!array_key_exists($file, self::$config)) {
            return false;
        }

        $current = self::$config[$file];

        // Navigate through nested keys
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Get all configuration data
     */
    public static function all(): array
    {
        // Load all config files in the directory
        self::loadAll();
        
        return self::$config;
    }

    /**
     * Load a specific configuration file
     */
    public static function load(string $file): void
    {
        if (empty(self::$configPath)) {
            throw new RuntimeException("Configuration system not initialized. Call Config::initialize() first.");
        }

        $filePath = self::$configPath . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            // If file doesn't exist, set empty array and mark as loaded
            self::$config[$file] = [];
            self::$loadedFiles[$file] = true;
            return;
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("Configuration file is not readable: " . $filePath);
        }

        $config = require $filePath;

        if (!is_array($config)) {
            throw new RuntimeException("Configuration file must return an array: " . $filePath);
        }

        self::$config[$file] = $config;
        self::$loadedFiles[$file] = true;
    }

    /**
     * Load all configuration files from the config directory
     */
    public static function loadAll(): void
    {
        if (empty(self::$configPath)) {
            throw new RuntimeException("Configuration system not initialized. Call Config::initialize() first.");
        }

        $files = glob(self::$configPath . '/*.php');
        
        if ($files === false) {
            throw new RuntimeException("Failed to scan configuration directory: " . self::$configPath);
        }

        foreach ($files as $filePath) {
            $fileName = basename($filePath, '.php');
            
            if (!isset(self::$loadedFiles[$fileName])) {
                self::load($fileName);
            }
        }
    }

    /**
     * Reload configuration data (clears cache and reloads files)
     */
    public static function reload(?string $file = null): void
    {
        if ($file !== null) {
            // Reload specific file
            unset(self::$loadedFiles[$file]);
            self::load($file);
        } else {
            // Reload all files
            self::$config = [];
            self::$loadedFiles = [];
            self::loadAll();
        }
    }

    /**
     * Clear all cached configuration data
     */
    public static function clear(): void
    {
        self::$config = [];
        self::$loadedFiles = [];
    }

    /**
     * Get the list of loaded configuration files
     */
    public static function getLoadedFiles(): array
    {
        return array_keys(self::$loadedFiles);
    }

    /**
     * Get configuration for a specific file
     */
    public static function getFile(string $file): array
    {
        if (!isset(self::$loadedFiles[$file])) {
            self::load($file);
        }

        return self::$config[$file] ?? [];
    }

    /**
     * Check if a specific configuration file has been loaded
     */
    public static function isFileLoaded(string $file): bool
    {
        return isset(self::$loadedFiles[$file]);
    }

    /**
     * Merge configuration arrays (useful for environment-specific overrides)
     */
    public static function merge(string $file, array $config): void
    {
        if (!isset(self::$config[$file])) {
            self::$config[$file] = [];
        }

        self::$config[$file] = array_merge_recursive(self::$config[$file], $config);
        self::$loadedFiles[$file] = true;
    }
}