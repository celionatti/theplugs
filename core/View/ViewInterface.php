<?php

declare(strict_types=1);

namespace Plugs\View;

interface ViewInterface
{
    public static function make(string $template, array $data = []): static;
    public function render(): string;
}