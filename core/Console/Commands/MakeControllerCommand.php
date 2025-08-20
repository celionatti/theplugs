<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Str;
use Plugs\Console\Support\Filesystem as FS;

class MakeControllerCommand extends Command
{
    protected string $description = 'Create a controller in app/Controllers';

    public function handle(): int
    {
        $name = $this->argument('0') ?? 'SampleController';
        $studly = Str::studly($name);
        $isResource = (bool)($this->option('resource') ?? $this->option('r') ?? false);
        $id = $isResource ? '$id' : '';

        $methods = $isResource
            ? "\n    public function index(){}\n    public function create(){}\n    public function store(){}\n    public function show($id){}\n    public function edit($id){}\n    public function update($id){}\n    public function destroy($id){}\n"
            : "\n    public function index(){}\n";

        $code = <<<PHP
        <?php
        declare(strict_types=1);

        namespace App\Controllers;

        class {$studly}
        {
        {$methods}}
        PHP;
        FS::put("app/Controllers/{$studly}.php", $code);
        $this->output->success("Controller created: app/Controllers/{$studly}.php");
        return 0;
    }
}