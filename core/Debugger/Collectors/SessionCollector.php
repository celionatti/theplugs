<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class SessionCollector implements CollectorInterface
{
    public function collect()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return $_SESSION;
        }
        return [];
    }
}