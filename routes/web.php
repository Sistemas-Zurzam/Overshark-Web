<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.home');
})->name('web.home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
});
