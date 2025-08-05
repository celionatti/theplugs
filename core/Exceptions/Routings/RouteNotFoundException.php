<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Routings;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct(string $method, string $uri)
    {
        parent::__construct("No route found for {$method} {$uri}");
    }
}
