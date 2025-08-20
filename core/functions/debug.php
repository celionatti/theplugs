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

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and continue execution.
     *
     * @param mixed ...$vars
     * @return void
     */
    function dd(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        exit(1);
    }
}