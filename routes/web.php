<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Routes File - Web Routes
|--------------------------------------------------------------------------
|
| This file is where you can define all of the routes that are handled
| by your application. Just tell the router the URIs it should respond
| to and give it the controller to call when that URI is requested.
| Also - 
| Using the Route facade for clean, static route definitions.
*/

use Plugs\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlugsController;

Route::get('/home', [HomeController::class, 'index']);

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/docs', [PlugsController::class, 'docs'])->name('docs');

Route::get('/examples', [PlugsController::class, 'examples'])->name('examples');

// Route::group(['prefix' => 'admin'], function () {
//     Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
// });