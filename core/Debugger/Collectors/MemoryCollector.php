<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class MemoryCollector implements CollectorInterface
{
    public function collect()
    {
        return [
            'current' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
        ];
    }
}