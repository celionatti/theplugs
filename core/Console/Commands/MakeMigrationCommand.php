<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Str;
use Plugs\Console\Support\Filesystem as FS;

class MakeMigrationCommand extends Command
{
    protected string $description = 'Create a migration in database/migrations';

    public function handle(): int
    {
        $name = $this->argument('0') ?? 'sample_table';
        $snake = Str::snake($name);
        $timestamp = date('Y_m_d_His');
        $file = "database/migrations/{$timestamp}_{$snake}.php";
        $code = <<<PHP
        <?php
        declare(strict_types=1);

        return new class {
            public function up(): void
            {
                // TODO: create table
            }

            public function down(): void
            {
                // TODO: drop table
            }
        };
        PHP;
        FS::put($file, $code);
        $this->output->success("Migration created: {$file}");
        return 0;
    }
}