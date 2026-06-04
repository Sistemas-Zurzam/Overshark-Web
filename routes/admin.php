<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandSettingController;
use App\Http\Controllers\Admin\ComboController;
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
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::patch('/banners/{banner}/estado', [BannerController::class, 'toggle'])->name('banners.toggle');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    Route::get('/combos', [ComboController::class, 'index'])->name('combos.index');
    Route::post('/combos', [ComboController::class, 'store'])->name('combos.store');
    Route::patch('/combos/{combo}/estado', [ComboController::class, 'toggle'])->name('combos.toggle');
    Route::delete('/combos/{combo}', [ComboController::class, 'destroy'])->name('combos.destroy');
    Route::get('/identidad-visual', [BrandSettingController::class, 'edit'])->name('brand.edit');
    Route::put('/identidad-visual', [BrandSettingController::class, 'update'])->name('brand.update');

    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('token.refresh');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
