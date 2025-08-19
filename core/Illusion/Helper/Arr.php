<?php

declare(strict_types=1);

namespace Illusion\Helper;

final class Arr
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        return $array[$key] ?? $default;
    }

    public static function set(array &$array, string $key, mixed $value): void
    {
        $array[$key] = $value;
    }

    public static function has(array $array, string $key): bool
    {
        return array_key_exists($key, $array);
    }

    public static function flatten(array $array): array
    {
        $result = [];
        
        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value));
            } else {
                $result[] = $value;
            }
        }
        
        return $result;
    }

    public static function first(array $array): mixed
    {
        if (empty($array)) {
            return null;
        }
        
        return array_values($array)[0];
    }

    public static function last(array $array): mixed
    {
        if (empty($array)) {
            return null;
        }
        
        return array_values($array)[count($array) - 1];
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}