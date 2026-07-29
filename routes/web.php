<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// downloads
Route::get('/downloads/{filename}', function ($filename) {
    $path = storage_path('downloads/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->download($path);
})->name('downloads');
