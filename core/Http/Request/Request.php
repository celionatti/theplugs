<?php

declare(strict_types=1);

namespace Plugs\Http\Request;

use InvalidArgumentException;
use Plugs\Http\Request\Headers;
use Plugs\Session\Interface\SessionInterface;

class Request
{
    private string $method;
    private string $uri;
    private array $parameters = [];
    private Headers $headers;
    private array $query = [];
    private array $server;
    private array $post = [];
    private array $rawPost = []; // Store unsanitized POST data
    private array $attributes = [];
    private array $cookies = [];
    private array $files = [];
    private ?array $jsonData = null;
    private ?SessionInterface $session = null;

    // HTML handling properties
    private array $htmlAllowedFields = [
        'content',
        'body',
        'description',
        'html_content',
        'post_content',
        'article_content',
        'bio',
        'about'
    ];

    private array $allowedHtmlTags = [
        'p',
        'br',
        'strong',
        'em',
        'b',
        'i',
        'u',
        'ul',
        'ol',
        'li',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'blockquote',
        'code',
        'pre',
        'a',
        'img',
        'div',
        'span',
        'table',
        'tr',
        'td',
        'th',
        'thead',
        'tbody',
        'tfoot'
    ];

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
        $this->headers = new Headers($this->sanitizeHeaders($headers ?: $this->parseHeaders()));
        $this->query = $this->sanitizeInput($query ?: $_GET);
        // Store raw POST data before sanitization
        $rawPostData = $post ?: $_POST;
        $this->rawPost = $rawPostData;
        $this->post = $this->sanitizeInput($rawPostData);
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

    // private function sanitizeInput(array $input): array
    // {
    //     $sanitized = [];
    //     foreach ($input as $key => $value) {
    //         $sanitizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    //         $sanitized[$sanitizedKey] = is_array($value)
    //             ? $this->sanitizeInput($value)
    //             : htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    //     }
    //     return $sanitized;
    // }

