<?php

declare(strict_types=1);

namespace Plugs\View\Contracts;

interface EngineInterface
{
    public function get(string $path, array $data = []): string;
}