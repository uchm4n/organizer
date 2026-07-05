<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\User\UserShowController;
use Illuminate\Support\Facades\Route;

Route::as('api.v1.')->group(function (): void {

    Route::post('/login', LoginController::class)->middleware('throttle:login')->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        // ->middleware(['sunset:2026-08-30,2026-09-30']) // We have ability to sunset the endpoint

        Route::get('/user', UserShowController::class)->name('user.show');
    });
});
