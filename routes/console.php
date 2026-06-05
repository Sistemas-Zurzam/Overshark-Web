<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use App\Services\OdooProductSyncService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('odoo:sync-products {--force : Sync even when automatic sync is disabled}', function (OdooProductSyncService $syncService) {
    if (! $this->option('force') && ! Cache::get('odoo.products.sync_enabled', false)) {
        $this->info('Odoo product sync is disabled.');

        return 0;
    }

    $result = $syncService->syncProducts();

    $this->info("Odoo products synced: {$result['total']} total, {$result['created']} created, {$result['updated']} updated.");

    return 0;
})->purpose('Sync products from Odoo');

Schedule::command('odoo:sync-products')
    ->everyMinute()
    ->withoutOverlapping();
