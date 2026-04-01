<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use Illuminate\Http\Request;

use App\Http\Controllers\CommandController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
});

// Testing Real-time
Route::get('/test-broadcast', function (Request $request) {
    $msg = $request->query('message', 'Đây là thông báo Real-time từ Reverb!');
    event(new \App\Events\SystemMessageEvent($msg));
    return response()->json(['status' => 'Broadcasted!', 'message' => $msg]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Auth Info
    Route::get('/auth/me', [AuthenticatedSessionController::class, 'me']);

    // AI Test
    Route::get('/test-ai', function () {
        $client = Gemini::client(config('services.gemini.key'));
        return response()->json($client->models()->list());
    });

    // User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Chat
    Route::post('/chat', ChatController::class);
    Route::get('/chat/stream', [ChatController::class, 'stream']);

    // Commands
    Route::post('/commands/execute', [CommandController::class, 'execute']);
    Route::get('/commands/history', [CommandController::class, 'history']);

    // Reminders
    Route::get('/reminders', [CommandController::class, 'getReminders']);
    Route::delete('/reminders/{id}', [CommandController::class, 'deleteReminder']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('api.profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('api.profile.destroy');

    // Auth
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::put('/password', [PasswordController::class, 'update'])->name('api.password.update');
});
