<?php

declare(strict_types=1);

namespace Plugs\Exceptions\View;

use Plugs\Exceptions\View\ViewException;

class ViewNotFoundException extends ViewException
{
    public function __construct(string $view, array $paths = [])
    {
        $pathsStr = implode(', ', $paths);
        $message = "View [{$view}] not found. Searched in: {$pathsStr}";
        parent::__construct($message, $view);
    }
}