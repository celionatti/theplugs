<?php

declare(strict_types=1);

namespace Plugs\Illusion\Helper;

use Plugs\Session\SessionManager;

class CsrfProtection
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function generateToken(): string
    {
        return $this->session->token();
    }

    public function verifyToken(string $token): bool
    {
        $sessionToken = $this->session->get('_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }
}