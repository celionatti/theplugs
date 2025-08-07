<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Controller;

use Plugs\Exceptions\Controller\ControllerException;

class MiddlewareNotFoundException extends ControllerException
{
    public function __construct(string $middleware)
    {
        parent::__construct("Middleware '{$middleware}' not found");
    }
}