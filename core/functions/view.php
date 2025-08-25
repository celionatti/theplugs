<?php

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     */
    function view(?string $view = null, array $data = [], array $mergeData = []): mixed
    {
        $factory = app('view');

        if (func_num_args() === 0) {
            return $factory;
        }

        $data = array_merge($mergeData, $data);

        return $factory->make($view, $data);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     */
    function resource_path(string $path = ''): string
    {
        return app()->resourcePath($path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     */
    function storage_path(string $path = ''): string
    {
        return app()->storagePath($path);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL.
     */
    function asset(string $path): string
    {
        $app = app();
        $assetPath = rtrim($app->config('view.asset_path', '/assets'), '/');
        $path = ltrim($path, '/');
        
        return $app->urlPath() . $assetPath . '/' . $path;
    }
}

// if (!function_exists('url')) {
//     /**
//      * Generate a URL for the application.
//      */
//     function url(string $path = ''): string
//     {
//         $app = app();
//         return $app->urlPath($path);
//     }
// }

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route.
     */
    function route(string $name, array $parameters = []): string
    {
        $router = app('router');
        return $router->url($name, $parameters);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token.
     */
    function csrf_token(): string
    {
        if (function_exists('session') && session()) {
            return session()->token() ?? '';
        }
        
        return '';
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token field.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Generate a method field for forms.
     */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Get old input data.
     */
    function old(string $key, mixed $default = null): mixed
    {
        if (function_exists('session') && session()) {
            return session()->getOldInput($key, $default);
        }
        
        return $default;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     */
    function e(mixed $value, bool $doubleEncode = true): string
    {
        if ($value === null) {
            return '';
        }
        
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the auth instance or check if user is authenticated.
     */
    function auth(?string $guard = null): mixed
    {
        if (app()->bound('auth')) {
            $auth = app('auth');
            return $guard ? $auth->guard($guard) : $auth;
        }
        
        return null;
    }
}

if (!function_exists('trans')) {
    /**
     * Translate the given message.
     */
    function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        if (app()->bound('translator')) {
            return app('translator')->get($key, $replace, $locale);
        }
        
        // Fallback: return key if no translator is available
        $message = $key;
        foreach ($replace as $search => $replacement) {
            $message = str_replace(":$search", $replacement, $message);
        }
        
        return $message;
    }
}

if (!function_exists('__')) {
    /**
     * Translate the given message (alias for trans).
     */
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return trans($key, $replace, $locale);
    }
}

if (!function_exists('json_encode_safe')) {
    /**
     * Safely encode data as JSON for use in HTML.
     */
    function json_encode_safe(mixed $data, int $flags = 0): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | $flags);
    }
}

if (!function_exists('mix')) {
    /**
     * Get the path to a versioned Mix file.
     */
    function mix(string $path, string $manifestDirectory = ''): string
    {
        static $manifests = [];
        
        $publicPath = app()->publicPath();
        $manifestPath = $publicPath . ($manifestDirectory ? "/$manifestDirectory" : '') . '/mix-manifest.json';
        
        if (!isset($manifests[$manifestPath])) {
            if (!file_exists($manifestPath)) {
                // If no manifest, return the original path
                return asset($path);
            }
            
            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }
        
        $manifest = $manifests[$manifestPath];
        
        if (!array_key_exists($path, $manifest)) {
            return asset($path);
        }
        
        return asset($manifest[$path]);
    }
}

if (!function_exists('vite')) {
    /**
     * Generate Vite asset URLs.
     */
    function vite(array|string $entrypoints, ?string $buildDirectory = null): string
    {
        // This is a placeholder for Vite integration
        // You would implement actual Vite manifest reading here
        $entrypoints = is_array($entrypoints) ? $entrypoints : [$entrypoints];
        $assets = [];
        
        foreach ($entrypoints as $entrypoint) {
            $assets[] = '<script type="module" src="' . asset($entrypoint) . '"></script>';
        }
        
        return implode("\n", $assets);
    }
}

if (!function_exists('include_view')) {
    /**
     * Include a sub-view within a template.
     */
    function include_view(string $view, array $data = []): string
    {
        return view($view, $data);
    }
}

if (!function_exists('render_component')) {
    /**
     * Render a view component.
     */
    function render_component(string $component, array $props = []): string
    {
        // This would integrate with a component system
        return view("components.$component", $props);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date for display.
     */
    function format_date(mixed $date, string $format = 'Y-m-d H:i:s'): string
    {
        if ($date === null) {
            return '';
        }
        
        if (is_string($date)) {
            $date = new DateTime($date);
        } elseif (is_numeric($date)) {
            $date = new DateTime('@' . $date);
        }
        
        if (!($date instanceof DateTime)) {
            return '';
        }
        
        return $date->format($format);
    }
}

if (!function_exists('format_money')) {
    /**
     * Format a number as money.
     */
    function format_money(float $amount, string $currency = '$', int $decimals = 2): string
    {
        return $currency . number_format($amount, $decimals);
    }
}

if (!function_exists('truncate')) {
    /**
     * Truncate a string to a specified length.
     */
    function truncate(string $string, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - strlen($suffix)) . $suffix;
    }
}