<?php

declare(strict_types=1);

namespace ThePlugs\examples;

use ThePlugs\src\Http\Request\Request;
use ThePlugs\src\Http\Response\Response;
use ThePlugs\src\Middleware\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}