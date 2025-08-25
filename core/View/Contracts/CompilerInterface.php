<?php

declare(strict_types=1);

namespace Plugs\View\Contracts;

interface CompilerInterface
{
    public function compile(string $path): string;
    public function isExpired(string $path, string $compiled): bool;
    public function getCompiledPath(string $path): string;
}