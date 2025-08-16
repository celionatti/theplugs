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
        public readonly string $appName = 'ThePlugs Framework',
        public readonly array $passwordRequirements = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => true
        ]
    ) {}
}