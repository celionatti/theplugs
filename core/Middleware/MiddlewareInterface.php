<?php

declare(strict_types=1);

namespace Plugs\Middleware;

use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

interface MiddlewareInterface
{
    /**
     * Handle the request and call the next middleware in the stack
     */
    public function handle(Request $request, callable $next): Response;
}