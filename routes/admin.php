<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BodegaController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BrandSettingController;
use App\Http\Controllers\Admin\CartSuggestionController;
use App\Http\Controllers\Admin\ComboController;
use App\Http\Controllers\Admin\LibroReclamacionController;
use App\Http\Controllers\Admin\MetodoPagoController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Models\Admin\Cliente;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Brand;
use App\Models\ContactMessage;
use App\Models\LibroReclamacion;
use App\Services\Orders\OrderPayments;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::get('/invitacion/{token}', [PasswordResetController::class, 'edit'])->middleware('throttle:10,1')->name('invite.edit');
Route::post('/invitacion', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('invite.update');

Route::middleware('jwt.auth')->group(function () {
    Route::get('/', function () {
        $now = now();
        $periodStart = $now->copy()->subDays(30);
        $prevPeriodStart = $now->copy()->subDays(60);

        $paidOrdersBetween = fn ($from, $to) => Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('statusOrder', fn ($query) => $query->whereIn('name', OrderPayments::PAID_STATUSES));

        return view('admin.dashboard', [
            'ventas30' => (clone $paidOrdersBetween($periodStart, $now))->sum('pago_monto'),
            'ventasPrev30' => (clone $paidOrdersBetween($prevPeriodStart, $periodStart))->sum('pago_monto'),
            'pedidos30' => Order::query()->whereBetween('created_at', [$periodStart, $now])->count(),
            'pedidosPrev30' => Order::query()->whereBetween('created_at', [$prevPeriodStart, $periodStart])->count(),
            'clientesCount' => Cliente::query()->count(),
            'productosCount' => Producto::query()->count(),
            'marcasCount' => Brand::query()->active()->count(),
            'reclamosPendientes' => LibroReclamacion::query()->whereNull('responded_at')->count(),
            'productosPrecioCero' => Producto::query()->where('price', 0)->count(),
            'productosSinMarca' => Producto::query()->where(fn ($query) => $query->whereNull('marca')->orWhere('marca', ''))->count(),
            'mensajesPendientes' => ContactMessage::query()->whereNull('read_at')->count(),
            'recentOrders' => Order::query()->with('statusOrder')->latest('id')->limit(6)->get(),
        ]);
    })->name('dashboard');
    Route::get('/pedidos', [OrderController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/exportar-csv', [OrderController::class, 'exportCsv'])->name('pedidos.export');
    Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('pedidos.show');
    Route::patch('/pedidos/{order}/estado', [OrderController::class, 'updateStatus'])->name('pedidos.status');
    Route::post('/pedidos/{order}/tareas/{task}/reintentar', [OrderController::class, 'retryTask'])->name('pedidos.tasks.retry');
    Route::get('/pedidos/{order}/correo', [OrderController::class, 'emailPreview'])->name('pedidos.email.preview');
    Route::post('/pedidos/{order}/correo/reenviar', [OrderController::class, 'resendEmail'])->name('pedidos.email.resend');
    Route::view('/clientes', 'admin.clientes.index')->name('clientes.index');
    Route::get('/reclamos', [LibroReclamacionController::class, 'index'])->name('reclamos.index');
    Route::get('/reclamos/exportar-csv', [LibroReclamacionController::class, 'exportCsv'])->name('reclamos.export');
    Route::get('/reclamos/{reclamo}', [LibroReclamacionController::class, 'show'])->name('reclamos.show');
    Route::patch('/reclamos/{reclamo}/estado', [LibroReclamacionController::class, 'updateStatus'])->name('reclamos.status');
    Route::get('/mensajes', [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/mensajes/{message}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'show'])->name('messages.show');
    Route::patch('/mensajes/{message}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'toggle'])->name('messages.toggle');
    Route::delete('/mensajes/{message}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::post('/usuarios/{user}/reenviar', [UserController::class, 'resend'])->name('usuarios.resend');
    Route::patch('/usuarios/{user}/acceso', [UserController::class, 'updateAccess'])->name('usuarios.access');
    Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggle'])->name('usuarios.toggle');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('/bodegas', [BodegaController::class, 'index'])->name('bodegas.index');
    Route::post('/bodegas', [BodegaController::class, 'store'])->name('bodegas.store');
    Route::get('/bodegas/asignar', [BodegaController::class, 'assign'])->name('bodegas.assign');
    Route::post('/bodegas/asignar', [BodegaController::class, 'assignStore'])->name('bodegas.assign.store');
    Route::patch('/bodegas/{bodega}', [BodegaController::class, 'update'])->name('bodegas.update');
    Route::patch('/bodegas/{bodega}/estado', [BodegaController::class, 'toggle'])->name('bodegas.toggle');
    Route::delete('/bodegas/{bodega}', [BodegaController::class, 'destroy'])->name('bodegas.destroy');
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/productos/exportar-csv', [ProductoController::class, 'exportCsv'])->name('productos.export');
    Route::get('/productos/carga-masiva-imagenes', [ProductoController::class, 'bulkImages'])->name('productos.images.bulk');
    Route::post('/productos/carga-masiva-imagenes/revisar', [ProductoController::class, 'previewBulkImages'])->name('productos.images.preview');
    Route::post('/productos/carga-masiva-imagenes/aplicar', [ProductoController::class, 'applyBulkImages'])->name('productos.images.apply');
    Route::post('/productos/carga-masiva-imagenes/cancelar', [ProductoController::class, 'cancelBulkImages'])->name('productos.images.cancel');
    Route::get('/productos/{producto}', [ProductoController::class, 'show'])->name('productos.show');
    Route::patch('/productos/{producto}/marca', [ProductoController::class, 'updateBrand'])->name('productos.brand');
    Route::patch('/productos/{producto}/detalle', [ProductoController::class, 'updateDetails'])->name('productos.details');
    Route::post('/productos/{producto}/imagen', [ProductoController::class, 'updateProductImage'])->name('productos.image');
    Route::post('/productos/{producto}/guia-tallas', [ProductoController::class, 'updateSizeGuideImage'])->name('productos.size-guide');
    Route::post('/productos/{producto}/imagenes-color', [ProductoController::class, 'updateColorImages'])->name('productos.color-images');
    Route::get('/medios-pago', [MetodoPagoController::class, 'index'])->name('medios-pago.index');
    Route::post('/medios-pago', [MetodoPagoController::class, 'store'])->name('medios-pago.store');
    Route::patch('/medios-pago/{metodoPago}', [MetodoPagoController::class, 'update'])->name('medios-pago.update');
    Route::patch('/medios-pago/{metodoPago}/estado', [MetodoPagoController::class, 'toggle'])->name('medios-pago.toggle');
    Route::delete('/medios-pago/{metodoPago}', [MetodoPagoController::class, 'destroy'])->name('medios-pago.destroy');
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::post('/banners/reordenar', [BannerController::class, 'reorder'])->name('banners.reorder');
    Route::patch('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::patch('/banners/{banner}/estado', [BannerController::class, 'toggle'])->name('banners.toggle');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    Route::get('/combos', [ComboController::class, 'index'])->name('combos.index');
    Route::get('/sugerencias', [CartSuggestionController::class, 'index'])->name('sugerencias.index');
    Route::post('/sugerencias', [CartSuggestionController::class, 'store'])->name('sugerencias.store');
    Route::get('/sugerencias/{suggestion}/editar', [CartSuggestionController::class, 'edit'])->name('sugerencias.edit');
    Route::put('/sugerencias/{suggestion}', [CartSuggestionController::class, 'update'])->name('sugerencias.update');
    Route::patch('/sugerencias/{suggestion}/estado', [CartSuggestionController::class, 'toggle'])->name('sugerencias.toggle');
    Route::delete('/sugerencias/{suggestion}', [CartSuggestionController::class, 'destroy'])->name('sugerencias.destroy');
    Route::get('/arma-tu-outfit', [ComboController::class, 'outfits'])->name('outfits.index');
    Route::post('/arma-tu-outfit/flyer', [ComboController::class, 'updateFlyer'])->name('outfits.flyer');
    // Overshark TV: maqueta del estudio (sin backend todavía).
    Route::view('/tv', 'admin.tv.index')->name('tv.index');
    Route::post('/combos/importar-catalogo', [ComboController::class, 'importCatalog'])->name('combos.import');
    Route::post('/combos', [ComboController::class, 'store'])->name('combos.store');
    Route::get('/combos/{combo}/editar', [ComboController::class, 'edit'])->name('combos.edit');
    Route::put('/combos/{combo}', [ComboController::class, 'update'])->name('combos.update');
    Route::patch('/combos/{combo}/estado', [ComboController::class, 'toggle'])->name('combos.toggle');
    Route::patch('/combos/{combo}/publicar', [ComboController::class, 'publish'])->name('combos.publish');
    Route::delete('/combos/{combo}', [ComboController::class, 'destroy'])->name('combos.destroy');
    Route::get('/marcas', [BrandController::class, 'index'])->name('brands.index');
    Route::post('/marcas', [BrandController::class, 'store'])->name('brands.store');
    Route::patch('/marcas/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::patch('/marcas/{brand}/estado', [BrandController::class, 'toggle'])->name('brands.toggle');
    Route::get('/identidad-visual', [BrandSettingController::class, 'edit'])->name('brand.edit');
    Route::get('/pagina-nosotros', [\App\Http\Controllers\Admin\AboutPageController::class, 'edit'])->name('about.edit');
    Route::put('/pagina-nosotros', [\App\Http\Controllers\Admin\AboutPageController::class, 'update'])->name('about.update');
    Route::post('/pagina-nosotros/restaurar', [\App\Http\Controllers\Admin\AboutPageController::class, 'reset'])->name('about.reset');
    Route::put('/identidad-visual', [BrandSettingController::class, 'update'])->name('brand.update');
    Route::put('/identidad-visual/whatsapp', [BrandSettingController::class, 'updateWhatsapp'])->name('brand.whatsapp');

    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('token.refresh');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
