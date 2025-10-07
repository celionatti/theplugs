<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class FileCollector implements CollectorInterface
{
    public function collect()
    {
        return get_included_files();
    }
}