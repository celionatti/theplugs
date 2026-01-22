<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| App Configuration File
|--------------------------------------------------------------------------
|
| This file is used to configure various settings for the application,
| such as the application name, environment, debug mode, and paths.
*/

return [
    'name' => $_ENV['APP_NAME'] ?? 'My Plugs App',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => $_ENV['APP_DEBUG'] ?? true,
    'url' => $_ENV['APP_URL'] ?? 'http://theplugs.local',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Africa/Lagos',
    
    'key' => $_ENV['APP_KEY'] ?? null,
    
    'paths' => [
        'views' => BASE_PATH . 'views',
        'cache' => BASE_PATH . 'storage/cache',
        'logs' => BASE_PATH . 'storage/logs',
        'storage' => BASE_PATH . 'storage',
    ],
    'required_files' => [
        'function' => BASE_PATH . 'utils/function.php'
    ],
];
