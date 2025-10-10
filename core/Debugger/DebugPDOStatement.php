<?php

declare(strict_types=1);

namespace Plugs\Debugger;

use PDOStatement;
use Plugs\Debugger\PlugDebugger;

class DebugPDOStatement extends PDOStatement
{
    private PlugDebugger $debugger;
    private array $params = [];
    private float $startTime;

    protected function __construct(PlugDebugger $debugger)
    {
        $this->debugger = $debugger;
    }

    public function execute(?array $params = null): bool
    {
        $this->startTime = microtime(true);
        $this->params = $params ?? [];
        
        $result = parent::execute($params);
        
        $executionTime = microtime(true) - $this->startTime;
        $this->debugger->logQuery($this->queryString, $this->params, $executionTime, 'pdo');
        
        return $result;
    }
}