    private function sanitizeInput(array $input): array
    {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
            $allowHtml = in_array($sanitizedKey, $this->htmlAllowedFields);
            $sanitized[$sanitizedKey] = $this->sanitizeValue($value, $allowHtml);
        }
        return $sanitized;
    }

    private function sanitizeValue(mixed $value, bool $allowHtml = false): mixed
    {
        if (is_array($value)) {
            return array_map(fn($item) => $this->sanitizeValue($item, $allowHtml), $value);
        }

        if (is_numeric($value)) {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }

        // Convert to string
        $value = (string)$value;

        // Allow HTML for specific fields
        if ($allowHtml) {
            // Basic sanitization for HTML content - remove potentially dangerous tags but keep allowed ones
            $allowedTags = '<' . implode('><', $this->allowedHtmlTags) . '>';
            $value = strip_tags($value, $allowedTags);

            // Remove dangerous attributes
            $value = preg_replace('/<([a-z][a-z0-9]*)[^>]*?(on\w+\s*=|javascript:|data:\s*text\/html|vbscript:|mocha:|livescript:)[^>]*?>/i', '<$1>', $value);

            // Remove style attributes that could contain dangerous CSS
            $value = preg_replace('/<([a-z][a-z0-9]*)[^>]*?\s+style\s*=[^>]*?>/i', '<$1>', $value);

            return $value;
        }

        // Default sanitization for regular text
        $value = strip_tags($value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

    /**
     * Get the raw body content of the request
     */
    public function getBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Get the request body as JSON decoded array (using raw data)
     */
    public function json(): array
    {
        if ($this->jsonData === null) {
            $this->jsonData = [];

            if ($this->isJson()) {
                $content = $this->getBody();
                $this->jsonData = json_decode($content, true) ?? [];

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Invalid JSON payload');
                }
            }
        }

        return $this->jsonData;
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

    /**
     * Get raw POST data without sanitization
     */
    public function rawPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->rawPost;
        }
        return $this->rawPost[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->post, $this->query);
        }
        return $this->post($key, $this->query($key, $default));
    }

    /**
     * Get raw input data without sanitization (POST takes precedence over GET)
     */
    public function rawInput(string $key, mixed $default = null): mixed
    {
        return $this->rawPost[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get HTML content safely with basic sanitization
     */
    public function htmlContent(string $key, string $default = ''): string
    {
        $value = $this->rawInput($key, $default);

        if (empty($value)) {
            return $default;
        }

        // Basic security sanitization for HTML
        $allowedTags = '<' . implode('><', $this->allowedHtmlTags) . '>';
        $value = strip_tags((string)$value, $allowedTags);

        // Remove potentially dangerous attributes
        $value = preg_replace('/<([a-z][a-z0-9]*)[^>]*?(on\w+\s*=|javascript:|data:\s*text\/html|vbscript:|mocha:|livescript:)[^>]*?>/i', '<$1>', $value);

        // Remove style attributes that could contain dangerous CSS
        $value = preg_replace('/<([a-z][a-z0-9]*)[^>]*?\s+style\s*=[^>]*?>/i', '<$1>', $value);

        // Clean up any empty tags
        $value = preg_replace('/<([a-z]+)[^>]*?>\s*<\/\1>/i', '', $value);

        return trim($value);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->parameters);
    }

    /**
     * Get all raw data without sanitization
     */
    public function allRaw(): array
    {
        return array_merge($this->query, $this->rawPost, $this->parameters);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Get only raw data for specified keys
     */
    public function onlyRaw(array $keys): array
    {
        return array_intersect_key($this->allRaw(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Get raw data except specified keys
     */
    public function exceptRaw(array $keys): array
    {
        return array_diff_key($this->allRaw(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * Get input value from query parameters or request body
     * Alias for input() method
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
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
            return $this->headers->all();
        }
        return $this->headers->get($key, $default);
    }

    public function setHeader(string $key, mixed $value): void
    {
        $this->headers->set($key, $value);
    }

    public function setHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function hasHeader(string $key): bool
    {
        return $this->headers->has($key);
    }

    // Add this getter method
    public function headers(): Headers
    {
        return $this->headers;
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

    // public function json(): array
    // {
    //     if ($this->jsonData === null) {
    //         $this->jsonData = [];

    //         if ($this->isJson()) {
    //             $content = $this->getContent();
    //             $this->jsonData = json_decode($content, true) ?? [];

    //             if (json_last_error() !== JSON_ERROR_NONE) {
    //                 throw new InvalidArgumentException('Invalid JSON payload');
    //             }
    //         }
    //     }

    //     return $this->jsonData;
    // }

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

    /**
     * Set the session instance
     */
    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    /**
     * Get the session instance
     */
    public function session(): ?SessionInterface
    {
        return $this->session;
    }

    /**
     * Check if request has session
     */
    public function hasSession(): bool
    {
        return $this->session !== null;
    }

    /**
     * Get value from session
     */
    public function sessionGet(string $key, mixed $default = null): mixed
    {
        return $this->session?->get($key, $default);
    }

    /**
     * Store value in session
     */
    public function sessionPut(string $key, mixed $value): void
    {
        $this->session?->put($key, $value);
    }

    /**
     * Flash data to session for next request
     */
    public function sessionFlash(string $key, mixed $value): void
    {
        $this->session?->flash($key, $value);
    }

    /**
     * Add fields that should allow HTML
     */
    public function allowHtmlFor(array $fields): void
    {
        $this->htmlAllowedFields = array_merge($this->htmlAllowedFields, $fields);
        $this->htmlAllowedFields = array_unique($this->htmlAllowedFields);
    }

    /**
     * Set allowed HTML tags for htmlContent method
     */
    public function setAllowedHtmlTags(array $tags): void
    {
        $this->allowedHtmlTags = $tags;
    }

    /**
     * Get current allowed HTML tags
     */
    public function getAllowedHtmlTags(): array
    {
        return $this->allowedHtmlTags;
    }

    /**
     * Get current HTML allowed fields
     */
    public function getHtmlAllowedFields(): array
    {
        return $this->htmlAllowedFields;
    }
}
