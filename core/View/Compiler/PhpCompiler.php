<?php

declare(strict_types=1);

namespace Plugs\View\Compiler;

class PhpCompiler extends BaseCompiler
{
    public function compile(string $path): string
    {
        // For plain PHP files, we don't need compilation
        // Just return the original path
        return $path;
    }

    public function isExpired(string $path, string $compiled): bool
    {
        // Plain PHP files are never expired
        return false;
    }

    public function getCompiledPath(string $path): string
    {
        // Return the original path for PHP files
        return $path;
    }
}