<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class ConfigCollector implements CollectorInterface
{
    private $config = [];
    
    public function setConfig($config)
    {
        $this->config = $config;
    }
    
    public function collect()
    {
        return $this->config;
    }
}