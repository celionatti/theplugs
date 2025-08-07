<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Controller;

use Plugs\Exceptions\Controller\ControllerException;

class DependencyResolutionException extends ControllerException
{
    public function __construct(string $dependency, string $context)
    {
        parent::__construct("Cannot resolve dependency '{$dependency}' in {$context}");
    }
}