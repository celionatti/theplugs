<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Routings;

use Exception;

class MethodNotAllowedException extends Exception
{
    private array $allowedMethods;

    public function __construct(array $allowedMethods, string $method, string $uri)
    {
        $this->allowedMethods = $allowedMethods;
        $allowed = implode(', ', $allowedMethods);
        parent::__construct("Method {$method} not allowed for {$uri}. Allowed methods: {$allowed}");
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}