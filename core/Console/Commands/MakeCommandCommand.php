<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Str;
use Plugs\Console\Support\Filesystem as FS;

class MakeCommandCommand extends Command
{
    protected string $description = 'Scaffold a new CLI command class';

    public function handle(): int
    {
        $name = $this->argument('0') ?? 'example:task';
        $class = Str::studly(str_replace(':', ' ', $name)) . 'Command';
        $path  = "app/Console/{$class}.php";
        $code = <<<PHP
        <?php
        declare(strict_types=1);

        namespace App\Console;

        use App\Console\Command;

        class {$class} extends Command
        {
            protected string \$description = 'Describe your command';

            public function handle(): int
            {
                \$this->output->info('Running {$name} ...');
                // Your logic here
                return 0;
            }
        }
        PHP;
        FS::put($path, $code);
        $this->output->success("Command scaffolded: {$path}");
        return 0;
    }
}