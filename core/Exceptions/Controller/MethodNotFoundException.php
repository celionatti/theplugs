<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Controller;

use Exception;

class MethodNotFoundException extends Exception
{
    public function __construct(
        string $className, 
        string $methodName, 
        int $code = 0, 
        ?Exception $previous = null
    ) {
        $message = sprintf(
            'Method %s::%s() does not exist', 
            $className, 
            $methodName
        );
        
        parent::__construct($message, $code, $previous);
    }
}