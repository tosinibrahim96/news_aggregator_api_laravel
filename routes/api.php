<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PreferencesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::prefix('articles')->group(function () {
    Route::get('search', [ArticleController::class, 'search']);
});

Route::middleware('auth:api')->group(function () {
    
    Route::prefix('preferences')->group(function () {
        Route::get('', [PreferencesController::class, 'index']);
        Route::put('', [PreferencesController::class, 'update']);
    });
});