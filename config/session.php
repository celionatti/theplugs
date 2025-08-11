<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "file", "database", "array"
    |
    */
    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    */
    'cookie' => env('SESSION_COOKIE', 'plugs_session'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires.
    |
    */
    'lifetime' => env('SESSION_LIFETIME', 120),

    /*
    |--------------------------------------------------------------------------
    | Expire On Close
    |--------------------------------------------------------------------------
    |
    | If this option is set to true, the session will expire when the browser
    | is closed instead of using the lifetime setting.
    |
    */
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify that all session data should be
    | encrypted before being stored.
    |
    */
    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the encryption service and should be set
    | to a random, 32 character string.
    |
    */
    'key' => env('SESSION_KEY', env('APP_KEY', 'your-secret-key-here')),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    */
    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies
    |--------------------------------------------------------------------------
    */
    'secure' => env('SESSION_SECURE', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie.
    |
    */
    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place, and can be used to mitigate CSRF attacks.
    |
    | Supported: "Lax", "Strict", "None", or null
    |
    */
    'same_site' => env('SESSION_SAME_SITE', 'Lax'),

    /*
    |--------------------------------------------------------------------------
    | Security Checks
    |--------------------------------------------------------------------------
    |
    | These options enable additional security checks on the session.
    |
    */
    'check_ip' => env('SESSION_CHECK_IP', false),
    'check_user_agent' => env('SESSION_CHECK_USER_AGENT', false),

    /*
    |--------------------------------------------------------------------------
    | Auto Start Session
    |--------------------------------------------------------------------------
    |
    | Whether to automatically start the session when the service provider boots.
    |
    */
    'auto_start' => env('SESSION_AUTO_START', true),

    /*
    |--------------------------------------------------------------------------
    | Session Driver Configurations
    |--------------------------------------------------------------------------
    */
    'file' => [
        'path' => env('SESSION_FILE_PATH', storage_path('framework/sessions')),
    ],

    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'database' => env('DB_DATABASE', 'plugs'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'table' => env('SESSION_DB_TABLE', 'sessions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Cipher
    |--------------------------------------------------------------------------
    |
    | The cipher used for session data encryption.
    |
    */
    'cipher' => env('SESSION_CIPHER', 'AES-256-CBC'),
];