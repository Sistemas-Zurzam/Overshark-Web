<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Models\Brand;
use App\Services\ComboCatalogImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $brandId = $request->query('marca');
        $estado = in_array($request->query('estado'), ['activos', 'borradores'], true) ? $request->query('estado') : '';

        $base = Combo::query()
            ->where(fn ($q) => $q->whereNull('modality')->orWhere('modality', '!=', 'Outfit'))
            ->when($search !== '', function ($query) use ($search): void {
                $like = "%{$search}%";
                $query->where(fn ($searchQuery) => $searchQuery
                    ->where('name', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('modality', 'like', $like)
                    ->orWhere('items', 'like', $like));
            })
            ->when($brandId !== null && $brandId !== '', fn ($query) => $query->where('brand_id', $brandId))
            ->latest()
            ->get();

        $counts = [
            'todos' => $base->count(),
            'activos' => $base->where('is_draft', false)->where('status', true)->count(),
            'borradores' => $base->where('is_draft', true)->count(),
        ];

        $combos = match ($estado) {
            'activos' => $base->where('is_draft', false)->where('status', true)->values(),
            'borradores' => $base->where('is_draft', true)->values(),
            default => $base,
        };

        return view('admin.combos.index', [
            'combos' => $combos,
            'search' => $search,
            'estado' => $estado,
            'selectedBrandId' => $brandId,
            'counts' => $counts,
            ...$this->formOptions(),
        ]);
    }

    /** Arma tu outfit: sección propia, separada de los combos del menú. */
    public function outfits(): View
    {
        return view('admin.combos.outfits', [
            'combos' => Combo::query()->where('modality', 'Outfit')->orderBy('price')->get(),
            ...$this->formOptions(),
        ]);
    }

    public function updateFlyer(Request $request): RedirectResponse
    {
        if ($request->boolean('reset')) {
            \App\Support\OutfitFlyer::delete();

            return back()->with('status', 'Se restauró el flyer original.');
        }

        $request->validate(['flyer' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192']], [
            'flyer.required' => 'Elige la imagen del flyer.',
            'flyer.image' => 'El flyer debe ser una imagen PNG, JPG o WEBP.',
            'flyer.max' => 'El flyer no puede superar los 8 MB.',
        ]);
        \App\Support\OutfitFlyer::store($request->file('flyer'));

        return back()->with('status', 'Flyer actualizado. Ya se ve en «Arma tu outfit».');
    }

    public function importCatalog(ComboCatalogImportService $importer): RedirectResponse
    {
        $result = $importer->import();
        $status = "Catalogo cargado: {$result['created']} creados y {$result['updated']} actualizados.";

        if ($result['missing'] !== []) {
            $status .= ' Los productos se vincularan cuando exista una sincronizacion de ZAZU.';
        }

        return back()
            ->with('status', $status)
            ->with('import_missing', $result['missing']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new Combo(['selection_mode' => 'fixed', 'items' => []]));

        return back()->with('status', $request->boolean('outfit') ? 'Combo de outfit guardado.' : 'Combo guardado correctamente.');
    }

    public function edit(Combo $combo): View
    {
        $related = $combo->productos()->get()->keyBy(fn (Producto $p) => mb_strtoupper(trim($p->name)));
        // La lista de productos usa un id por nombre + empresa (el menor): se traduce el producto vinculado a ese id.
        $optionId = function (string $name) use ($related): ?int {
            $product = $related->get(mb_strtoupper(trim($name)));

            return Producto::query()->where('name', $name)
                ->when($product, fn ($q) => $q->where('zazu_company_id', $product->zazu_company_id))
                ->min('id');
        };

        return view('admin.combos.edit', [
            'combo' => $combo,
            'productRows' => collect($combo->items ?? [])->map(fn (array $item) => [
                'producto_id' => $optionId((string) ($item['zazu'] ?? '')),
                'grupo' => $item['grupo'] ?? '',
                'cantidad' => $item['cantidad'] ?? 1,
            ])->values()->all(),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Combo $combo): RedirectResponse
    {
        $this->save($request, $combo);

        return redirect()->route($combo->modality === 'Outfit' ? 'admin.outfits.index' : 'admin.combos.index')
            ->with('status', "Combo «{$combo->name}» actualizado.");
    }

    /**
     * Crea o actualiza un combo. Cada fila de producto puede llevar una "prenda" (grupo): las filas con la
     * misma prenda son alternativas y el cliente elige una (ver ComboSelectionService::slots).
     */
    private function save(Request $request, Combo $combo): void
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_id' => ['required', 'exists:brands,id'],
            'modality' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:255'],
            'outfit' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:price'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'url' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/i'],
            'status' => ['nullable', 'boolean'],
            'is_draft' => ['nullable', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*.producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'products.*.grupo' => ['nullable', 'string', 'max:40'],
            'products.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [
            'url.regex' => 'La URL debe comenzar con http://, https:// o /.',
            'compare_at_price.gte' => 'El precio regular debe ser mayor o igual al precio del combo.',
        ]);

        // "Mostrar en Arma tu outfit" equivale a la modalidad "Outfit", que es la que lee esa página.
        $modality = $validated['modality'] ?? null;
        if ($request->boolean('outfit')) {
            $modality = 'Outfit';
        } elseif ($modality === 'Outfit') {
            $modality = 'Promoción';
        }

        $isDraft = $request->boolean('is_draft');
        $brand = Brand::query()->findOrFail($validated['brand_id']);

        DB::transaction(function () use ($request, $validated, $combo, $modality, $isDraft, $brand): void {
            $oldImage = $combo->imagen;
            $newImage = $request->file('image')?->store('combos', 'public');

            $combo->fill([
                'name' => $validated['name'],
                'brand' => $brand->name,
                'brand_id' => $brand->id,
                'modality' => $modality,
                'notes' => $validated['notes'] ?? null,
                'price' => $validated['price'] ?? null,
                'compare_at_price' => $validated['compare_at_price'] ?? null,
                'imagen' => $newImage ?: ($oldImage ?: 'images/default-hero-banner.png'),
                // Un borrador nunca se muestra en la tienda, sin importar lo que diga el checkbox de estado.
                'is_draft' => $isDraft,
                'status' => $isDraft ? false : $request->boolean('status'),
            ])->save();

            if ($newImage && $oldImage && ! str_starts_with($oldImage, 'images/') && ! filter_var($oldImage, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($oldImage);
            }

            $pivot = [];
            $items = [];

            foreach ($validated['products'] ?? [] as $productRow) {
                if (blank($productRow['producto_id'] ?? null)) {
                    continue;
                }

                $product = Producto::query()->findOrFail($productRow['producto_id']);
                $quantity = (int) ($productRow['cantidad'] ?? 1);
                $group = trim((string) ($productRow['grupo'] ?? ''));
                $pivot[$product->id] = ['cantidad' => $quantity];
                $items[] = array_filter(['zazu' => $product->name, 'cantidad' => $quantity, 'grupo' => $group], fn ($v) => $v !== '');
            }

            $combo->update([
                'items' => $items,
                'url' => ($validated['url'] ?? null) ?: '/combos/'.$combo->id,
            ]);
            $combo->productos()->sync($pivot);
        });
    }

    private function formOptions(): array
    {
        return [
            // Un producto por nombre y empresa: la empresa elegida define de qué inventario salen las variantes.
            'productOptions' => Producto::query()
                ->selectRaw('MIN(id) as id, name, MAX(empresa_nombre) as empresa_nombre, SUM(CASE WHEN price > 0 AND stock > 0 THEN 1 ELSE 0 END) as visibles')
                ->whereNotNull('name')
                ->groupBy('name', 'zazu_company_id')
                ->orderBy('name')
                ->get(),
            'brands' => Brand::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'modalities' => ['Live', 'Publicidad', 'Promoción', 'Posventa', 'Cross-Selling'],
        ];
    }

    public function toggle(Combo $combo): RedirectResponse
    {
        $combo->update(['status' => ! $combo->status]);

        return back()->with('status', 'Estado del combo actualizado.');
    }

    public function publish(Combo $combo): RedirectResponse
    {
        $combo->update(['is_draft' => false, 'status' => true]);

        return back()->with('status', "Combo «{$combo->name}» publicado.");
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        if ($combo->imagen
            && ! str_starts_with($combo->imagen, 'images/')
            && ! filter_var($combo->imagen, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($combo->imagen);
        }

        $combo->delete();

        return back()->with('status', 'Combo eliminado correctamente.');
    }
}
