<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Filesystem as FS;

class make-framework extends Command
{
    protected string $description = 'Generate default framework structure.';

    public function handle(): int
    {
        $dirs = [
            'app/Controllers', 'app/Models', 'app/Services',
            'config', 'routes',
            'database/migrations', 'database/seeds',
            'public', 'resources/views', 'resources/assets',
            'storage/cache', 'tests'
        ];
        foreach ($dirs as $d) FS::ensureDir($d);

        // seed default route files
        if (!file_exists('routes/web.php')) {
            FS::put('routes/web.php', "<?php\nreturn [\n    ['GET','/','HomeController@index','web'],\n];\n");
        }
        if (!file_exists('routes/api.php')) {
            FS::put('routes/api.php', "<?php\nreturn [\n    ['GET','/api/ping','Api\\PingController@ping','api'],\n];\n");
        }

        $this->output->success('Framework directories created.');
        return 0;
    }
}