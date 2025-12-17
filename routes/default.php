<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Routes File - Default Routes
|--------------------------------------------------------------------------
|
| This file is where you can define all of the routes that are handled
| by your application. Just tell the router the URIs it should respond
| to and give it the controller to call when that URI is requested.
| Also - 
| Using the Route facade for clean, static route definitions.
*/

use Plugs\Facades\Route;
use Plugs\Http\Controllers\Debug\PerformanceController;


Route::group(['prefix' => 'debug'], function ($router) {
    $router->get('/performance', [PerformanceController::class, 'index']);
    $router->get('/performance/latest', [PerformanceController::class, 'latest']);
    $router->get('/performance/{id}', [PerformanceController::class, 'show']);
});