<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DeviceApiController;
use App\Http\Controllers\Api\SongApiController;
use App\Http\Controllers\Api\TypeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login']);

    Route::middleware('api.token')->group(function () {
        Route::get('/me', [AuthApiController::class, 'me']);
        Route::post('/logout', [AuthApiController::class, 'logout']);

        Route::get('/songs', [SongApiController::class, 'index']);
        Route::get('/songs/{song}', [SongApiController::class, 'show']);
        Route::put('/songs/{song}', [SongApiController::class, 'update']);

        Route::get('/mqtt/latest', [DeviceApiController::class, 'latest']);
        Route::get('/types', [TypeApiController::class, 'index']);
    });
});
