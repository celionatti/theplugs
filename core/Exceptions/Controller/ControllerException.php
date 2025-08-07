<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Controller;

use Exception;

class ControllerException extends Exception 
{
    public function __construct(string $message = "Controller Exception", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}