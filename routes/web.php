<?php

use Illuminate\Support\Facades\Route;

/**
 * User Interface Routes
 * These routes serve the Blade views for the application.
 * Business logic and auth are handled via /api/... routes.
 */

Route::get('/', function () {
    return view('welcome');
})->name('home');

// We still keep the dashboard route for standard Laravel Breeze flow if needed,
// but it's recommended to handle login/logout via API.
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
