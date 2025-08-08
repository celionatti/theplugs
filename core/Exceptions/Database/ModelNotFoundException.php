<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Database;

use Exception;

class ModelNotFoundException extends Exception
{
    protected $message = 'Model not found';
}