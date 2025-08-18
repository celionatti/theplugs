<?php

declare(strict_types=1);

namespace Plugs\Authentication;

class AuthConfig
{
    public function __construct(
        public readonly string $authMethod = 'session', // 'session' or 'jwt'
        public readonly bool $requireEmailVerification = true,
        public readonly int $maxLoginAttempts = 5,
        public readonly int $lockoutDuration = 15, // minutes
        public readonly string $jwtSecret = '',
        public readonly int $jwtExpiry = 3600, // seconds
        public readonly string $appName = 'ThePlugs',
        public readonly string $appKey = '', // For session encryption
        public readonly array $passwordRequirements = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => true
        ],
        // Session-specific configuration for your SessionManager
        public readonly string $sessionDriver = 'file', // 'file', 'database', 'array'
        public readonly string $sessionCookieName = 'app_session',
        public readonly int $sessionLifetime = 120, // minutes
        public readonly int $sessionTimeout = 7200, // seconds for security timeout
        public readonly bool $expireOnClose = false,
        public readonly string $sessionPath = '/',
        public readonly string $sessionDomain = '',
        public readonly bool $secureCookies = false, // Will be auto-detected if not set
        public readonly string $sameSite = 'Lax',
        public readonly bool $encryptSession = false,
        public readonly string $sessionKey = '', // For session encryption
        public readonly bool $checkClientIp = true,
        public readonly bool $checkUserAgent = true,
        public readonly string $sessionTable = 'sessions', // For database driver
        public readonly string $sessionFilePath = '', // For file driver
        // Mailer configuration
        public readonly string $appUrl = 'https://yoursite.com',
        public readonly string $mailFrom = 'noreply@yoursite.com',
        public readonly string $mailReplyTo = 'support@yoursite.com',
        public readonly string $mailDriver = 'smtp', // 'smtp', 'sendmail', 'mailgun', etc.
        public readonly string $smtpHost = 'smtp.example.com',
        public readonly int $smtpPort = 587,
        public readonly string $smtpUsername = '',
        public readonly string $smtpPassword = '',
        public readonly string $smtpEncryption = 'tls', // 'tls' or 'ssl'
    ) {}
}
