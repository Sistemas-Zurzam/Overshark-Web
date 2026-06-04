<?php

use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.home', [
        'banner' => BannerPortada::query()->where('status', true)->latest()->first(),
        'combos' => Combo::query()
            ->where('status', true)
            ->whereNotNull('imagen')
            ->latest()
            ->get(),
    ]);
})->name('web.home');
