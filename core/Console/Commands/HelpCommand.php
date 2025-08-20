<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\ConsoleKernel;

class HelpCommand extends Command
{
    protected string $description = 'List all available commands and descriptions';

    public function handle(): int
    {
        $kernel = new ConsoleKernel();
        $headers = ['Command', 'Description'];
        $rows = [];
        foreach ($kernel->commands() as $name => $class) {
            $rows[] = [$name, (new $class($name))->description()];
        }
        $this->output->table($headers, $rows);
        return 0;
    }
}