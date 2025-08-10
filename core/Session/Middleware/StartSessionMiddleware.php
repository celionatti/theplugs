<?php

declare(strict_types=1);

namespace Plugs\Session\Middleware;

use Plugs\Session\SessionManager;

class StartSessionMiddleware
{
    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    /**
     * Handle the request
     */
    public function handle($request, \Closure $next)
    {
        // Start the session
        $this->session->start();
        
        // Add session to request
        $request->setSession($this->session);
        
        // Process the request
        $response = $next($request);
        
        // Session is automatically saved by PHP's session handler
        
        return $response;
    }
}