<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Str;
use Plugs\Console\Support\Filesystem as FS;

class MakeModelCommand extends Command
{
    protected string $description = 'Create a model in app/Models';

    public function handle(): int
    {
        $name = $this->argument('0') ?? 'Sample';
        $studly = Str::studly($name);
        $code = <<<PHP
        <?php
        declare(strict_types=1);

        namespace App\Models;

        class {$studly}
        {
            // Define fillable, casts, relations, etc.
        }
        PHP;
        FS::put("app/Models/{$studly}.php", $code);
        $this->output->success("Model created: app/Models/{$studly}.php");
        return 0;
    }
}