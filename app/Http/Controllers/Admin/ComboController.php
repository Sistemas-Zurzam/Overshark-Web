<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Services\ComboCatalogImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(): View
    {
        return view('admin.combos.index', [
            'combos' => Combo::query()->latest()->get(),
            'productOptions' => Producto::query()
                ->selectRaw('MIN(id) as id, name, MAX(empresa_nombre) as empresa_nombre')
                ->whereNotNull('name')
                ->groupBy('name')
                ->orderBy('name')
                ->get(),
            'brands' => Brand::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name'),
        ]);
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:80'],
            'modality' => ['nullable', 'string', 'max:80'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'url' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/i'],
            'status' => ['nullable', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*.producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'products.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [
            'url.regex' => 'La URL debe comenzar con http://, https:// o /.',
        ]);

        DB::transaction(function () use ($request, $validated): Combo {
            $combo = Combo::query()->create([
                'name' => $validated['name'],
                'brand' => $validated['brand'] ?? null,
                'modality' => $validated['modality'] ?? null,
                'price' => $validated['price'] ?? null,
                'imagen' => $request->file('image')?->store('combos', 'public') ?: 'images/default-hero-banner.png',
                'items' => [],
                'selection_mode' => 'fixed',
                'status' => $request->boolean('status'),
                'url' => null,
            ]);

            $pivot = [];
            $items = [];

            foreach ($validated['products'] ?? [] as $productRow) {
                if (blank($productRow['producto_id'] ?? null)) {
                    continue;
                }

                $product = Producto::query()->findOrFail($productRow['producto_id']);
                $quantity = (int) ($productRow['cantidad'] ?? 1);
                $pivot[$product->id] = ['cantidad' => $quantity];
                $items[] = ['zazu' => $product->name, 'cantidad' => $quantity];
            }

            $combo->update([
                'items' => $items,
                'url' => $validated['url'] ?? '/combos/'.$combo->id,
            ]);
            $combo->productos()->sync($pivot);

            return $combo;
        });

        return back()->with('status', 'Combo guardado correctamente.');
    }

    public function toggle(Combo $combo): RedirectResponse
    {
        $combo->update(['status' => ! $combo->status]);

        return back()->with('status', 'Estado del combo actualizado.');
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
