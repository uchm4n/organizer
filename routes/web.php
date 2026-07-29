<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Download\ShowDownloadController;

Route::view('/', 'welcome')->name('home');

Route::get('/downloads/{filename}', ShowDownloadController::class)
    ->name('downloads')
    ->middleware('throttle:60,1');
