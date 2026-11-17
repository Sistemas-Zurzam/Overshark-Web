<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * GET /api/v1/products: catálogo que la tienda vende (empresas de la tienda, no ocultos, con precio). Un producto =
 * variantes (color y talla) con el mismo nombre y empresa; cada variante trae su stock actual.
 *
 * Filtros: q (nombre), updated_since (fecha ISO 8601: variantes cambiadas desde entonces, p. ej. stock), in_stock=1,
 * page y per_page (máx. 100).
 */
class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'updated_since' => ['nullable', 'date'],
            'in_stock' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $base = fn (): Builder => $this->catalog()
            ->when(filled($filters['q'] ?? null), fn (Builder $query) => $query->where('productos.name', 'like', '%'.$filters['q'].'%'))
            ->when(filled($filters['updated_since'] ?? null), fn (Builder $query) => $query->where('productos.updated_at', '>=', Carbon::parse($filters['updated_since'])))
            ->when((bool) ($filters['in_stock'] ?? false), fn (Builder $query) => $query->where('productos.stock', '>', 0));

        $groups = $base()
            ->selectRaw('MIN(productos.id) as id, productos.zazu_company_id, productos.name')
            ->groupBy('productos.zazu_company_id', 'productos.name')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 50));

        $variants = $groups->isEmpty() ? collect() : $base()
            ->where(function (Builder $query) use ($groups): void {
                foreach ($groups as $group) {
                    $query->orWhere(fn (Builder $inner) => $inner->where('productos.name', $group->name)
                        ->where(fn (Builder $company) => $group->zazu_company_id === null
                            ? $company->whereNull('productos.zazu_company_id')
                            : $company->where('productos.zazu_company_id', $group->zazu_company_id)));
                }
            })
            ->orderBy('productos.id')
            ->get();

        return response()->json([
            'data' => $this->present($groups->getCollection(), $variants),
            'meta' => ['page' => $groups->currentPage(), 'per_page' => $groups->perPage(), 'total' => $groups->total(), 'last_page' => $groups->lastPage()],
        ]);
    }

    /** GET /api/v1/products/{id}: el producto de esa variante, con todas sus variantes. */
    public function show(int $id): JsonResponse
    {
        $variant = $this->catalog()->where('productos.id', $id)->first();
        abort_unless($variant, 404, 'Producto no encontrado.');

        $variants = $this->catalog()->where('productos.name', $variant->name)
            ->where(fn (Builder $company) => $variant->zazu_company_id === null
                ? $company->whereNull('productos.zazu_company_id')
                : $company->where('productos.zazu_company_id', $variant->zazu_company_id))
            ->orderBy('productos.id')
            ->get();
        $group = (object) ['id' => $variants->min('id'), 'name' => $variant->name, 'zazu_company_id' => $variant->zazu_company_id];

        return response()->json(['data' => $this->present(collect([$group]), $variants)[0]]);
    }

    private function catalog(): Builder
    {
        return Producto::query()->inStore()->where('productos.price', '>', 0);
    }

    private function present(Collection $groups, Collection $variants): array
    {
        $galleries = ProductoColorImage::query()->whereIn('product_name', $groups->pluck('name')->unique())->get()
            ->groupBy('product_name');

        return $groups->map(function ($group) use ($variants, $galleries) {
            $own = $variants->filter(fn (Producto $variant) => $variant->name === $group->name
                && (string) $variant->zazu_company_id === (string) $group->zazu_company_id)->values();
            $images = $galleries->get($group->name, collect())->keyBy(fn (ProductoColorImage $gallery) => mb_strtoupper(trim((string) $gallery->color)));
            $imageFor = function (Producto $variant) use ($images): ?string {
                $gallery = $images->get(mb_strtoupper(trim((string) $variant->color)));
                $path = $gallery?->imageUrls()[0] ?? $variant->imagen;

                return $path ? (str_starts_with($path, 'http') ? $path : url($path)) : null;
            };
            $first = $own->first();

            return [
                'id' => (int) $group->id,
                'name' => $group->name,
                'brand' => $first?->marca,
                'company' => $first?->empresa_nombre,
                'url' => route('web.products.show', $group->id),
                'image' => $first ? $imageFor($first) : null,
                'price_from' => round((float) $own->min('price'), 2),
                'currency' => 'PEN',
                'stock' => (int) $own->sum('stock'),
                'variants' => $own->map(fn (Producto $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->default_code,
                    'color' => $variant->color,
                    'size' => $variant->talla,
                    'price' => round((float) $variant->price, 2),
                    'stock' => (int) $variant->stock,
                    'image' => $imageFor($variant),
                    'updated_at' => $variant->updated_at?->toIso8601String(),
                ])->all(),
            ];
        })->values()->all();
    }
}
