<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Http;

use Exception;
use Throwable;

class HttpException extends Exception
{
    private array $headers;

    public function __construct(
        int $statusCode = 500,
        string $message = '',
        array $headers = [],
        ?Throwable $previous = null
    ) {
        $this->headers = $headers;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public static function createFromCode(int $statusCode, array $headers = []): self
    {
        $message = self::getDefaultMessageForStatusCode($statusCode);
        return new self($statusCode, $message, $headers);
    }

    private static function getDefaultMessageForStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            413 => 'Payload Too Large',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            503 => 'Service Unavailable',
            default => 'HTTP Error',
        };
    }
}