<?php

declare(strict_types=1);

namespace Illusion\Upload;

class UploadResult
{
    private array $files;

    public function __construct(array $files)
    {
        $this->files = $files;
    }

    /**
     * Get all uploaded files info
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Get first uploaded file info
     */
    public function getFile(): ?array
    {
        return $this->files[0] ?? null;
    }

    /**
     * Get file paths only
     */
    public function getPaths(): array
    {
        return array_column($this->files, 'path');
    }

    /**
     * Get first file path
     */
    public function getPath(): ?string
    {
        return $this->files[0]['path'] ?? null;
    }

    /**
     * Get count of uploaded files
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Check if upload was successful
     */
    public function isSuccess(): bool
    {
        return !empty($this->files);
    }
}