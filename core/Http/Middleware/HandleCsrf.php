<?php

declare(strict_types=1);

namespace Plugs\Http\Middleware;

use Plugs\Http\Response\Response;
use Plugs\Middleware\MiddlewareInterface;

class HandleCsrf implements MiddlewareInterface
{
    public function handle($request, $next): Response
    {
        // CSRF protection logic here
        return $next($request);
    }
}