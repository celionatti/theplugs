<?php

declare(strict_types=1);

namespace Plugs\View\Contracts;

interface ViewInterface
{
    public function getName(): string;
    public function getData(): array;
    public function with(string|array $key, mixed $value = null): self;
    public function render(): string;
}