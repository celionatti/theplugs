<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class RouteListCommand extends Command
{
    protected string $description = 'Display routes from routes/web.php & routes/api.php';

    public function handle(): int
    {
        $files = array_filter(['routes/web.php','routes/api.php'], 'file_exists');
        $routes = [];
        foreach ($files as $file) {
            /** @var array<int,array{0:string,1:string,2:string,3?:string}> $arr */
            $arr = require $file; // each: [METHOD, URI, Action, Group]
            foreach ($arr as $r) {
                $routes[] = [$r[0] ?? '', $r[1] ?? '', $r[2] ?? '', $r[3] ?? basename($file, '.php')];
            }
        }
        $this->output->table(['Method','URI','Action','From'], $routes);
        return 0;
    }
}