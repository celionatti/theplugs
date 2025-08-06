<?php

declare(strict_types=1);

use Plugs\Http\Router\Route;

Route::get('/', function() {
    return '<h1>Welcome to Plugs Framework!</h1>';
});

Route::get('/user', function() {
    return '<h1>Welcome to Plugs Framework!</h1> <br> This is the user page.';
});

Route::get('/about', 'HomeController@about');