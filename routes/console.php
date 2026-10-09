<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('zazu:sync-products')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Las fotos del probador se conservan como máximo OutfitPhotoStore::RETENTION_HOURS (24 h).
Schedule::command('outfit:purge')
    ->hourly()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
