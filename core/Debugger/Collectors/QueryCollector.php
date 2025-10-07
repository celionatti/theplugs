<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class QueryCollector implements CollectorInterface
{
    private $queries = [];
    
    public function addQuery($sql, $bindings = [], $time = 0)
    {
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => round($time, 2),
        ];
    }
    
    public function collect()
    {
        return $this->queries;
    }
}