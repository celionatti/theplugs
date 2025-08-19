<?php

declare(strict_types=1);

namespace Illusion\Upload;

class UploadConfig
{
    public static function documents(): array
    {
        return [
            'allowed' => ['documents'],
            'maxSize' => 10 * 1024 * 1024, // 10MB
            'rename' => 'unique'
        ];
    }

    public static function images(): array
    {
        return [
            'allowed' => ['images'],
            'maxSize' => 5 * 1024 * 1024, // 5MB
            'compress' => 80,
            'rename' => 'unique'
        ];
    }

    public static function media(): array
    {
        return [
            'allowed' => ['audio', 'video'],
            'maxSize' => 50 * 1024 * 1024, // 50MB
            'rename' => 'unique'
        ];
    }

    public static function avatars(): array
    {
        return [
            'allowed' => ['jpg', 'png', 'webp'],
            'maxSize' => 2 * 1024 * 1024, // 2MB
            'resize' => [300, 300],
            'compress' => 85,
            'convertTo' => 'webp',
            'rename' => 'unique'
        ];
    }
}