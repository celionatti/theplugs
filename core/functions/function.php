<?php

declare(strict_types=1);

use Plugs\View\View;
use Plugs\Database\Eloquent\Collection;

if (!function_exists("view")) {
    function view(string $template, array $data = []): View
    {
        return View::make($template, $data);
    }
}

if (!function_exists('collect')) {
    function collect($value = null) {
        return new Collection($value);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object/class.
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
