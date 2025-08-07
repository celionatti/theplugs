<?php

declare(strict_types=1);

namespace Plugs\Http\Request;

class Headers
{
    private array $headers = [];

    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $key = strtolower($key);
        $this->headers[$key] = is_array($value)
            ? array_map('strip_tags', $value)
            : strip_tags($value);
        return $this;
    }

    public function has(string $key): bool
    {
        $key = strtolower($key);
        return isset($this->headers[$key]);
    }

    public function all(): array
    {
        return $this->headers;
    }
}