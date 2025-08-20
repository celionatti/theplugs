<?php

declare(strict_types=1);

namespace Plugs\Console;

use Throwable;
use Plugs\Console\ConsoleKernel;
use Plugs\Console\Support\Output;
use Plugs\Console\Support\ArgvParser;

class ConsolePlugs
{
    public function __construct(private ConsoleKernel $kernel) {}

    public function run(array $argv): int
    {
        $parser = new ArgvParser($argv);
        $name   = $parser->commandName() ?? 'help';
        $input  = $parser->input();
        $output = new Output();

        try {
            $command = $this->kernel->resolve($name);
            if ($command === null) {
                $output->error("Command '$name' not found. See 'help'.");
                return 1;
            }
            $command->setIO($input, $output);
            return (int) $command->handle();
        } catch (Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }
    }
}