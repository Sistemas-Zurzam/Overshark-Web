<?php

use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\LoginController;
use App\Http\Controllers\Customer\PasswordResetController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\RegisterController;
use App\Http\Controllers\Customer\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Cuentas de compradores (guard "customer"). Todo es opcional: el checkout de invitados no depende de estas rutas.
// Los nombres login, password.* y verification.* son los que esperan los middleware y las notificaciones de Laravel.

Route::middleware('guest:customer')->group(function () {
    Route::get('/registro', [RegisterController::class, 'create'])->name('customer.register');
    Route::post('/registro', [RegisterController::class, 'store'])->middleware('throttle:customer-register')->name('customer.register.store');

    Route::get('/iniciar-sesion', [LoginController::class, 'create'])->name('login');
    Route::post('/iniciar-sesion', [LoginController::class, 'store'])->middleware('throttle:customer-login')->name('customer.login.store');

    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'email'])->middleware('throttle:customer-password')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])->middleware('throttle:customer-password')->name('password.update');
});

Route::middleware('auth:customer')->group(function () {
    Route::post('/cerrar-sesion', [LoginController::class, 'destroy'])->name('customer.logout');

    Route::get('/verificar-correo/{id}/{hash}', [VerifyEmailController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/verificar-correo/reenviar', [VerifyEmailController::class, 'send'])->middleware('throttle:3,1')->name('verification.send');

    Route::prefix('mi-cuenta')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('customer.account');
        Route::get('/pedidos', [AccountController::class, 'orders'])->name('customer.orders');
        Route::get('/pedidos/{order}', [AccountController::class, 'showOrder'])->whereNumber('order')->name('customer.orders.show');

        Route::get('/perfil', [ProfileController::class, 'edit'])->name('customer.profile');
        Route::put('/perfil', [ProfileController::class, 'update'])->name('customer.profile.update');
        Route::put('/perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('customer.password.update');
        Route::post('/perfil/correo', [ProfileController::class, 'requestEmailChange'])->middleware('throttle:3,1')->name('customer.email.request');
        Route::get('/perfil/correo/{id}/{hash}', [ProfileController::class, 'confirmEmailChange'])->middleware(['signed', 'throttle:6,1'])->name('customer.email.confirm');

        Route::get('/direcciones', [AddressController::class, 'index'])->name('customer.addresses');
        Route::post('/direcciones', [AddressController::class, 'store'])->name('customer.addresses.store');
        Route::get('/direcciones/{address}/editar', [AddressController::class, 'edit'])->whereNumber('address')->name('customer.addresses.edit');
        Route::put('/direcciones/{address}', [AddressController::class, 'update'])->whereNumber('address')->name('customer.addresses.update');
        Route::patch('/direcciones/{address}/principal', [AddressController::class, 'makeDefault'])->whereNumber('address')->name('customer.addresses.default');
        Route::delete('/direcciones/{address}', [AddressController::class, 'destroy'])->whereNumber('address')->name('customer.addresses.destroy');
    });
});
