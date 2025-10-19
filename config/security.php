<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Security Configuration File
|--------------------------------------------------------------------------
|
| This file contains security-related settings for the application,
| including CSRF protection, security headers, content security policy,
| rate limiting, CORS, and session security.
*/

return [
    // CSRF Protection
    'csrf' => [
        'enabled' => true,
        'except' => [
            '#^/api/#',
        ],
    ],

    // Security Headers
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    // Content Security Policy
    'csp' => [
        'enabled' => false,
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'", 'cdn.tailwindcss.com'],
        'style-src' => ["'self'", "'unsafe-inline'", 'cdn.jsdelivr.net'],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
    ],

    // Rate Limiting
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 60,
        'per_minutes' => 1,
    ],

    // CORS
    'cors' => [
        'enabled' => false,
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'max_age' => 86400,
    ],

    // Session Security
    'session' => [
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
        'lifetime' => 120,
    ],
];
