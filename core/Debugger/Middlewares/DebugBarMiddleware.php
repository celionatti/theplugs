<?php

declare(strict_types=1);

namespace Plugs\Debugger\Middlewares;

use Plugs\Http\Request\Request;
use Plugs\Debugger\PlugDebugger;
use Plugs\Http\Response\Response;
use Plugs\Middleware\MiddlewareInterface;

class DebugBarMiddleware implements MiddlewareInterface
{
    /**
     * Handle the incoming request
     */
    public function handle(Request $request, $next): Response
    {
        // Get the debugger instance
        $debugger = PlugDebugger::getInstance();
        
        // Mark the start of request handling
        $debugger->markPerformance('Request Start', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri()
        ]);
        
        // Process the request
        $response = $next($request);
        
        // Mark the end of request handling
        $debugger->markPerformance('Request End');
        
        // Only inject debug bar for HTML responses in non-production environments
        if ($this->shouldInjectDebugBar($request, $response)) {
            $this->injectDebugBar($response, $debugger);
        }
        
        return $response;
    }
    
    /**
     * Determine if debug bar should be injected
     */
    protected function shouldInjectDebugBar(Request $request, Response $response): bool
    {
        // Don't inject for AJAX requests
        if ($request->isXmlHttpRequest()) {
            return false;
        }
        
        // Don't inject for API requests
        if (strpos($request->getUri(), '/api/') === 0) {
            return false;
        }
        
        // Check if response is HTML
        $contentType = $request->headers()->get('Content-Type') ?? '';
        if (stripos($contentType, 'text/html') === false && !empty($contentType)) {
            return false;
        }
        
        // Check environment
        $env = $_ENV['APP_ENV'] ?? 'production';
        if ($env === 'production') {
            return false;
        }
        
        return true;
    }
    
    /**
     * Inject debug bar into response
     */
    protected function injectDebugBar(Response $response, PlugDebugger $debugger): void
    {
        $content = $response->getContent();
        
        // Check if content is a string and has a closing body tag
        if (!is_string($content) || stripos($content, '</body>') === false) {
            return;
        }
        
        // Get debug bar HTML
        $debugBarHtml = $debugger->render();
        
        // Inject before closing body tag
        $content = str_ireplace('</body>', $debugBarHtml . "\n</body>", $content);
        
        // Update response content
        $response->setContent($content);
    }
}