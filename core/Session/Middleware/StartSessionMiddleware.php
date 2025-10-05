<?php

declare(strict_types=1);

namespace Plugs\Session\Middleware;

use Plugs\Session\Interface\SessionInterface;

/**
 * Start Session Middleware
 * 
 * This middleware starts the session and handles cleanup after the response.
 */
class StartSessionMiddleware
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Handle an incoming request.
     */
    public function handle($request, callable $next)
    {
        // Start the session
        $this->startSession();

        // Store previous URL for redirects (exclude certain paths)
        $this->storePreviousUrl($request);

        // Process the request
        $response = $next($request);

        // Session is automatically saved by PHP's session handler
        // No need to manually write session data

        return $response;
    }

    /**
     * Start the session.
     */
    protected function startSession(): void
    {
        // Check if session is already started
        if ($this->session->isStarted()) {
            return;
        }

        $this->session->start();
    }

    /**
     * Store the previous URL in the session.
     */
    protected function storePreviousUrl($request): void
    {
        // Get request method
        $method = $this->getRequestMethod($request);

        // Only store GET requests
        if (strtoupper($method) !== 'GET') {
            return;
        }

        // Get request URI
        $uri = $this->getRequestUri($request);

        // Don't store URLs for certain paths
        if ($this->shouldNotStorePreviousUrl($uri)) {
            return;
        }

        // Store the URL
        $this->session->setPreviousUrl($uri);
    }

    /**
     * Get the request method.
     */
    protected function getRequestMethod($request): string
    {
        // Try different methods to get the HTTP method
        if (is_object($request) && method_exists($request, 'getMethod')) {
            return $request->getMethod();
        }

        if (is_object($request) && isset($request->method)) {
            return $request->method;
        }

        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the request URI.
     */
    protected function getRequestUri($request): string
    {
        // Try different methods to get the URI
        if (is_object($request) && method_exists($request, 'getUri')) {
            return $request->getUri();
        }

        if (is_object($request) && method_exists($request, 'getRequestUri')) {
            return $request->getRequestUri();
        }

        if (is_object($request) && isset($request->uri)) {
            return $request->uri;
        }

        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Determine if the previous URL should not be stored.
     */
    protected function shouldNotStorePreviousUrl(string $uri): bool
    {
        // List of paths that should not be stored as previous URL
        $excludedPaths = [
            '/login',
            '/logout',
            '/register',
            '/password/reset',
            '/password/email',
            '/api/',
        ];

        foreach ($excludedPaths as $path) {
            if (str_starts_with($uri, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Terminate middleware (called after response is sent).
     * This is optional and depends on your framework.
     */
    public function terminate($request, $response): void
    {
        // Session is automatically written by PHP
        // This method is here for compatibility with frameworks that use it
    }
}