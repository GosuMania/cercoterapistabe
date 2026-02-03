<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TherapistCenterRelationshipController;

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    // Route::post('register', 'register');
    // Route::post('login', 'login');
    Route::post('login-or-register', 'loginOrRegister');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
});

Route::controller(UserController::class)->prefix('user')->group(function () {
    Route::get('get-all-users', 'index')->middleware('auth:sanctum');
    Route::get('get-info-user', 'getInfoUser')->middleware('auth:sanctum');
    Route::get('get-saved-users', 'getSavedUsers')->middleware('auth:sanctum');
    Route::post('toggle-saved-user', 'toggleSavedUser')->middleware('auth:sanctum');
    Route::post('search', 'search')->middleware('auth:sanctum');
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

    // Rotte protette per conversazioni e messaggi
    Route::controller(ConversationController::class)
        ->prefix('relationships')
        ->middleware('auth:sanctum')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::put('{id}', 'update');
        });
