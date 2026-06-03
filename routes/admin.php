<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

Route::middleware('jwt.auth')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('/pedidos', 'admin.pedidos.index')->name('pedidos.index');
    Route::view('/clientes', 'admin.clientes.index')->name('clientes.index');
    Route::view('/usuarios', 'admin.usuarios.index')->name('usuarios.index');
    Route::view('/bodegas', 'admin.bodegas.index')->name('bodegas.index');
    Route::view('/productos', 'admin.productos.index')->name('productos.index');
    Route::view('/medios-pago', 'admin.medios-pago.index')->name('medios-pago.index');
    Route::view('/banners', 'admin.banners.index')->name('banners.index');

    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('token.refresh');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
