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

// Pedidos que esperaban el pago con tarjeta y no se pagaron a tiempo: se cancelan (App\Services\Orders\OrderPayments).
Artisan::command('pedidos:vencer-qr', function () {
    $this->info(\App\Services\Orders\OrderPayments::expireQrs().' pedidos cancelados por pago vencido.');
})->purpose('Cancela los pedidos con el pago con tarjeta vencido');

Schedule::command('pedidos:vencer-qr')->everyMinute()->withoutOverlapping();

// Borra de la base los productos de empresas que la tienda no vende (BOX PRIME). La sincronización con Zazu ya lo hace;
// este comando sirve para limpiar de una vez sin esperar la sincronización (p. ej. al desplegar).
Artisan::command('productos:quitar-empresas-ajenas', function () {
    $this->info(\App\Models\Admin\Producto::removeForeignCompanies().' productos de otras empresas borrados.');
})->purpose('Borra los productos de empresas fuera de ZAZU_COMPANIES (BOX PRIME)');

// Archivos que se subieron pero no quedaron en ningún registro: borradores de reclamos (más de un día) y comprobantes
// de pago de compras que no se terminaron (más de dos días).
Artisan::command('limpieza:archivos-huerfanos', function () {
    $drafts = 0;
    foreach (\Illuminate\Support\Facades\Storage::files('reclamos/borradores') as $file) {
        if (\Illuminate\Support\Facades\Storage::lastModified($file) < now()->subDay()->getTimestamp()) {
            \Illuminate\Support\Facades\Storage::delete($file);
            $drafts++;
        }
    }

    $used = \App\Models\Admin\Order::query()->whereNotNull('comprobante_pago')->pluck('comprobante_pago')->all();
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $receipts = 0;
    foreach ($disk->files('payment-receipts') as $file) {
        if (! in_array($file, $used, true) && $disk->lastModified($file) < now()->subDays(2)->getTimestamp()) {
            $disk->delete($file);
            $receipts++;
        }
    }

    $this->info("{$drafts} borradores de reclamos y {$receipts} comprobantes sin pedido eliminados.");
})->purpose('Borra archivos subidos que quedaron sin registro');

Schedule::command('limpieza:archivos-huerfanos')->daily()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
