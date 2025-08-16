<?php

declare(strict_types=1);

namespace Plugs\Authentication;

use Plugs\Authentication\Interface\UserInterface;

class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?UserInterface $user = null,
        public readonly array $errors = [],
        public readonly ?string $status = null,
        public readonly ?string $token = null
    ) {}
}