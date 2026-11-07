<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductoController extends Controller
{
    private const BULK_IMAGES_SESSION_KEY = 'admin.bulk_product_images.v2';

    /** Mensajes legibles para las subidas de imágenes (sin archivos de idioma se verían las claves). */
    private const UPLOAD_MESSAGES = [
        'uploaded' => 'Una imagen no se pudo subir: pesa más de lo que permite el servidor. Prueba con fotos más livianas o menos fotos a la vez.',
        'images.*.uploaded' => 'Una imagen no se pudo subir: pesa más de lo que permite el servidor. Prueba con fotos más livianas o menos fotos a la vez.',
        'image' => 'El archivo debe ser una imagen.',
        'images.*.image' => 'Cada archivo debe ser una imagen.',
        'mimes' => 'Usa imágenes PNG, JPG o WEBP.',
        'images.*.mimes' => 'Usa imágenes PNG, JPG o WEBP.',
        'max' => 'Cada imagen puede pesar como máximo 8 MB.',
        'images.*.max' => 'Cada imagen puede pesar como máximo 8 MB.',
        'images.max' => 'Sube como máximo 5 imágenes por color.',
    ];

    private const ESTADOS = ['publicado', 'sin_marca', 'precio_cero', 'sin_stock'];

    private const ESTADO_LABELS = [
        'publicado' => 'Publicado',
        'sin_marca' => 'Sin marca',
        'precio_cero' => 'Precio en 0',
        'sin_stock' => 'Sin stock',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filtersFrom($request);
        $groups = $this->groupedProducts($filters);

        $tab = in_array($request->query('tab'), ['review', 'out_of_stock'], true) ? $request->query('tab') : 'all';
        $estado = in_array($request->query('estado'), self::ESTADOS, true) ? $request->query('estado') : '';

        $counts = [
            'all' => $groups->count(),
            'review' => $groups->whereIn('estado', ['sin_marca', 'precio_cero'])->count(),
            'out_of_stock' => $groups->where('estado', 'sin_stock')->count(),
        ];

        $filtered = match ($tab) {
            'review' => $groups->whereIn('estado', ['sin_marca', 'precio_cero'])->values(),
            'out_of_stock' => $groups->where('estado', 'sin_stock')->values(),
            default => $groups,
        };
        if ($estado !== '') {
            $filtered = $filtered->where('estado', $estado)->values();
        }

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $productos = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
                'query' => $request->except('page'),
            ],
        );

        return view('admin.productos.index', [
            'productos' => $productos,
            'empresas' => $this->empresasDisponibles(),
            'marcas' => $this->marcasDisponibles(),
            'filters' => $filters,
            'tab' => $tab,
            'estado' => $estado,
            'counts' => $counts,
            'estadoLabels' => self::ESTADO_LABELS,
            'lastSync' => Producto::query()->max('zazu_synced_at'),
        ]);
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $this->filtersFrom($request);
        $groups = $this->groupedProducts($filters);

        $tab = in_array($request->query('tab'), ['review', 'out_of_stock'], true) ? $request->query('tab') : 'all';
        $estado = in_array($request->query('estado'), self::ESTADOS, true) ? $request->query('estado') : '';

        $rows = match ($tab) {
            'review' => $groups->whereIn('estado', ['sin_marca', 'precio_cero']),
            'out_of_stock' => $groups->where('estado', 'sin_stock'),
            default => $groups,
        };
        if ($estado !== '') {
            $rows = $rows->where('estado', $estado);
        }

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Producto', 'Variantes', 'Marca', 'Empresa', 'Precio min', 'Precio max', 'Stock', 'Estado', 'Actualizado']);
            foreach ($rows as $group) {
                fputcsv($handle, [
                    $group->name,
                    $group->variant_count,
                    $group->marca ?: 'Sin asignar',
                    $group->empresa_nombre ?: 'Overshark',
                    number_format((float) $group->min_price, 2),
                    number_format((float) $group->max_price, 2),
                    (int) $group->total_stock,
                    self::ESTADO_LABELS[$group->estado],
                    optional($group->updated_at)->format('d/m/Y H:i'),
                ]);
            }
            fclose($handle);
        }, 'productos-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtersFrom(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'empresa' => trim((string) $request->query('empresa', '')),
            'marca' => trim((string) $request->query('marca', '')),
        ];
    }

    /** Agrupa variantes en productos (mismo nombre + empresa Zazu) y calcula su estado para tabs/filtro/export. */
    private function groupedProducts(array $filters): \Illuminate\Support\Collection
    {
        $productosQuery = Producto::query()
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($searchQuery) use ($filters): void {
                    $searchQuery
                        ->where('name', 'like', "%{$filters['search']}%")
                        ->orWhere('default_code', 'like', "%{$filters['search']}%");
                });
            })
            ->when($filters['empresa'] !== '', fn ($query) => $query->where('empresa_nombre', $filters['empresa']))
            ->when($filters['marca'] !== '', fn ($query) => $query->where('marca', $filters['marca']));

        return $productosQuery
            ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, COUNT(*) as variant_count, SUM(stock) as total_stock, MIN(price) as min_price, MAX(price) as max_price, MAX(updated_at) as updated_at, MAX(imagen) as imagen')
            ->groupBy('zazu_company_id', 'name')
            ->orderByDesc('updated_at')
            ->get()
            ->each(function ($group): void {
                $group->estado = match (true) {
                    (float) $group->total_stock <= 0.0 => 'sin_stock',
                    (float) $group->min_price <= 0.0 => 'precio_cero',
                    trim((string) $group->marca) === '' => 'sin_marca',
                    default => 'publicado',
                };
            });
    }

    private function empresasDisponibles()
    {
        return Producto::query()
            ->whereNotNull('empresa_nombre')
            ->where('empresa_nombre', '<>', '')
            ->distinct()
            ->orderBy('empresa_nombre')
            ->pluck('empresa_nombre');
    }

    private function marcasDisponibles()
    {
        return Brand::query()
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
            ->values();
    }

    public function bulkImages(Request $request): View
    {
        $normalize = fn ($value) => mb_strtolower(trim((string) $value));
        $galleryImages = ProductoColorImage::query()->get(['product_name', 'color', 'images'])
            ->mapWithKeys(fn (ProductoColorImage $gallery) => [
                $normalize($gallery->product_name).'|'.$normalize($gallery->color) => collect($gallery->images ?? [])->filter()->count(),
            ]);

        $allCoverageProducts = Producto::query()
            ->whereNotNull('color')
            ->where('color', '<>', '')
            ->get(['name', 'color', 'imagen'])
            ->groupBy('name')
            ->map(function ($variants, string $name) use ($galleryImages, $normalize): array {
                $colors = $variants->groupBy('color')->map(function ($colorVariants, string $color) use ($name, $galleryImages, $normalize): array {
                    $galleryCount = (int) $galleryImages->get($normalize($name).'|'.$normalize($color), 0);
                    $variantCount = $colorVariants->count();
                    $mainImageCount = $colorVariants->filter(fn (Producto $variant) => filled($variant->imagen))->count();
                    $complete = $galleryCount > 0 || $mainImageCount === $variantCount;

                    return [
                        'name' => $color,
                        'gallery_count' => $galleryCount,
                        'variant_count' => $variantCount,
                        'main_image_count' => $mainImageCount,
                        'complete' => $complete,
                        'source' => $galleryCount > 0 ? 'gallery' : ($mainImageCount === 0 ? 'missing' : ($complete ? 'main' : 'partial')),
                    ];
                })->values();
                $missingCount = $colors->where('complete', false)->count();

                return [
                    'name' => $name,
                    'colors' => $colors,
                    'color_count' => $colors->count(),
                    'covered_count' => $colors->where('complete', true)->count(),
                    'missing_count' => $missingCount,
                    'complete' => $missingCount === 0,
                ];
            })
            ->sortBy('name')
            ->values();

        $search = trim((string) $request->query('coverage_search', ''));
        $status = (string) $request->query('coverage_status', 'all');
        if (! in_array($status, ['all', 'missing', 'complete'], true)) {
            $status = 'all';
        }
        $filteredCoverage = $allCoverageProducts
            ->when($search !== '', fn ($items) => $items->filter(fn ($item) => Str::contains(Str::lower($item['name']), Str::lower($search))))
            ->when($status === 'missing', fn ($items) => $items->where('complete', false))
            ->when($status === 'complete', fn ($items) => $items->where('complete', true))
            ->values();
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage('coverage_page');
        $coverageProducts = new LengthAwarePaginator(
            $filteredCoverage->forPage($page, $perPage)->values(),
            $filteredCoverage->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'coverage_page',
                'query' => $request->except('coverage_page'),
            ],
        );

        $allColors = $allCoverageProducts->flatMap(fn ($product) => $product['colors']);

        return view('admin.productos.bulk-images', [
            'preview' => session(self::BULK_IMAGES_SESSION_KEY),
            'coverageProducts' => $coverageProducts,
            'coverageSearch' => $search,
            'coverageStatus' => $status,
            'coverageSummary' => [
                'products' => $allCoverageProducts->count(),
                'colors' => $allColors->count(),
                'covered' => $allColors->where('complete', true)->count(),
                'missing' => $allColors->where('complete', false)->count(),
            ],
        ]);
    }

    public function previewBulkImages(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ], [
            'images.required' => 'Selecciona al menos una imagen.',
            'images.max' => 'Puedes revisar hasta 20 imágenes por lote.',
            'images.*.image' => 'Cada archivo debe ser una imagen PNG, JPG o WEBP.',
            'images.*.mimes' => 'Usa imágenes PNG, JPG o WEBP.',
            'images.*.max' => 'Cada imagen puede pesar como máximo 8 MB.',
        ] + self::UPLOAD_MESSAGES);

        $this->forgetBulkImagePreview();
        $token = Str::random(40);
        $files = collect($validated['images'])->values();
        $parsedFiles = $files->map(function ($file): array {
            $stem = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $parts = explode('__', $stem);
            $normalize = fn ($value) => Str::upper(preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value));

            return [
                'product_name' => $normalize($parts[0] ?? ''),
                'color' => count($parts) > 1 ? $normalize($parts[1]) : null,
                'order' => count($parts) > 2 && ctype_digit($parts[2]) ? (int) $parts[2] : null,
                'invalid_parts' => count($parts) > 3 || (count($parts) === 3 && ! ctype_digit($parts[2])),
            ];
        });
        $items = [];

        foreach ($files as $index => $file) {
            $parsed = $parsedFiles[$index];
            $productName = $parsed['product_name'];
            $color = $parsed['color'];
            $item = [
                'filename' => $file->getClientOriginalName(),
                'product_name' => $productName,
                'color' => $color,
                'order' => $parsed['order'],
                'has_order' => $parsed['order'] !== null,
                'matches' => 0,
                'products' => [],
                'temp_path' => null,
                'error' => null,
            ];

            if ($parsed['invalid_parts'] || $productName === '' || mb_strlen($productName) > 255 || ($color !== null && ($color === '' || mb_strlen($color) > 255))) {
                $item['error'] = 'Usa Nombre del producto__Color__número.jpg o Nombre del producto.jpg.';
                $items[] = $item;

                continue;
            }

            $productsQuery = Producto::query()->whereRaw('UPPER(TRIM(name)) = ?', [$productName]);
            if ($color !== null) {
                $productsQuery->whereRaw('UPPER(TRIM(color)) = ?', [$color]);
            }
            $products = $productsQuery->get(['name', 'color', 'empresa_nombre']);

            $item['products'] = $products
                ->map(fn (Producto $product) => trim($product->name.($color !== null ? ' · '.$product->color : '').' · '.($product->empresa_nombre ?: 'Sin empresa')))
                ->unique()
                ->values()
                ->all();
            $item['matches'] = count($item['products']);

            if ($products->isEmpty()) {
                $item['error'] = $color === null
                    ? 'No encontramos ese nombre exacto en el catálogo.'
                    : 'No encontramos ese producto con ese color en el catálogo.';
                $items[] = $item;

                continue;
            }

            $safeExtension = $file->guessExtension() ?: 'img';
            $tempPath = $file->storeAs("bulk-product-images/{$token}", $index.'.'.$safeExtension, 'local');
            if (! $tempPath) {
                $item['error'] = 'No se pudo guardar temporalmente este archivo. Inténtalo de nuevo.';
                $items[] = $item;

                continue;
            }
            $item['temp_path'] = $tempPath;
            $items[] = $item;
        }

        $mainGroups = collect($items)->filter(fn ($item) => $item['color'] === null && empty($item['error']))
            ->groupBy('product_name');
        foreach ($mainGroups as $group) {
            if ($group->count() > 1) {
                foreach ($group as $groupIndex => $groupItem) {
                    $items[$groupIndex]['error'] = 'Solo puede haber una imagen principal por producto en cada lote.';
                    if (! empty($items[$groupIndex]['temp_path'])) {
                        Storage::disk('local')->delete($items[$groupIndex]['temp_path']);
                        $items[$groupIndex]['temp_path'] = null;
                    }
                }
            }
        }

        $colorGroups = collect($items)->filter(fn ($item) => $item['color'] !== null && empty($item['error']))
            ->groupBy(fn ($item) => $item['product_name'].'|'.$item['color']);
        foreach ($colorGroups as $group) {
            $orders = $group->pluck('order');
            $groupError = $group->count() > 5
                ? 'Máximo 5 imágenes por producto y color.'
                : ($group->count() > 1 && $group->contains(fn ($item) => ! $item['has_order'])
                    ? 'Numera cada imagen del color al subir varias, por ejemplo __01, __02.'
                    : ($orders->count() !== $orders->unique()->count() ? 'Los números de imagen deben ser distintos.' : null));
            if ($groupError !== null) {
                foreach ($group as $groupIndex => $groupItem) {
                    $items[$groupIndex]['error'] = $groupError;
                    if (! empty($items[$groupIndex]['temp_path'])) {
                        Storage::disk('local')->delete($items[$groupIndex]['temp_path']);
                        $items[$groupIndex]['temp_path'] = null;
                    }
                }
            }
        }

        $manifest = ['token' => $token, 'items' => $items];
        session()->put(self::BULK_IMAGES_SESSION_KEY, $manifest);

        return redirect()->route('admin.productos.images.bulk')->with('preview_ready', true);
    }

    public function applyBulkImages(Request $request): RedirectResponse
    {
        $manifest = session(self::BULK_IMAGES_SESSION_KEY);
        abort_unless(is_array($manifest) && hash_equals((string) ($manifest['token'] ?? ''), (string) $request->input('token')), 419);

        $updated = 0;
        $errors = [];
        $oldPaths = collect();
        $validItems = collect($manifest['items'] ?? [])->filter(fn ($item) => empty($item['error']) && ! empty($item['temp_path']));

        $mainImages = $validItems->filter(fn ($item) => $item['color'] === null);
        foreach ($mainImages as $item) {
            $productName = (string) $item['product_name'];
            $imagePath = 'productos/'.Str::uuid().'.'.pathinfo($item['temp_path'], PATHINFO_EXTENSION);
            try {
                $contents = Storage::disk('local')->get($item['temp_path']);
                if (! Storage::disk('public')->put($imagePath, $contents)) {
                    throw new \RuntimeException('No se pudo escribir la imagen en el almacenamiento público.');
                }
                $variants = Producto::query()->whereRaw('UPPER(TRIM(name)) = ?', [$productName]);
                $oldPaths = $oldPaths->merge((clone $variants)->whereNotNull('imagen')->pluck('imagen'));
                if ($variants->update(['imagen' => $imagePath]) < 1) {
                    Storage::disk('public')->delete($imagePath);
                    $errors[] = "{$productName}: el producto ya no existe.";
                    continue;
                }
                $updated++;
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($imagePath);
                $errors[] = "{$productName}: no se pudo guardar la imagen principal.";
            }
        }

        $colorGroups = $validItems->filter(fn ($item) => $item['color'] !== null)
            ->groupBy(fn ($item) => $item['product_name'].'|'.$item['color']);
        foreach ($colorGroups as $images) {
            $first = $images->first();
            $productName = (string) $first['product_name'];
            $color = (string) $first['color'];
            $savedPaths = [];
            try {
                $images = $images->sortBy(fn ($item) => $item['order'] ?? 0)->values();
                foreach ($images as $item) {
                    $path = 'productos/colores/'.Str::uuid().'.'.pathinfo($item['temp_path'], PATHINFO_EXTENSION);
                    $contents = Storage::disk('local')->get($item['temp_path']);
                    if (! Storage::disk('public')->put($path, $contents)) {
                        throw new \RuntimeException('No se pudo escribir una imagen en el almacenamiento público.');
                    }
                    $savedPaths[] = $path;
                }

                $catalogProduct = Producto::query()
                    ->whereRaw('UPPER(TRIM(name)) = ?', [$productName])
                    ->whereRaw('UPPER(TRIM(color)) = ?', [$color])
                    ->first(['name', 'color']);
                if (! $catalogProduct) {
                    throw new \RuntimeException('El producto o color ya no existe en el catálogo.');
                }

                $record = ProductoColorImage::query()->firstOrNew([
                    'product_name' => $catalogProduct->name,
                    'color' => $catalogProduct->color,
                ]);
                $oldPaths = $oldPaths->merge($record->images ?? []);
                $record->images = $savedPaths;
                $record->save();
                $updated++;
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($savedPaths);
                $errors[] = "{$productName} · {$color}: no se pudo guardar la galería de color.";
            }
        }

        Producto::refreshCacheVersion();
        $this->forgetBulkImagePreview();

        $oldPaths->filter()->unique()->each(function (string $path): void {
            if (! Str::startsWith($path, ['http://', 'https://'])
                && ! Producto::query()->where('imagen', $path)->exists()
                && ! ProductoColorImage::query()->whereJsonContains('images', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
        });

        $message = "Imágenes aplicadas a {$updated} productos o colores.";
        if ($errors !== []) {
            $message .= ' No se pudieron aplicar: '.implode(' ', $errors);
        }

        return redirect()->route('admin.productos.index')->with($errors === [] ? 'status' : 'error', $message);
    }

    public function cancelBulkImages(): RedirectResponse
    {
        $this->forgetBulkImagePreview();

        return redirect()->route('admin.productos.images.bulk');
    }

    private function forgetBulkImagePreview(): void
    {
        $manifest = session(self::BULK_IMAGES_SESSION_KEY);
        if (! empty($manifest['token'])) {
            Storage::disk('local')->deleteDirectory('bulk-product-images/'.$manifest['token']);
        }
        session()->forget(self::BULK_IMAGES_SESSION_KEY);
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
        ], self::UPLOAD_MESSAGES);

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
        ], self::UPLOAD_MESSAGES);

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
        ], self::UPLOAD_MESSAGES);

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
