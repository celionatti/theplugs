<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Filesystem as FS;

class ConfigCacheCommand extends Command
{
    protected string $description = 'Compile configs from /config into one cached file';

    public function handle(): int
    {
        $dir = 'config';
        $data = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) ?: [] as $f) {
                if (str_ends_with((string)$f, '.php')) {
                    /** @var array $cfg */
                    $cfg = require $dir . '/' . $f;
                    $data[basename((string)$f, '.php')] = $cfg;
                }
            }
        }
        FS::put('storage/cache/config.php', '<?php return ' . var_export($data, true) . ';');
        $this->output->success('Config cached to storage/cache/config.php');
        return 0;
    }
}