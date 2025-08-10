<?php

declare(strict_types=1);

namespace Plugs\Session\Driver;

use Plugs\Session\Interface\SessionDriverInterface;

class FileSessionDriver implements SessionDriverInterface
{
    private string $path;

    public function __construct(array $config)
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/sessions';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function read(string $sessionId): string
    {
        $file = $this->path . '/' . $sessionId;
        return file_exists($file) ? file_get_contents($file) : '';
    }

    public function write(string $sessionId, string $data): bool
    {
        $file = $this->path . '/' . $sessionId;
        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    public function destroy(string $sessionId): bool
    {
        $file = $this->path . '/' . $sessionId;
        return !file_exists($file) || unlink($file);
    }

    public function gc(int $maxLifetime): bool
    {
        $files = glob($this->path . '/*');
        $now = time();
        
        foreach ($files as $file) {
            if (filemtime($file) + $maxLifetime < $now) {
                unlink($file);
            }
        }
        
        return true;
    }

    public function exists(string $sessionId): bool
    {
        return file_exists($this->path . '/' . $sessionId);
    }
}