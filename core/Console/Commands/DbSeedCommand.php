<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class DbSeedCommand extends Command
{
    protected string $description = 'Run seeder classes from database/seeds';

    public function handle(): int
    {
        $dir = 'database/seeds';
        if (!is_dir($dir)) {
            $this->output->warning('No seeds directory found.');
            return 0;
        }
        $count = 0;
        foreach (scandir($dir) ?: [] as $file) {
            if (!str_ends_with((string)$file, '.php')) continue;
            $seeder = require $dir . '/' . $file; // seeder returns a callable or object with run()
            if (is_callable($seeder)) {
                $seeder();
                $count++;
            } elseif (is_object($seeder) && method_exists($seeder, 'run')) {
                $seeder->run();
                $count++;
            }
        }
        $this->output->success("Seeders executed: {$count}");
        return 0;
    }
}