<?php

declare(strict_types=1);

namespace Plugs\Session\Driver;

use Plugs\Session\Interface\SessionDriverInterface;

class FileSessionDriver implements SessionDriverInterface
{
    private string $path;

    public function __construct(array $config)
    {
        $this->path = $config['file']['path'] ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sessions';

        // Normalize path separators and remove trailing separator
        $this->path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->path), DIRECTORY_SEPARATOR);

        // Ensure the directory exists and is writable
        if (!is_dir($this->path)) {
            if (!@mkdir($this->path, 0755, true)) {
                throw new \RuntimeException("Failed to create session directory: {$this->path}");
            }
        }

        // Verify the directory is writable
        if (!is_writable($this->path)) {
            throw new \RuntimeException("Session directory is not writable: {$this->path}");
        }
    }

    public function read(string $sessionId): string
    {
        $sessionId = $this->sanitizeSessionId($sessionId);
        $file = $this->path . DIRECTORY_SEPARATOR . $sessionId;
        return file_exists($file) ? (string)file_get_contents($file) : '';
    }

    public function write(string $sessionId, string $data): bool
    {
        $sessionId = $this->sanitizeSessionId($sessionId);
        $file = $this->path . DIRECTORY_SEPARATOR . $sessionId;
        $tempFile = $this->path . DIRECTORY_SEPARATOR . 'temp_' . $sessionId . '_' . uniqid();

        // Ensure directory still exists (in case it was deleted)
        if (!is_dir($this->path)) {
            if (!@mkdir($this->path, 0755, true)) {
                return false;
            }
        }

        // Write to temporary file first with error handling
        $result = @file_put_contents($tempFile, $data, LOCK_EX);
        if ($result === false) {
            return false;
        }

        // Then rename to target file
        if (!@rename($tempFile, $file)) {
            @unlink($tempFile);
            return false;
        }

        return true;
    }

    public function destroy(string $sessionId): bool
    {
        $sessionId = $this->sanitizeSessionId($sessionId);
        $file = $this->path . DIRECTORY_SEPARATOR . $sessionId;
        return !file_exists($file) || @unlink($file);
    }

    public function gc(int $maxLifetime): bool
    {
        $pattern = $this->path . DIRECTORY_SEPARATOR . '*';
        $files = @glob($pattern);

        if ($files === false) {
            return false;
        }

        $now = time();

        foreach ($files as $file) {
            // Skip temporary files and directories
            if (is_dir($file) || strpos(basename($file), 'temp_') === 0) {
                continue;
            }

            $mtime = @filemtime($file);
            if ($mtime !== false && ($mtime + $maxLifetime < $now)) {
                @unlink($file);
            }
        }

        // Also clean up old temporary files (older than 1 hour)
        $tempFiles = @glob($this->path . DIRECTORY_SEPARATOR . 'temp_*');
        if ($tempFiles !== false) {
            foreach ($tempFiles as $tempFile) {
                $mtime = @filemtime($tempFile);
                if ($mtime !== false && ($mtime + 3600 < $now)) {
                    @unlink($tempFile);
                }
            }
        }

        return true;
    }

    public function exists(string $sessionId): bool
    {
        $sessionId = $this->sanitizeSessionId($sessionId);
        return file_exists($this->path . DIRECTORY_SEPARATOR . $sessionId);
    }

    /**
     * Sanitize session ID to prevent directory traversal
     */
    private function sanitizeSessionId(string $sessionId): string
    {
        // Remove any path separators and other potentially dangerous characters
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
    }
}
