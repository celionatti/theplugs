<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;

class CacheClearCommand extends Command
{
    protected string $description = 'Clear cached config/routes/views in storage/cache';

    public function handle(): int
    {
        $paths = ['storage/cache/config.php','storage/cache/routes.php','storage/cache/views.php'];
        $ok = true;
        foreach ($paths as $p) {
            if (file_exists($p)) $ok = $ok && unlink($p);
        }
        $this->output->success('Cache cleared.');
        return $ok ? 0 : 1;
    }
}