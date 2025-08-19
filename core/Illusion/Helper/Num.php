<?php

declare(strict_types=1);

namespace Illusion\Helper;

use InvalidArgumentException;

final class Num
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function format(int|float $number, int $decimals = 2): string
    {
        if ($decimals < 0) {
            throw new InvalidArgumentException('Decimals must be a non-negative integer');
        }

        return number_format($number, $decimals, '.', ',');
    }

    public static function bytesToHuman(int $bytes): string
    {
        if ($bytes < 0) {
            throw new InvalidArgumentException('Bytes must be a non-negative integer');
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public static function percentage(float $part, float $total, int $decimals = 2): float
    {
        if ($total <= 0) {
            throw new InvalidArgumentException('Total must be greater than 0');
        }

        $percentage = ($part / $total) * 100;
        return round($percentage, $decimals);
    }

    public static function random(int $min, int $max): int
    {
        if ($min > $max) {
            throw new InvalidArgumentException('Minimum value cannot be greater than maximum value');
        }

        return random_int($min, $max);
    }

    public static function isEven(int $number): bool
    {
        return $number % 2 === 0;
    }

    public static function isOdd(int $number): bool
    {
        return $number % 2 !== 0;
    }
}