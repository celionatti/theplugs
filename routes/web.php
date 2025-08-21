<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Plugs\Http\Router\Route;

Route::get('/', function() {
    return '<h1>Welcome to Plugs Framework!</h1>';
});

Route::get('/user', function() {
    return '<h1>Welcome to Plugs Framework!</h1> <br> This is the user page.';
});

Route::get('/contact', [HomeController::class, 'contact'])->name('contact')->middleware('auth');

Route::get('/about', 'App\Controllers\HomeController@about');

Route::get('/show/{id}', 'App\Controllers\HomeController@show');

Route::post('/show/{id}', 'App\Controllers\HomeController@post');

Route::delete('/show/{id}', 'App\Controllers\HomeController@delete');