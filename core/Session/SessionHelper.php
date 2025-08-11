<?php

declare(strict_types=1);

namespace Plugs\Session;

use Plugs\Session\Interface\SessionInterface;

class SessionHelper
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Get session value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    /**
     * Put session value
     */
    public function put(string $key, mixed $value): void
    {
        $this->session->put($key, $value);
    }

    /**
     * Flash session value
     */
    public function flash(string $key, mixed $value): void
    {
        $this->session->flash($key, $value);
    }

    /**
     * Get CSRF token
     */
    public function token(): string
    {
        return $this->session->token();
    }

    /**
     * Check if session has key
     */
    public function has(string $key): bool
    {
        return $this->session->has($key);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        return $this->session->all();
    }
}