<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Validation;

use Plugs\Exceptions\Controller\ControllerException;

class ValidationException extends ControllerException
{
    private array $errors;

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}