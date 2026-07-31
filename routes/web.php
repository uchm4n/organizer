<?php

use App\Http\Controllers\Download\ShowDownloadController;
use App\Http\Middleware\LogDownload;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/downloads/{filename}', ShowDownloadController::class)
    ->name('downloads')
    ->middleware([LogDownload::class, 'throttle:60,1']);
