<?php

declare(strict_types=1);

namespace Plugs\Console;

use Plugs\Console\Support\Input;
use Plugs\Console\Support\Output;
use Plugs\Console\Contracts\CommandInterface;

abstract class Command implements CommandInterface
{
    protected string $name;
    protected string $description = '';

    protected Input $input;
    protected Output $output;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string { return $this->name; }

    public function description(): string { return $this->description; }

    public function setIO(Input $input, Output $output): void
    {
        $this->input  = $input;
        $this->output = $output;
    }

    protected function argument(string $key, ?string $default = null): ?string
    {
        return $this->input->arguments[$key] ?? $default;
    }

    protected function option(string $key, string|int|bool|null $default = null): string|int|bool|null
    {
        return $this->input->options[$key] ?? $default;
    }
}