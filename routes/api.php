<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class)->middleware('throttle:login')->name('api.login');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
