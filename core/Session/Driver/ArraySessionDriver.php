<?php

declare(strict_types=1);

namespace Plugs\Session\Driver;

use Plugs\Session\Interface\SessionDriverInterface;

class ArraySessionDriver implements SessionDriverInterface
{
    private static array $sessions = [];

    public function read(string $sessionId): string
    {
        return self::$sessions[$sessionId]['data'] ?? '';
    }

    public function write(string $sessionId, string $data): bool
    {
        self::$sessions[$sessionId] = [
            'data' => $data,
            'timestamp' => time()
        ];
        return true;
    }

    public function destroy(string $sessionId): bool
    {
        unset(self::$sessions[$sessionId]);
        return true;
    }

    public function gc(int $maxLifetime): bool
    {
        $now = time();
        foreach (self::$sessions as $id => $session) {
            if ($session['timestamp'] + $maxLifetime < $now) {
                unset(self::$sessions[$id]);
            }
        }
        return true;
    }

    public function exists(string $sessionId): bool
    {
        return isset(self::$sessions[$sessionId]);
    }

    public static function clear(): void
    {
        self::$sessions = [];
    }
}
