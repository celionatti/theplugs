<?php

declare(strict_types=1);

namespace Plugs\Console\Support;

class Filesystem
{
    public static function put(string $path, string $contents): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($path, $contents);
    }

    public static function ensureDir(string $path): void
    {
        if (!is_dir($path)) mkdir($path, 0777, true);
    }
}