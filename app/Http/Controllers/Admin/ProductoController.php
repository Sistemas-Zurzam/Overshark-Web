<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $empresa = trim((string) $request->query('empresa', ''));
        $marca = trim((string) $request->query('marca', ''));

        $productosQuery = Producto::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('default_code', 'like', "%{$search}%");
                });
            })
            ->when($empresa !== '', fn ($query) => $query->where('empresa_nombre', $empresa))
            ->when($marca !== '', fn ($query) => $query->where('marca', $marca));

        return view('admin.productos.index', [
            'productos' => $productosQuery
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, COUNT(*) as variant_count, SUM(stock) as total_stock, MIN(price) as min_price, MAX(price) as max_price, MAX(updated_at) as updated_at, MAX(imagen) as imagen')
                ->groupBy('zazu_company_id', 'name')
                ->orderByDesc('updated_at')
                ->paginate(25)
                ->withQueryString(),
            'empresas' => Producto::query()
                ->whereNotNull('empresa_nombre')
                ->where('empresa_nombre', '<>', '')
                ->distinct()
                ->orderBy('empresa_nombre')
                ->pluck('empresa_nombre'),
            'marcas' => Brand::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->merge(Producto::query()
                ->whereNotNull('marca')
                ->where('marca', '<>', '')
                ->distinct()
                ->orderBy('marca')
                ->pluck('marca'))
                ->unique()
                ->values(),
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
            ->get()
            ->keyBy('color');

        return view('admin.productos.show', [
            'producto' => $producto,
            'variantsBySize' => $variants->groupBy(fn (Producto $variant) => $variant->talla ?: 'Sin talla'),
            'colors' => $variants->pluck('color')->filter()->unique()->values(),
            'colorImages' => $colorImages,
            'brands' => Brand::query()->orderBy('sort_order')->orderBy('name')->pluck('name'),
        ]);
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

    public function updateBrand(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'marca' => ['nullable', 'string', 'max:120'],
        ]);

        $marca = trim((string) ($validated['marca'] ?? '')) ?: null;
        $query = $this->variantsFor($producto);
        $query->update(['marca' => $marca]);
        Producto::refreshCacheVersion();

        return back()->with('status', $marca
            ? "Marca {$marca} asignada al producto."
            : 'Marca retirada del producto.');
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
            'product_name' => $producto->name,
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
            ->where('name', $producto->name)
            ->when(
                $producto->zazu_company_id !== null,
                fn ($query) => $query->where('zazu_company_id', $producto->zazu_company_id),
            );
    }
}
