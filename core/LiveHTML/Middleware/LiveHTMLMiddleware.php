<?php

declare(strict_types=1);

namespace Plugs\LiveHTML\Middleware;

use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\LiveHTML\LiveHTMLHandler;

class LiveHTMLMiddleware
{
    protected LiveHTMLHandler $handler;

    public function __construct(LiveHTMLHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Check if this is a LiveHTML request
        if ($this->handler->isLiveHTMLRequest($request)) {
            $response = $this->handler->handleRequest($request);
            
            if ($response instanceof Response) {
                return $response;
            }
        }

        // Continue to next middleware
        return $next($request);
    }
}