<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class)->middleware('throttle:login')->name('api.login');
Route::as('api.v1.')
    ->middleware(['auth:sanctum'])
    // ->middleware(['sunset:2026-08-30,2026-09-30']) // We have ability to sunset the endpoint
    ->group(function () {

        Route::get('/user', CurrentUserController::class)->name('user.show');

    });
