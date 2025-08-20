<?php

declare(strict_types=1);

namespace Plugs\Console\Contracts;

use Plugs\Console\Support\Input;
use Plugs\Console\Support\Output;

interface CommandInterface
{
    public function name(): string;
    public function description(): string;
    public function handle(): int;
    public function setIO(Input $input, Output $output): void;
}