<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Routings;

use Exception;

class NotFoundHttpException extends Exception
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404);
    }
}