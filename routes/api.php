<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Middleware\AuthenticateApiKey;
use Illuminate\Support\Facades\Route;

/*
| API de Overshark para sistemas externos (Admin → Configuración → Integraciones). Prefijo /api/v1.
| Autenticación: Authorization: Bearer osk_... (o X-Api-Key). 120 solicitudes por minuto por clave.
*/
Route::prefix('v1')->middleware('throttle:overshark-api')->group(function () {
    // Prueba de conexión: datos de la clave usada (sin permisos especiales).
    Route::get('/ping', fn (\Illuminate\Http\Request $request) => response()->json([
        'ok' => true,
        'key' => $request->attributes->get(AuthenticateApiKey::ATTRIBUTE)?->only(['name', 'token_prefix', 'abilities']),
    ]))->middleware('api.key');

    Route::get('/products', [CatalogController::class, 'index'])->middleware('api.key:catalog.read');
    Route::get('/products/{id}', [CatalogController::class, 'show'])->whereNumber('id')->middleware('api.key:catalog.read');

    Route::get('/orders', [OrderController::class, 'index'])->middleware('api.key:orders.read');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->middleware('api.key:orders.read');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->whereNumber('order')->middleware('api.key:orders.write');
});
