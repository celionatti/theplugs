<?php

declare(strict_types=1);

namespace Plugs\Http\Request;

use InvalidArgumentException;

class Request
{
    private string $method;
    private string $uri;
    private array $parameters = [];
    private array $headers = [];
    private array $query = [];
    private array $server;
    private array $post = [];
    private array $attributes = [];
    private array $cookies = [];
    private array $files = [];
    private ?array $jsonData = null;

    public function __construct(
        ?string $method = null,
        ?string $uri = null,
        array $headers = [],
        array $query = [],
        array $post = [],
        array $server = [],
        array $cookies = [],
        array $files = []
    ) {
        $this->method = $this->sanitizeMethod($method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->sanitizeUri($uri ?? $this->parseUri());
        $this->headers = $this->sanitizeHeaders($headers ?: $this->parseHeaders());
        $this->query = $this->sanitizeInput($query ?: $_GET);
        $this->post = $this->sanitizeInput($post ?: $_POST);
        $this->server = $server ?: $_SERVER;
        $this->cookies = $this->sanitizeInput($cookies ?: $_COOKIE);
        $this->files = $this->sanitizeFiles($files ?: $_FILES);
    }

    public static function capture(): self
    {
        return new static();
    }

    private function sanitizeMethod(string $method): string
    {
        return strtoupper($method);
    }

    private function sanitizeUri(string $uri): string
    {
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        return $uri ?: '/';
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        foreach ($headers as $key => $value) {
            $sanitized[strtolower($key)] = is_array($value) 
                ? array_map('strip_tags', $value)
                : strip_tags($value);
        }
        return $sanitized;
    }

    private function sanitizeInput(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
            $sanitized[$sanitizedKey] = is_array($value)
                ? $this->sanitizeInput($value)
                : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return $sanitized;
    }

    private function sanitizeFiles(array $files): array
    {
        $sanitized = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                foreach ($file as $property => $values) {
                    foreach ($values as $index => $value) {
                        $sanitized[$key][$index][$property] = $this->sanitizeFileValue($property, $value);
                    }
                }
            } else {
                foreach ($file as $property => $value) {
                    $sanitized[$key][$property] = $this->sanitizeFileValue($property, $value);
                }
            }
        }
        return $sanitized;
    }

    private function sanitizeFileValue(string $property, $value)
    {
        return in_array($property, ['name', 'type'])
            ? filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            : $value;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return parse_url($this->uri, PHP_URL_PATH);
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') 
            || (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    public function getHost(): string
    {
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return preg_replace('/:\d+$/', '', $host); // Remove port if present
    }

    public function getPort(): int
    {
        if (isset($this->server['SERVER_PORT'])) {
            return (int)$this->server['SERVER_PORT'];
        }
        return $this->isSecure() ? 443 : 80;
    }

    public function getBaseUrl(): string
    {
        return $this->getScheme() . '://' . $this->getHost();
    }

    public function getFullUrl(): string
    {
        return $this->getBaseUrl() . $this->uri;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $this->sanitizeInput($parameters);
    }

    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->post, $this->query);
        }
        return $this->post($key, $this->query($key, $default));
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->parameters);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return !empty($value) || $value === '0';
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function header(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->headers;
        }
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function setHeader(string $key, mixed $value): void
    {
        $this->headers[strtolower($key)] = is_array($value)
            ? array_map('strip_tags', $value)
            : strip_tags($value);
    }

    public function setHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function hasHeader(string $key): bool
    {
        return isset($this->headers[strtolower($key)]);
    }

    public function cookie(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    public function file(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }

    public function isXmlHttpRequest(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        $acceptable = $this->header('Accept', '');
        return str_contains($acceptable, 'application/json');
    }

    public function getContent(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    public function json(): array
    {
        if ($this->jsonData === null) {
            $this->jsonData = [];
            
            if ($this->isJson()) {
                $content = $this->getContent();
                $this->jsonData = json_decode($content, true) ?? [];
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Invalid JSON payload');
                }
            }
        }
        
        return $this->jsonData;
    }

    public function jsonGet(string $key, mixed $default = null): mixed
    {
        $data = $this->json();
        return $data[$key] ?? $default;
    }

    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return strtok($uri, '?') ?: '/';
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            return $this->sanitizeBearerToken($token);
        }

        return null;
    }

    private function sanitizeBearerToken(string $token): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-.]/', '', $token);
    }

    public function hasBearerToken(): bool
    {
        return $this->bearerToken() !== null;
    }

    public function enableHttpMethodParameterOverride(): void
    {
        if ($this->getMethod() === 'POST') {
            $method = $this->post('_method');
            if ($method && in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE'])) {
                $this->method = strtoupper($method);
                $this->server['REQUEST_METHOD'] = $this->method;
            }
        }
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $header = strtolower(str_replace('_', '-', $key));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    public function getClientIp(): string
    {
        $ip = $this->server['HTTP_CLIENT_IP'] 
            ?? $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '';

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    public function getUserAgent(): string
    {
        return $this->header('User-Agent', '');
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function isMethodSafe(): bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD']);
    }

    public function isPrefetch(): bool
    {
        return strtolower($this->header('Purpose', '')) === 'prefetch' ||
               strtolower($this->header('X-Purpose', '')) === 'prefetch';
    }
}