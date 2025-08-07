<?php

declare(strict_types=1);

use Plugs\View\View;

if (!function_exists("view")) {
    function view(string $template, array $data = []): View
    {
        return View::make($template, $data);
    }
}
