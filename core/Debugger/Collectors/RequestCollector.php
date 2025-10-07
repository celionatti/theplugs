<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class RequestCollector implements CollectorInterface
{
    public function collect()
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'get' => $_GET,
            'post' => $_POST,
            'server' => $_SERVER,
        ];
    }
}