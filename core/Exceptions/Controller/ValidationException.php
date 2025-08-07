<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Controller;

use Exception;

class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = "Validation failed", int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstKey = array_key_first($this->errors);
        return is_array($this->errors[$firstKey]) 
            ? $this->errors[$firstKey][0] 
            : $this->errors[$firstKey];
    }
}