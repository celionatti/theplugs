<?php

declare(strict_types=1);

namespace Plugs\Exceptions\LiveHTML;

use Exception;

class LiveHTMLException extends Exception 
{
    public function __construct(string $message = "LiveHTML Exception", int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}