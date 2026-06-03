<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.home');
})->name('web.home');
