<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('social-login', 'socialLogin');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
});

// Rotte protette per conversazioni e messaggi
Route::controller(ConversationController::class)
    ->prefix('conversations')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{id}', 'show');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
        Route::get('/user', 'getByAuthenticatedUser');
    });

Route::controller(MessageController::class)
    ->prefix('messages')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('conversation/{conversationId}', 'index');
        Route::post('conversation/{conversationId}', 'store');
        Route::get('{id}', 'show');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
    });
