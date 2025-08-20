<?php

declare(strict_types=1);

namespace Plugs\Console\Support;

class Str
{
    public static function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }

    public static function snake(string $name): string
    {
        $name = preg_replace('/(.)([A-Z])/u', '$1_$2', $name);
        return strtolower((string)$name);
    }
}