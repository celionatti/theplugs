<?php

declare(strict_types=1);

namespace Plugs\Http\Request;

class Request
{
    private ?string $method;
    private ?string $uri;
    private array $parameters = [];
    private array $headers = [];
    private array $query = [];
    protected array $server;
    private array $post = [];
    private array $attributes = [];

    public function __construct(
        ?string $method = null,
        ?string $uri = null,
        array $headers = [],
        array $query = [],
        array $post = [],
        array $server = []
    ) {
        $this->method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $uri ?? $this->parseUri();
        $this->headers = $headers ?: $this->parseHeaders();
        $this->query = $query ?: $_GET;
        $this->post = $post ?: $_POST;
        $this->server = $server ?: $_SERVER;
    }

    public static function capture(): self
    {
        return new static();
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return strtok($uri, '?') ?: '/';
    }

    public function enableHttpMethodParameterOverride(): void
    {
        if ($this->getMethod() === 'POST' && $this->post('_method')) {
            $this->server['REQUEST_METHOD'] = strtoupper($this->post('_method'));
        }
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
}
