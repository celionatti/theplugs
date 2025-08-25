<?php

declare(strict_types=1);

namespace Plugs\View\Compiler;

use Plugs\View\Contracts\CompilerInterface;

abstract class BaseCompiler implements CompilerInterface
{
    protected string $cachePath;

    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }

    public function getCompiledPath(string $path): string
    {
        return $this->cachePath . DIRECTORY_SEPARATOR . sha1($path) . '.php';
    }

    public function isExpired(string $path, string $compiled): bool
    {
        if (!file_exists($compiled)) {
            return true;
        }

        return filemtime($path) >= filemtime($compiled);
    }

    abstract public function compile(string $path): string;

    protected function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }
}
