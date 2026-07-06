<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\User\UserIndexController;
use App\Http\Controllers\Api\User\UserRoleShowController;
use App\Http\Controllers\Api\User\UserRoleUpdateController;
use App\Http\Controllers\Api\User\UserShowController;
use Illuminate\Support\Facades\Route;

Route::as('api.v1.')
    ->middleware('throttle:api')
    ->group(function (): void {
        Route::post('/login', LoginController::class)->middleware('throttle:login')->name('auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/user', UserShowController::class)->name('user.show');

            // Admin-only endpoints
            Route::middleware('role:admin')->group(function (): void {
                Route::get('/users', UserIndexController::class)->name('user.index');
                Route::get('/users/{user}/role', UserRoleShowController::class)->name('user.role.show');
                Route::patch('/users/{user}/role', UserRoleUpdateController::class)->name('user.role.update');
            });
        });
    });
