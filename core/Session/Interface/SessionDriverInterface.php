<?php

declare(strict_types=1);

namespace Plugs\Session\Interface;

interface SessionDriverInterface
{
    /**
     * Read session data
     */
    public function read(string $sessionId): string;

    /**
     * Write session data
     */
    public function write(string $sessionId, string $data): bool;

    /**
     * Destroy session
     */
    public function destroy(string $sessionId): bool;

    /**
     * Garbage collection
     */
    public function gc(int $maxLifetime): bool;

    /**
     * Check if session exists
     */
    public function exists(string $sessionId): bool;
}