<?php

declare(strict_types=1);

namespace Plugs\Exceptions\View;

use Exception;

class ViewException extends Exception
{
    public function __construct(string $message, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}