<?php

declare(strict_types=1);

use Plugs\Dumper\Dumper;

if(!function_exists('dump')) {
    function dump(...$args)
    {
        Dumper::dump(...$args);
        exit;
    }
}

if(!function_exists('quick_dump')) {
    function quick_dump(...$args)
    {
        Dumper::quickDump(...$args);
        exit;
    }
}