<?php

declare(strict_types=1);

namespace Illusion\Helper;

use InvalidArgumentException;

final class Str
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function slug(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // Replace non-letter/non-digit characters with dashes
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        
        // Remove leading/trailing dashes
        $text = trim($text, '-');
        
        // Remove consecutive dashes
        $text = preg_replace('/-+/', '-', $text);
        
        return $text;
    }

    public static function limit(string $text, int $limit, string $end = '...'): string
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be a non-negative integer');
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit)) . $end;
    }

    public static function camelCase(string $text): string
    {
        $text = self::slug($text);
        $text = str_replace(' ', '', ucwords(str_replace('-', ' ', $text)));
        
        return lcfirst($text);
    }

    public static function snakeCase(string $text): string
    {
        $text = self::slug($text);
        return str_replace('-', '_', $text);
    }

    public static function startsWith(string $text, string $search): bool
    {
        if (empty($search)) {
            return true;
        }

        return str_starts_with($text, $search);
    }

    public static function endsWith(string $text, string $search): bool
    {
        if (empty($search)) {
            return true;
        }

        return str_ends_with($text, $search);
    }

    public static function contains(string $text, string $search): bool
    {
        if (empty($search)) {
            return true;
        }

        return str_contains($text, $search);
    }

    public static function random(int $length = 16): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be at least 1');
        }

        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    public static function replace(string $search, string $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    public static function sanitize(string $text): string
    {
        // Basic XSS protection
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $text;
    }
}