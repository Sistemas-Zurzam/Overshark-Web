<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('zazu:sync-products')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('zazu:sync-agencies')
    ->everySixHours()
    ->withoutOverlapping();

// Pedidos pagados con Yape cuyo QR venció sin pagarse: se cancelan (App\Services\Orders\OrderPayments).
Artisan::command('pedidos:vencer-qr', function () {
    $this->info(\App\Services\Orders\OrderPayments::expireQrs().' pedidos cancelados por QR vencido.');
})->purpose('Cancela los pedidos con QR de Yape vencido');

Schedule::command('pedidos:vencer-qr')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
