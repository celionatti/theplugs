<?php

declare(strict_types=1);

namespace Plugs\Http\Response;

use Plugs\View\View;
use RuntimeException;
use InvalidArgumentException;

class Response
{
    private mixed $content = '';
    private int $statusCode = 200;
    private string $statusText = 'OK';
    private array $headers = [];
    private array $cookies = []; // Added for cookie support
    private string $version = '1.1';
    private bool $sent = false; // Added to track if response was sent
    private array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($statusCode);
        $this->setHeaders($headers);
        $this->setDefaultHeaders(); // Added default security headers
    }

    /**
     * Set default security headers (from Trees\Response)
     */
    private function setDefaultHeaders(): void
    {
        $defaultHeaders = [
            'content-security-policy' => "default-src 'self'; " .
                "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
                "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " .
                "img-src 'self' data: https:; " .
                "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;",
            'x-content-type-options' => 'nosniff',
            'x-frame-options' => 'DENY',
            'x-xss-protection' => '1; mode=block',
            'referrer-policy' => 'strict-origin-when-cross-origin',
            'x-framework' => 'ThePlugs'
        ];

        // Only set headers if not already set by user
        foreach ($defaultHeaders as $name => $value) {
            if (!$this->hasHeader($name)) {
                $this->setHeader($name, $value);
            }
        }

        // Only add HSTS in production/HTTPS
        if ($this->isHttps() && !$this->hasHeader('strict-transport-security')) {
            $this->setHeader('strict-transport-security', 'max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Check if connection is HTTPS (from Trees\Response)
     */
    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }

    public function setContent(mixed $content): self
    {
        // Handle View objects
        if ($content instanceof View) {
            $this->content = $content->render();

            // Set content type to HTML if not already set
            if (!$this->hasHeader('Content-Type')) {
                $this->contentType('text/html');
            }

            return $this;
        }

        if (
            null !== $content && !is_string($content) && !is_numeric($content) && !is_callable($content) &&
            (!is_object($content) || !method_exists($content, '__toString'))
        ) {
            throw new InvalidArgumentException(sprintf(
                'The Response content must be a string, View object, or object implementing __toString(), "%s" given.',
                gettype($content)
            ));
        }

        $this->content = $content;
        return $this;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function setStatusCode(int $statusCode, ?string $text = null): self
    {
        $this->statusCode = $statusCode;
        if ($text === null) {
            $this->statusText = $this->statusTexts[$statusCode] ?? 'unknown status';
        } else {
            $this->statusText = $text;
        }

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getStatusText(): string
    {
        return $this->statusText;
    }

    public function setProtocolVersion(string $version): self
    {
        if (!in_array($version, ['1.0', '1.1', '2.0', '2'])) {
            throw new InvalidArgumentException('Invalid HTTP protocol version');
        }
        $this->version = $version;
        return $this;
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function setHeader(string $name, string $value, bool $replace = true): self
    {
        $normalized = strtolower($name);

        if ($replace || !isset($this->headers[$normalized])) {
            $this->headers[$normalized] = [$value];
        } else {
            $this->headers[$normalized][] = $value;
        }

        return $this;
    }

    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    public function getHeaders(): array
    {
        $flattened = [];
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $flattened[$name][] = $value;
            }
        }
        return $flattened;
    }

    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? [];
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function removeHeader(string $name): self
    {
        unset($this->headers[strtolower($name)]);
        return $this;
    }

    public function clearHeaders(): self
    {
        $this->headers = [];
        return $this;
    }

    public function contentType(string $contentType, string $charset = 'UTF-8'): self
    {
        $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
        return $this;
    }

    /**
     * Set cache headers (from Trees\Response)
     */
    public function cache(int $seconds): self
    {
        $this->setHeader('Cache-Control', "max-age={$seconds}");
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        return $this;
    }

    public function noCache(): self
    {
        $this->setHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Mon, 01 Jan 1990 00:00:00 GMT',
        ]);
        return $this;
    }

    /**
     * Set CORS headers (from Trees\Response)
     */
    public function cors(
        array $origins = ['*'],
        array $methods = ['GET', 'POST', 'PUT', 'DELETE'],
        array $headers = ['Content-Type', 'Authorization']
    ): self {
        $this->setHeaders([
            'Access-Control-Allow-Origin' => implode(', ', $origins),
            'Access-Control-Allow-Methods' => implode(', ', $methods),
            'Access-Control-Allow-Headers' => implode(', ', $headers)
        ]);
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302, bool $secure = true): self
    {
        if ($secure && !preg_match('/^https?:\\/\\//', $url)) {
            throw new InvalidArgumentException('Redirect URL must be absolute for security reasons');
        }

        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);

        $escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->content = sprintf('<!DOCTYPE html>
        <html>
            <head>
                <meta charset="UTF-8" />
                <meta http-equiv="refresh" content="0;url=\'%1$s\'" />
                <title>Redirecting to %1$s</title>
            </head>
            <body>
                Redirecting to <a href="%1$s">%1$s</a>.
            </body>
        </html>', $escapedUrl);

        return $this;
    }

    // public function download(
    //     string $filePath,
    //     ?string $name = null,
    //     bool $deleteAfterSend = false,
    //     bool $inline = false
    // ): self {
    //     if (!is_readable($filePath)) {
    //         throw new RuntimeException("File not found or not readable: $filePath");
    //     }

    //     $name = $name ?? basename($filePath);
    //     $disposition = $inline ? 'inline' : 'attachment';

    //     $this->setHeaders([
    //         'Content-Type' => $this->guessContentType($filePath) ?: 'application/octet-stream',
    //         'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $this->quoteFilename($name)),
    //         'Content-Length' => filesize($filePath),
    //         'Content-Transfer-Encoding' => 'binary',
    //         'Pragma' => 'public',
    //         'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
    //         'Last-Modified' => gmdate('D, d M Y H:i:s T', filemtime($filePath)),
    //         'Expires' => '0',
    //     ]);

    //     $this->content = function () use ($filePath, $deleteAfterSend) {
    //         $output = fopen('php://output', 'wb');
    //         $file = fopen($filePath, 'rb');

    //         stream_copy_to_stream($file, $output);

    //         fclose($file);
    //         fclose($output);

    //         if ($deleteAfterSend) {
    //             unlink($filePath);
    //         }
    //     };

    //     return $this;
    // }

    public function download(
        string $filePath,
        ?string $name = null,
        bool $deleteAfterSend = false,
        bool $inline = false,
        bool $stream = false // Added streaming option
    ): self {
        if ($stream) {
            return $this->stream($filePath, $name);
        }

        // Your existing download logic...
        if (!is_readable($filePath)) {
            throw new RuntimeException("File not found or not readable: $filePath");
        }

        $name = $name ?? basename($filePath);
        $disposition = $inline ? 'inline' : 'attachment';

        $this->setHeaders([
            'Content-Type' => $this->guessContentType($filePath) ?: 'application/octet-stream',
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $this->quoteFilename($name)),
            'Content-Length' => filesize($filePath),
            'Content-Transfer-Encoding' => 'binary',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Last-Modified' => gmdate('D, d M Y H:i:s T', filemtime($filePath)),
            'Expires' => '0',
        ]);

        $this->content = function () use ($filePath, $deleteAfterSend) {
            $output = fopen('php://output', 'wb');
            $file = fopen($filePath, 'rb');
            
            stream_copy_to_stream($file, $output);
            
            fclose($file);
            fclose($output);
            
            if ($deleteAfterSend) {
                unlink($filePath);
            }
        };

        return $this;
    }

    /**
     * Stream a file (enhanced version from Trees\Response)
     */
    public function stream(string $filePath, ?string $filename = null): self
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: {$filePath}");
        }

        $filename = $filename ?: basename($filePath);
        $size = filesize($filePath);
        $mimeType = $this->guessContentType($filePath) ?: 'application/octet-stream';

        $this->setHeaders([
            'Content-Type' => $mimeType,
            'Content-Length' => (string)$size,
            'Content-Disposition' => 'inline; filename="' . $this->quoteFilename($filename) . '"',
            'Accept-Ranges' => 'bytes'
        ]);

        $this->content = function () use ($filePath) {
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    flush();
                }
                fclose($handle);
            }
        };

        return $this;
    }

    /**
     * Set XML content type (from Trees\Response)
     */
    public function xml(string $content): self
    {
        $this->content = $content;
        $this->setHeader('Content-Type', 'application/xml; charset=UTF-8');
        return $this;
    }

    /**
     * Set CSV content type (from Trees\Response)
     */
    public function csv(string $content, string $filename = 'export.csv'): self
    {
        $this->content = $content;
        $this->setHeaders([
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
        return $this;
    }

    // public function send(): void
    // {
    //     $this->sendHeaders();
    //     $this->sendContent();
    // }

    public function send(): void
    {
        if ($this->sent) {
            throw new RuntimeException('Response already sent');
        }

        $this->sent = true;
        $this->sendHeaders();
        $this->sendContent();
    }

    /**
     * Output content directly without storing it (from Trees\Response)
     */
    public function output(string $content): self
    {
        if ($this->sent) {
            throw new RuntimeException('Response already sent');
        }

        $this->sendHeaders();

        echo $content;
        $this->sent = true;

        return $this;
    }

    // protected function sendHeaders(): void
    // {
    //     if (headers_sent()) {
    //         return;
    //     }

    //     // Status
    //     header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

    //     // Headers
    //     foreach ($this->headers as $name => $values) {
    //         $replace = true;
    //         foreach ($values as $value) {
    //             header($name.': '.$value, $replace);
    //             $replace = false;
    //         }
    //     }
    // }

    protected function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // Status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

        // Headers
        foreach ($this->headers as $name => $values) {
            $replace = true;
            foreach ($values as $value) {
                header($name . ': ' . $value, $replace);
                $replace = false;
            }
        }

        // Cookies (from Trees\Response)
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                [
                    'expires' => $cookie['expires'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite']
                ]
            );
        }
    }

    protected function sendContent(): void
    {
        if (is_callable($this->content)) {
            call_user_func($this->content);
        } else {
            echo $this->content;
        }
    }

    public static function json(
        array $data,
        int $statusCode = 200,
        array $headers = [],
        int $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
    ): self {
        $response = new self('', $statusCode, $headers);
        $response->setContent(json_encode($data, $jsonOptions));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON data: ' . json_last_error_msg());
        }

        return $response->contentType('application/json');
    }

    public static function text(string $text, int $statusCode = 200, array $headers = []): self
    {
        return (new self($text, $statusCode, $headers))
            ->contentType('text/plain');
    }

    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        return (new self($html, $statusCode, $headers))
            ->contentType('text/html');
    }

    /**
     * Create a view response
     */
    public static function view(string|View $view, array $data = [], int $statusCode = 200, array $headers = []): self
    {
        if (is_string($view)) {
            $view = app('view')->make($view, $data);
        }

        return (new self($view, $statusCode, $headers))
            ->contentType('text/html');
    }

    public static function notFound(string $message = 'Not Found', array $headers = []): self
    {
        return new self($message, 404, $headers);
    }

    public static function serverError(string $message = 'Internal Server Error', array $headers = []): self
    {
        return new self($message, 500, $headers);
    }

    public static function noContent(array $headers = []): self
    {
        return new self('', 204, $headers);
    }

    public static function created(string $location, mixed $content = '', array $headers = []): self
    {
        $headers['Location'] = $location;
        return new self($content, 201, $headers);
    }

    public static function accepted(mixed $content = '', array $headers = []): self
    {
        return new self($content, 202, $headers);
    }

    public static function forJsonApi(
        array $data,
        array $included = [],
        array $meta = [],
        array $links = [],
        int $statusCode = 200,
        array $headers = []
    ): self {
        $responseData = ['data' => $data];

        if (!empty($included)) {
            $responseData['included'] = $included;
        }

        if (!empty($meta)) {
            $responseData['meta'] = $meta;
        }

        if (!empty($links)) {
            $responseData['links'] = $links;
        }

        return self::json($responseData, $statusCode, $headers);
    }

    private function guessContentType(string $filePath): ?string
    {
        if (!class_exists('finfo') || !is_readable($filePath)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        return $mimeType ?: null;
    }

    private function quoteFilename(string $filename): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
    }

    /**
     * Set a cookie (from Trees\Response)
     */
    public function setCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): self {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite
        ];
        return $this;
    }

    /**
     * Delete a cookie (from Trees\Response)
     */
    public function deleteCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->setCookie($name, '', time() - 3600, $path, $domain);
    }

    /**
     * Check if response was sent (from Trees\Response)
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Static method for CSV responses
     */
    // public static function csv(
    //     array $data,
    //     string $filename = 'export.csv',
    //     int $statusCode = 200,
    //     array $headers = []
    // ): self {
    //     $csvContent = '';
        
    //     if (!empty($data)) {
    //         // Add headers
    //         $headers = array_keys($data[0]);
    //         $csvContent .= implode(',', $headers) . "\n";
            
    //         // Add data
    //         foreach ($data as $row) {
    //             $csvContent .= implode(',', array_map(function ($value) {
    //                 return '"' . str_replace('"', '""', $value) . '"';
    //             }, $row)) . "\n";
    //         }
    //     }

    //     return (new self($csvContent, $statusCode, $headers))
    //         ->csv($csvContent, $filename);
    // }

    /**
     * Static method for XML responses
     */
    // public static function xml(string $xml, int $statusCode = 200, array $headers = []): self
    // {
    //     return (new self($xml, $statusCode, $headers))
    //         ->xml($xml);
    // }

    /**
     * Destructor to auto-send response (from Trees\Response)
     */
    public function __destruct()
    {
        if (!$this->sent) {
            $this->send();
        }
    }
}
