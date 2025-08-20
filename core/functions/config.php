<?php

declare(strict_types=1);

/**
 * The Plugs Framework
 *
 * @package ThePlugs
 * @author  ThePlugs Team
 * @license https://opensource.org/licenses/MIT MIT License
 */

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return mixed
     */
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return \Plugs\Config::all();
        }
        
        if (is_array($key)) {
            // Setting multiple config values
            foreach ($key as $configKey => $configValue) {
                \Plugs\Config::set($configKey, $configValue);
            }
            return null;
        }
        
        return \Plugs\Config::get($key, $default);
    }
}