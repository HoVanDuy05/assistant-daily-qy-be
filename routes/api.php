<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/register', [RegisteredUserController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Chat
    Route::post('/chat', ChatController::class);
    Route::get('/chat/stream', [ChatController::class, 'stream']); // SSE usually uses GET for simplicity in PHP

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('api.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('api.profile.destroy');
    
    // Auth
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::put('/password', [PasswordController::class, 'update'])->name('api.password.update');
});
