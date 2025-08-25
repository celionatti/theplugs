<?php

declare(strict_types=1);

namespace Plugs\View\Contracts;

use Plugs\View\Contracts\ViewInterface;

interface ViewFactoryInterface
{
    public function make(string $view, array $data = []): ViewInterface;
    public function exists(string $view): bool;
    public function addLocation(string $location): void;
    public function addExtension(string $extension, string $engine): void;
    public function addNamespace(string $namespace, string $hints): void;
    public function share(string|array $key, mixed $value = null): void;
}