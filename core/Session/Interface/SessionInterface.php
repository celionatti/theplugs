<?php

declare(strict_types=1);

namespace Plugs\Session\Interface;

interface SessionInterface
{
    /**
     * Start the session
     */
    public function start(): bool;

    /**
     * Check if session is started
     */
    public function isStarted(): bool;

    /**
     * Store a value in the session
     */
    public function put(string $key, mixed $value): void;

    /**
     * Get a value from the session
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a key exists in the session
     */
    public function has(string $key): bool;

    /**
     * Remove one or more values from the session
     */
    public function forget(string|array $keys): void;

    /**
     * Get all session data
     */
    public function all(): array;

    /**
     * Store flash data for the next request
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Keep all flash data for another request
     */
    public function reflash(): void;

    /**
     * Clear all session data
     */
    public function clear(): void;

    /**
     * Destroy the session and regenerate ID
     */
    public function invalidate(): bool;

    /**
     * Regenerate the session ID
     */
    public function regenerate(): bool;

    /**
     * Get or generate CSRF token
     */
    public function token(): string;

    /**
     * Get session ID
     */
    public function getId(): ?string;

    /**
     * Get previous URL from session
     */
    public function previousUrl(): ?string;

    /**
     * Set previous URL in session
     */
    public function setPreviousUrl(string $url): void;
}