<?php

declare(strict_types=1);

namespace Plugs\LiveHTML;

use Plugs\Container\Container;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Exceptions\LiveHTML\LiveHTMLException;

class LiveHTMLHandler
{
    protected Container $container;
    protected $session;
    protected array $config;
    protected array $components = [];
    protected ComponentManager $componentManager;
    protected string $endpoint = '/livehtml';
    protected array $middleware = [];

    public function __construct(Container $container, $session, array $config = [])
    {
        $this->container = $container;
        $this->session = $session;
        $this->config = $config;
        $this->componentManager = new ComponentManager($container);
        
        // Initialize session for component storage
        $this->initializeSession();
    }

    /**
     * Initialize session storage for components
     */
    protected function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['livehtml_components'])) {
            $_SESSION['livehtml_components'] = [];
        }
    }

    /**
     * Handle incoming LiveHTML request
     */
    public function handleRequest(Request $request): ?Response
    {
        if (!$this->isLiveHTMLRequest($request)) {
            return null;
        }

        try {
            // Verify CSRF token
            if (!$this->verifyCsrfToken($request)) {
                return $this->errorResponse('Invalid CSRF token', 403);
            }

            // Extract request data
            $componentId = $request->input('fingerprint.id');
            $componentName = $request->input('fingerprint.name');
            $method = $request->input('serverMemo.method');
            $data = $request->input('serverMemo.data', []);
            $updates = $request->input('updates', []);

            // Resolve component
            $component = $this->resolveComponent($componentId, $componentName, $data);

            // Apply property updates
            $this->applyUpdates($component, $updates);

            // Call the method if specified
            if ($method && $this->isMethodCallable($component, $method)) {
                $params = $request->input('serverMemo.params', []);
                $this->callComponentMethod($component, $method, $params);
            }

            // Re-render component
            $html = $component->render();

            // Store updated component state
            $this->storeComponent($component);

            // Prepare response
            return $this->successResponse([
                'html' => $html,
                'serverMemo' => [
                    'data' => $component->getState(),
                    'checksum' => $component->getChecksum(),
                ],
                'effects' => [
                    'emits' => $component->getEmittedEvents(),
                    'dispatches' => $component->getDispatchedEvents(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Render a LiveHTML component
     */
    public function render(string $componentName, array $parameters = []): string
    {
        try {
            // Create new component instance
            $component = $this->componentManager->create($componentName, $parameters);
            
            // Store component in session
            $this->storeComponent($component);
            
            // Generate component HTML with wrapper
            $html = $component->render();
            
            return $this->wrapComponent($component, $html);
            
        } catch (\Exception $e) {
            if ($this->config['debug'] ?? false) {
                return $this->renderError($e);
            }
            throw new LiveHTMLException("Failed to render component '{$componentName}': " . $e->getMessage());
        }
    }

    /**
     * Wrap component HTML with LiveHTML attributes
     */
    protected function wrapComponent(Component $component, string $html): string
    {
        $fingerprint = [
            'id' => $component->getId(),
            'name' => $component->getName(),
            'locale' => 'en', // Could be dynamic
            'path' => $_SERVER['REQUEST_URI'] ?? '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        ];

        $serverMemo = [
            'data' => $component->getState(),
            'checksum' => $component->getChecksum(),
        ];

        $attributes = [
            'wire:id' => $component->getId(),
            'wire:initial-data' => htmlspecialchars(json_encode($serverMemo), ENT_QUOTES),
            'wire:fingerprint' => htmlspecialchars(json_encode($fingerprint), ENT_QUOTES),
        ];

        // Add attributes to the root element
        if (preg_match('/^<([a-zA-Z0-9-]+)([^>]*)>/', $html, $matches)) {
            $tag = $matches[1];
            $existingAttrs = $matches[2];
            
            $newAttrs = '';
            foreach ($attributes as $key => $value) {
                $newAttrs .= " {$key}=\"{$value}\"";
            }
            
            $html = preg_replace(
                '/^<' . preg_quote($tag) . '([^>]*)>/',
                "<{$tag}{$existingAttrs}{$newAttrs}>",
                $html,
                1
            );
        } else {
            // Wrap in div if no root element
            $attrs = '';
            foreach ($attributes as $key => $value) {
                $attrs .= " {$key}=\"{$value}\"";
            }
            $html = "<div{$attrs}>{$html}</div>";
        }

        return $html;
    }

    /**
     * Resolve component from request data
     */
    protected function resolveComponent(string $componentId, string $componentName, array $data): Component
    {
        // Try to load from session first
        if (isset($_SESSION['livehtml_components'][$componentId])) {
            $componentData = $_SESSION['livehtml_components'][$componentId];
            $component = $this->componentManager->create(
                $componentName, 
                $componentData['state']
            );
            $component->setId($componentId);
            return $component;
        }

        // Create new component if not found in session
        $component = $this->componentManager->create($componentName, $data);
        $component->setId($componentId);
        
        return $component;
    }

    /**
     * Apply property updates to component
     */
    protected function applyUpdates(Component $component, array $updates): void
    {
        foreach ($updates as $update) {
            $property = $update['payload']['name'] ?? null;
            $value = $update['payload']['value'] ?? null;
            
            if ($property && $component->isPropertyFillable($property)) {
                $component->setProperty($property, $value);
            }
        }
    }

    /**
     * Call component method with parameters
     */
    protected function callComponentMethod(Component $component, string $method, array $params = []): void
    {
        if (!$this->isMethodCallable($component, $method)) {
            throw new LiveHTMLException("Method '{$method}' is not callable on component");
        }

        // Call method with proper parameter binding
        if (method_exists($component, $method)) {
            call_user_func_array([$component, $method], $params);
        }
    }

    /**
     * Check if method is callable on component
     */
    protected function isMethodCallable(Component $component, string $method): bool
    {
        // Check if method exists and is public
        if (!method_exists($component, $method)) {
            return false;
        }

        $reflection = new \ReflectionMethod($component, $method);
        
        // Must be public and not in blacklist
        if (!$reflection->isPublic()) {
            return false;
        }

        $blacklist = [
            'render', '__construct', '__destruct', '__call', '__callStatic',
            '__get', '__set', '__isset', '__unset', '__toString', '__invoke',
            'getId', 'setId', 'getName', 'getState', 'setState', 'getChecksum'
        ];

        return !in_array($method, $blacklist);
    }

    /**
     * Store component in session
     */
    protected function storeComponent(Component $component): void
    {
        $_SESSION['livehtml_components'][$component->getId()] = [
            'name' => $component->getName(),
            'state' => $component->getState(),
            'checksum' => $component->getChecksum(),
            'timestamp' => time(),
        ];
    }

    /**
     * Check if request is a LiveHTML request
     */
    public function isLiveHTMLRequest(Request $request): bool
    {
        return $request->hasHeader('X-LiveHTML') || 
               $request->input('_livehtml') === '1' ||
               str_contains($request->getUri(), $this->endpoint);
    }

    /**
     * Verify CSRF token
     */
    protected function verifyCsrfToken(Request $request): bool
    {
        // Get token from request
        $token = $request->input('_token') ?? 
                 $request->headers()->get('X-CSRF-TOKEN') ?? 
                 $request->headers()->get('X-XSRF-TOKEN');

        // Get session token
        $sessionToken = $_SESSION['_token'] ?? $_SESSION['csrf_token'] ?? null;

        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Generate JavaScript for LiveHTML
     */
    public function scripts(): string
    {
        $config = [
            'endpoint' => $this->endpoint,
            'csrf_token' => $_SESSION['_token'] ?? $_SESSION['csrf_token'] ?? null,
            'debug' => $this->config['debug'] ?? false,
        ];

        return sprintf(
            '<script>window.LiveHTMLConfig = %s;</script>
            <script src="%s"></script>',
            json_encode($config),
            '/assets/js/livehtml.js' // You'll need to create this file
        );
    }

    /**
     * Generate CSS for LiveHTML
     */
    public function styles(): string
    {
        return '<link rel="stylesheet" href="/assets/css/livehtml.css">';
    }

    /**
     * Create success response
     */
    protected function successResponse(array $data): Response
    {
        return new Response(
            json_encode(array_merge(['success' => true], $data)),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Create error response
     */
    protected function errorResponse(string $message, int $code = 400): Response
    {
        return new Response(
            json_encode(['success' => false, 'error' => $message]),
            $code,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Render error for debugging
     */
    protected function renderError(\Exception $e): string
    {
        return sprintf(
            '<div class="livehtml-error" style="border: 2px solid #ff0000; padding: 10px; background: #ffe6e6;">
                <h3>LiveHTML Error</h3>
                <p><strong>Message:</strong> %s</p>
                <p><strong>File:</strong> %s:%d</p>
                <pre>%s</pre>
            </div>',
            htmlspecialchars($e->getMessage()),
            htmlspecialchars($e->getFile()),
            $e->getLine(),
            htmlspecialchars($e->getTraceAsString())
        );
    }

    /**
     * Set endpoint for LiveHTML requests
     */
    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Get registered components
     */
    public function getComponents(): array
    {
        return $this->componentManager->getRegistered();
    }

    /**
     * Register a component
     */
    public function component(string $name, string $class): void
    {
        $this->componentManager->register($name, $class);
    }
}