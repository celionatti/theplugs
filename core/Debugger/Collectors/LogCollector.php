<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class LogCollector implements CollectorInterface
{
    private $logs = [];
    
    public function log($level, $message, $context = [])
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'time' => microtime(true),
        ];
    }
    
    public function collect()
    {
        return $this->logs;
    }
}