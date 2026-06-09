<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Services\OdooProductSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ProductoController extends Controller
{
    public function index(): View
    {
        return view('admin.productos.index', [
            'productos' => Producto::query()
                ->selectRaw('MIN(id) as id, odoo_template_id, name, COUNT(*) as variant_count, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(price) as max_price, MAX(odoo_synced_at) as odoo_synced_at, MAX(imagen) as imagen')
                ->groupBy('odoo_template_id', 'name')
                ->latest('odoo_synced_at')
                ->paginate(25),
            'autoSyncEnabled' => Cache::get('odoo.products.sync_enabled', false),
        ]);
    }

    public function show(Producto $producto): View
    {
        $variants = $this->variantsFor($producto)
            ->orderBy('talla')
            ->orderBy('color')
            ->get();

        $colorImages = ProductoColorImage::query()
            ->where('product_name', $producto->name)
            ->when($producto->odoo_template_id, fn ($query) => $query->where('odoo_template_id', $producto->odoo_template_id))
            ->get()
            ->keyBy('color');

        return view('admin.productos.show', [
            'producto' => $producto,
            'variantsBySize' => $variants->groupBy(fn (Producto $variant) => $variant->talla ?: 'Sin talla'),
            'colors' => $variants->pluck('color')->filter()->unique()->values(),
            'colorImages' => $colorImages,
        ]);
    }

    public function sync(OdooProductSyncService $syncService): RedirectResponse
    {
        try {
            $result = $syncService->syncProducts();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo sincronizar con Odoo: '.$exception->getMessage());
        }

        return back()->with(
            'status',
            "Sincronizacion Odoo completa: {$result['total']} productos, {$result['created']} nuevos, {$result['updated']} actualizados.",
        );
    }

    public function toggleAutoSync(): RedirectResponse
    {
        $enabled = ! Cache::get('odoo.products.sync_enabled', false);
        Cache::forever('odoo.products.sync_enabled', $enabled);

        return back()->with('status', $enabled
            ? 'Sincronizacion automatica activada. Ejecuta php artisan schedule:work para procesarla cada minuto.'
            : 'Sincronizacion automatica desactivada.');
    }

    public function updateProductImage(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        $variants = $this->variantsFor($producto)->get();
        $oldImages = $variants->pluck('imagen')->filter()->unique();
        $path = $validated['image']->store('productos', 'public');

        $this->variantsFor($producto)->update(['imagen' => $path]);
        Producto::refreshCacheVersion();
        $oldImages->each(fn (string $oldPath) => Storage::disk('public')->delete($oldPath));

        return back()->with('status', 'Imagen principal del producto actualizada.');
    }

    public function updateDetails(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'composicion' => ['nullable', 'string', 'max:5000'],
            'cuidados' => ['nullable', 'string', 'max:5000'],
            'material' => ['nullable', 'string', 'max:255'],
            'fit' => ['nullable', 'string', 'max:255'],
            'sensacion' => ['nullable', 'string', 'max:255'],
        ]);

        $this->variantsFor($producto)->update($validated);
        Producto::refreshCacheVersion();

        return back()->with('status', 'Informacion del producto actualizada.');
    }

    public function updateSizeGuideImage(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'guia_tallas_imagen' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        $variants = $this->variantsFor($producto)->get();
        $oldImages = $variants->pluck('guia_tallas_imagen')->filter()->unique();
        $path = $validated['guia_tallas_imagen']->store('productos/guias-tallas', 'public');

        $this->variantsFor($producto)->update(['guia_tallas_imagen' => $path]);
        Producto::refreshCacheVersion();
        $oldImages->each(fn (string $oldPath) => Storage::disk('public')->delete($oldPath));

        return back()->with('status', 'Guia de tallas actualizada.');
    }

    public function updateColorImages(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'color' => ['required', 'string', 'max:255'],
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        $record = ProductoColorImage::query()->firstOrNew([
            'odoo_template_id' => $producto->odoo_template_id,
            'color' => $validated['color'],
        ]);

        foreach ($record->images ?? [] as $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $record->fill([
            'product_name' => $producto->name,
            'images' => collect($validated['images'])
                ->map(fn ($image) => $image->store('productos/colores', 'public'))
                ->all(),
        ])->save();

        return back()->with('status', 'Imagenes del color actualizadas.');
    }

    private function variantsFor(Producto $producto)
    {
        return Producto::query()
            ->when(
                $producto->odoo_template_id,
                fn ($query) => $query->where('odoo_template_id', $producto->odoo_template_id),
                fn ($query) => $query->where('name', $producto->name),
            );
    }
}
