<?php

use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Admin\Departamento;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\Admin\TipoDocumento;
use App\Models\Brand;
use App\Http\Controllers\LibroReclamacionController;
use App\Support\GirlsHome;
use App\Support\ProductCards;
use App\Support\SessionCart;
use App\Support\StorefrontCategories;
use App\Services\ComboSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/carrito', function () {
    $items = collect(session('cart.items', []));
    $subtotal = $items->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
    $igv = $items->isEmpty() ? 0 : $subtotal * 0.18;

    return view('web.cart-summary', [
        'items' => $items,
        'itemCount' => $items->sum('qty'),
        'subtotal' => $subtotal,
        'igv' => $igv,
        'total' => $subtotal + $igv,
        'paymentMethods' => MetodoPago::active(),
    ]);
})->name('web.cart.index');

Route::get('/combos/{combo}', function (Combo $combo) {
    abort_unless($combo->status, 404);

    return view('web.combo-show', [
        'combo' => $combo,
        'comboSlots' => app(ComboSelectionService::class)->slots($combo),
    ]);
})->name('web.combos.show');

Route::post('/combos/{combo}/carrito', function (Request $request, Combo $combo, ComboSelectionService $selector) {
    abort_unless($combo->status, 404);

    if ($combo->price === null) {
        return back()->withErrors(['selections' => 'Este combo todavía no tiene un precio configurado.']);
    }

    $validated = $request->validate([
        'selections' => ['required', 'array'],
        'selections.*' => ['required', 'integer', 'exists:productos,id'],
    ]);
    $selectedVariants = $selector->resolveSelections($combo, $validated['selections']);
    $items = session('cart.items', []);
    $selectionIds = $selectedVariants->pluck('id')->implode('-');
    $key = 'combo-'.$combo->id.'-'.substr(hash('sha256', $selectionIds), 0, 16);

    $existingItem = $items[$key] ?? null;
    $existingQuantities = collect($existingItem['selections'] ?? [])->countBy('variant_id');
    foreach ($selectedVariants->countBy(fn (Producto $variant): int => $variant->id) as $variantId => $quantity) {
        $variant = $selectedVariants->firstWhere('id', (int) $variantId);
        $requestedQuantity = (int) ($existingQuantities->get($variantId, 0)) + (int) $quantity;

        if ($requestedQuantity > (int) $variant->stock) {
            return back()->withErrors([
                'selections' => "No hay stock suficiente para {$variant->name} en el carrito.",
            ])->withInput();
        }
    }

    $comboItem = [
        'kind' => 'combo',
        'combo_id' => $combo->id,
        'producto' => $combo->name,
        'qty' => 1,
        'price' => (float) $combo->price,
        'image' => $combo->imageUrl(),
        'selections' => $selectedVariants->map(fn (Producto $variant): array => [
            'variant_id' => $variant->id,
            'producto' => $variant->name,
            'talla' => $variant->talla,
            'color' => $variant->color,
        ])->values()->all(),
    ];

    if (isset($items[$key])) {
        $items[$key]['qty'] = (int) $items[$key]['qty'] + 1;
    } else {
        $items[$key] = $comboItem;
    }

    session(['cart.items' => $items]);

    return redirect()->route('web.cart.index')->with('status', 'Combo agregado al carrito.');
})->name('web.combos.cart.store');

Route::post('/carrito', function (Request $request) {
    $validated = $request->validate([
        'producto_id' => ['required', 'integer', 'exists:productos,id'],
        'color' => ['required', 'string', 'max:255'],
        'talla' => ['required', 'string', 'max:255'],
        'qty' => ['required', 'integer', 'min:1', 'max:99'],
    ]);

    $producto = Producto::query()->findOrFail($validated['producto_id']);
    $variant = Producto::query()
        ->where('name', $producto->name)
        ->when(
            $producto->zazu_company_id !== null,
            fn ($query) => $query->where('zazu_company_id', $producto->zazu_company_id),
        )
        ->where('color', $validated['color'])
        ->where('talla', $validated['talla'])
        ->where('price', '>', 0)
        ->where('stock', '>', 0)
        ->firstOrFail();

    SessionCart::addVariant($variant, (int) $validated['qty'], $producto);

    if ($request->boolean('checkout')) {
        return redirect()->route('web.cart.index');
    }

    return back()
        ->with('status', 'Producto agregado al carrito.')
        ->with('cart_open', true);
})->name('web.cart.store');

Route::patch('/carrito/{variantId}', function (Request $request, int $variantId) {
    $validated = $request->validate([
        'action' => ['required', 'in:increment,decrement'],
    ]);
    $items = session('cart.items', []);
    $key = (string) $variantId;

    abort_if(! isset($items[$key]), 404);

    $items[$key]['qty'] += $validated['action'] === 'increment' ? 1 : -1;

    if ($items[$key]['qty'] <= 0) {
        unset($items[$key]);
    }

    session(['cart.items' => $items]);

    return back()->with('cart_open', true);
})->name('web.cart.update');

Route::delete('/carrito/{variantId}', function (int $variantId) {
    $items = session('cart.items', []);
    unset($items[(string) $variantId]);
    session(['cart.items' => $items]);

    return back()->with('cart_open', true);
})->name('web.cart.destroy');

Route::delete('/carrito/combo/{itemKey}', function (string $itemKey) {
    $items = session('cart.items', []);
    unset($items[$itemKey]);
    session(['cart.items' => $items]);

    return back()->with('cart_open', true);
})->name('web.cart.combo.destroy');

Route::get('/checkout/datos-personales', function () {
    $items = collect(session('cart.items', []));

    if ($items->isEmpty()) {
        return redirect()->route('web.cart.index');
    }

    $subtotal = $items->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
    $igv = $subtotal * 0.18;

    $departamentos = Departamento::query()->with('provincias.distritos')->orderBy('name')->get();
    $provincias = $departamentos->flatMap(fn ($departamento) => $departamento->provincias)->sortBy('name')->values();
    $distritos = $provincias->flatMap(fn ($provincia) => $provincia->distritos)->sortBy('name')->values();

    return view('web.checkout-personal', [
        'items' => $items,
        'itemCount' => $items->sum('qty'),
        'subtotal' => $subtotal,
        'igv' => $igv,
        'shipping' => null,
        'total' => $subtotal + $igv,
        'documentTypes' => TipoDocumento::query()->orderBy('name')->get(),
        'departamentos' => $departamentos,
        'provincias' => $provincias,
        'distritos' => $distritos,
        'paymentMethods' => MetodoPago::active(),
    ]);
})->name('web.checkout.personal');

Route::get('/checkout/entrega-y-pago', function () {
    $items = collect(session('cart.items', []));

    if ($items->isEmpty()) {
        return redirect()->route('web.cart.index');
    }

    $subtotal = $items->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
    $igv = $subtotal * 0.18;
    $shipping = \App\Support\Shipping::costFor($subtotal);

    return view('web.checkout-delivery', [
        'items' => $items,
        'itemCount' => $items->sum('qty'),
        'subtotal' => $subtotal,
        'igv' => $igv,
        'shipping' => $shipping,
        'total' => $subtotal + $igv + $shipping,
        'paymentMethods' => MetodoPago::active(),
    ]);
})->name('web.checkout.delivery');

Route::get('/checkout/pago', function () {
    $items = collect(session('cart.items', []));

    if ($items->isEmpty()) {
        return redirect()->route('web.cart.index');
    }

    $subtotal = $items->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
    $igv = $subtotal * 0.18;
    $shipping = \App\Support\Shipping::costFor($subtotal);

    return view('web.checkout-payment', [
        'items' => $items,
        'itemCount' => $items->sum('qty'),
        'subtotal' => $subtotal,
        'igv' => $igv,
        'shipping' => $shipping,
        'discount' => 0,
        'total' => $subtotal + $igv + $shipping,
        'paymentMethods' => MetodoPago::active(),
    ]);
})->name('web.checkout.payment');

Route::post('/checkout/comprobante', function (Request $request) {
    $validated = $request->validate([
        'payment_receipt' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
    ]);

    if ($previousPath = session('checkout.payment_receipt_path')) {
        Storage::disk('public')->delete($previousPath);
    }

    $path = $validated['payment_receipt']->store('payment-receipts', 'public');
    session(['checkout.payment_receipt_path' => $path]);

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
        'name' => $validated['payment_receipt']->getClientOriginalName(),
        'size' => $validated['payment_receipt']->getSize(),
    ]);
})->name('web.checkout.receipt');

Route::get('/checkout/courier-agencies', function (Request $request) {
    $normalize = function (?string $value): string {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value);

        return trim(preg_replace('/\s+/', ' ', strtolower($value ?: '')));
    };

    $department = $normalize($request->query('departamento'));
    $province = $normalize($request->query('provincia'));
    $district = $normalize($request->query('distrito'));
    $address = $normalize($request->query('address'));
    $search = $normalize($request->query('q'));
    $terms = collect(explode(' ', "{$district} {$province} {$department} {$address} {$search}"))
        ->map(fn ($term) => trim($term))
        ->filter(fn ($term) => strlen($term) >= 3)
        ->unique()
        ->values();

    $agencies = DB::table('courier')
        ->select('id', 'ter_id', 'direccion', 'zona', 'provincia', 'departamento', 'lugar_over')
        ->whereNotNull('direccion')
        ->whereNotNull('lugar_over')
        ->get()
        ->map(function ($agency) use ($normalize, $department, $province, $district, $search, $terms) {
            $agencyDepartment = $normalize($agency->departamento);
            $agencyProvince = $normalize($agency->provincia);
            $agencyZone = $normalize($agency->zona);
            $agencyAddress = $normalize($agency->direccion);
            $agencyName = $normalize($agency->lugar_over);
            $haystack = "{$agencyName} {$agencyAddress} {$agencyZone} {$agencyProvince} {$agencyDepartment}";
            $score = 0;

            if ($department !== '' && $agencyDepartment === $department) {
                $score += 80;
            }

            if ($province !== '' && $agencyProvince === $province) {
                $score += 70;
            }

            if ($district !== '' && ($agencyZone === $district || str_contains($agencyName, $district) || str_contains($agencyAddress, $district))) {
                $score += 90;
            }

            if ($search !== '' && str_contains($haystack, $search)) {
                $score += 100;
            }

            foreach ($terms as $term) {
                if (str_contains($haystack, $term)) {
                    $score += 8;
                }
            }

            $agency->score = $score;

            return $agency;
        })
        ->sortByDesc(fn ($agency) => [$agency->score, $agency->departamento, $agency->provincia, $agency->zona])
        ->take(10)
        ->values()
        ->map(fn ($agency, int $index) => [
            'id' => $agency->id,
            'ter_id' => $agency->ter_id,
            'name' => $agency->lugar_over,
            'address' => $agency->direccion,
            'zone' => $agency->zona,
            'province' => $agency->provincia,
            'department' => $agency->departamento,
            'badge' => $index === 0 ? 'Mas cercana a tu direccion' : null,
        ]);

    return response()->json(['agencies' => $agencies]);
})->name('web.checkout.courier-agencies');

// Combos de la colección actual (Overshark u Overshark Girls), o de una marca con ?marca=.
Route::get('/combos', function (Request $request) {
    $brand = trim((string) $request->query('marca')) ?: (session('storefront.line') === 'mujer' ? 'Girls' : 'Overshark');

    return view('web.combos', [
        'combos' => \App\Support\ComboCatalog::forStore($brand),
        'brandLabel' => $brand === 'Girls' ? 'Overshark Girls' : $brand,
    ]);
})->name('web.combos.index');

// Arma tu outfit: combos de outfit (modalidad "Outfit" en Admin → Combos).
Route::get('/arma-tu-outfit', fn (\App\Support\OutfitBuilder $builder) => view('web.outfit', [
    'combos' => $builder->combos(),
]))->name('web.outfit');

// Nosotros: historia, ADN, proceso de fabricación, marcas y comunidad (textos y fotos en Admin → Página Nosotros).
Route::get('/nosotros', fn () => view('web.nosotros', [
    'c' => \App\Support\AboutPage::content(),
    'bravosBrand' => Brand::active()->whereRaw('UPPER(name) LIKE ?', ['%BRAVO%'])->first(),
]))->name('web.nosotros');

Route::get('/contacto', [\App\Http\Controllers\ContactController::class, 'create'])->name('web.contact');
Route::post('/contacto', [\App\Http\Controllers\ContactController::class, 'store'])->middleware('throttle:5,1')->name('web.contact.store');

// Overshark TV: maqueta del canal en vivo (datos de ejemplo, sin streaming ni chat reales).
Route::view('/tv', 'web.tv')->name('web.tv');

Route::get('/libro-de-reclamaciones', [LibroReclamacionController::class, 'create'])->name('web.claims.create');
Route::post('/libro-de-reclamaciones', [LibroReclamacionController::class, 'store'])->name('web.claims.store');

Route::get('/marcas/{brand:slug}', function (Brand $brand) {
    abort_unless($brand->is_active, 404);

    $fallbackImage = asset('images/default-hero-banner.png');
    $brandName = mb_strtoupper(trim($brand->name));
    $brandCombos = Combo::query()
        ->where('status', true)
        ->whereNotNull('imagen')
        ->whereRaw('UPPER(TRIM(brand)) = ?', [$brandName])
        ->latest()
        ->get();
    $products = ProductCards::make(
        Producto::query()
            ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->whereRaw('UPPER(TRIM(marca)) = ?', [$brandName])
            ->groupBy('zazu_company_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(48)
            ->get(),
        $fallbackImage,
        $brand->name,
    );

    return view('web.brand-show', [
        'brandPage' => $brand,
        'brandCombos' => $brandCombos,
        'products' => $products,
    ]);
})->name('web.brands.show');

Route::get('/productos/{producto}', function (Producto $producto) {
    $fallbackImage = asset('images/default-hero-banner.png');
    $variants = Producto::query()
        ->where('name', $producto->name)
        ->when(
            $producto->zazu_company_id !== null,
            fn ($query) => $query->where('zazu_company_id', $producto->zazu_company_id),
        )
        ->where('price', '>', 0)
        ->where('stock', '>', 0)
        ->orderBy('color')
        ->orderBy('talla')
        ->get();

    abort_if($variants->isEmpty(), 404);

    $galleries = ProductoColorImage::query()
        ->where('product_name', $producto->name)
        ->get()
        ->keyBy('color');
    $colorOptions = $variants
        ->whereNotNull('color')
        ->groupBy('color')
        // Mismo orden que la tarjeta del catálogo: primero el color con más stock.
        ->sortByDesc(fn ($items) => $items->sum('stock'))
        ->map(function ($items, string $color) use ($galleries, $producto, $fallbackImage) {
            $galleryImages = $galleries->get($color)?->imageUrls() ?? [];

            return [
                'name' => $color,
                'images' => $galleryImages ?: [$producto->imageUrl() ?? $fallbackImage],
                'sizes' => $items->pluck('talla')->filter()->unique()->values(),
            ];
        })
        ->values();
    $mainImages = $colorOptions->first()['images'] ?? [$producto->imageUrl() ?? $fallbackImage];
    $recommendedProductsQuery = Producto::query()
        ->selectRaw('MIN(id) as id, categoria_id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
        ->where('price', '>', 0)
        ->where('stock', '>', 0)
        ->where('name', '!=', $producto->name);

    if ($producto->categoria_id) {
        $recommendedProductsQuery->where('categoria_id', $producto->categoria_id);
    }

    $recommendedProducts = ProductCards::make(
        $recommendedProductsQuery
            ->groupBy('categoria_id', 'zazu_company_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(3)
            ->get(),
        $fallbackImage,
    );

    return view('web.product-show', [
        'producto' => $producto,
        'variants' => $variants,
        'colorOptions' => $colorOptions,
        'sizes' => $variants->pluck('talla')->filter()->unique()->values(),
        'mainImages' => $mainImages,
        'price' => (float) $variants->min('price') ?: (float) $producto->price,
        'paymentMethods' => MetodoPago::active(),
        'recommendedProducts' => $recommendedProducts,
    ]);
})->name('web.products.show');

// Tienda: catálogo con filtros (categoría, talla, color y precio), orden y paginación de 12.
Route::get('/buscar', function (Request $request) {
    // ?linea=hombre|mujer abre la tienda directamente en esa línea.
    if (in_array($request->query('linea'), ['hombre', 'mujer'], true)) {
        session(['storefront.line' => $request->query('linea')]);
    }

    $search = trim((string) $request->query('q', ''));
    $marca = trim((string) $request->query('marca', ''));
    $categoria = StorefrontCategories::find($request->query('categoria')) ? (string) $request->query('categoria') : '';
    $orden = in_array($request->query('orden'), ['novedades', 'populares', 'precio-asc', 'precio-desc'], true) ? (string) $request->query('orden') : '';
    $line = session('storefront.line') === 'mujer' ? 'mujer' : 'hombre';

    // Variantes a la venta de la línea actual (en hombre sin Girls, salvo que se busque algo concreto).
    $available = fn () => Producto::query()
        ->where('price', '>', 0)
        ->where('stock', '>', 0)
        ->when($marca !== '', fn ($query) => $query->where('marca', $marca))
        ->when($marca === '' && $line === 'mujer', fn ($query) => GirlsHome::onlyGirls($query))
        ->when($marca === '' && $line === 'hombre' && $search === '', fn ($query) => GirlsHome::exceptGirls($query));

    $sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    $sizes = $available()->whereNotNull('talla')->distinct()->pluck('talla')
        ->map(fn ($size) => mb_strtoupper(trim($size)))->unique()
        ->sortBy(fn ($size) => array_search($size, $sizeOrder, true) === false ? 99 : array_search($size, $sizeOrder, true))
        ->values();
    $colors = $available()->whereNotNull('color')->selectRaw('UPPER(TRIM(color)) as color_name')->distinct()->orderBy('color_name')->pluck('color_name');
    $bounds = $available()->selectRaw('MIN(price) as low, MAX(price) as high')->first();
    $priceFloor = (int) floor((float) $bounds?->low);
    $priceCeil = (int) ceil((float) $bounds?->high);

    $talla = $sizes->contains(mb_strtoupper((string) $request->query('talla'))) ? mb_strtoupper((string) $request->query('talla')) : '';
    $color = $colors->contains(mb_strtoupper(trim((string) $request->query('color')))) ? mb_strtoupper(trim((string) $request->query('color'))) : '';
    $min = $request->filled('min') ? max($priceFloor, min($priceCeil, (int) $request->query('min'))) : null;
    $max = $request->filled('max') ? max($priceFloor, min($priceCeil, (int) $request->query('max'))) : null;
    $min = $min === $priceFloor ? null : $min;
    $max = $max === $priceCeil ? null : $max;

    $filtered = fn (bool $withCategory) => $available()
        ->when($search !== '', function ($query) use ($search): void {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('default_code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('talla', 'like', "%{$search}%");
            });
        })
        ->when($talla !== '', fn ($query) => $query->whereRaw('UPPER(TRIM(talla)) = ?', [$talla]))
        ->when($color !== '', fn ($query) => $query->whereRaw('UPPER(TRIM(color)) = ?', [$color]))
        ->when($min !== null, fn ($query) => $query->where('price', '>=', $min))
        ->when($max !== null, fn ($query) => $query->where('price', '<=', $max + 0.99))
        ->when($withCategory && $categoria !== '', fn ($query) => StorefrontCategories::apply($query, $categoria));

    $categoryCounts = collect(StorefrontCategories::all())
        ->map(fn (array $category, string $slug) => StorefrontCategories::apply($filtered(false), $slug)->distinct()->count('name'));

    $products = $filtered(true)
        ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen, MIN(created_at) as first_seen_at')
        ->groupBy('zazu_company_id', 'name')
        ->when($orden === 'novedades', fn ($query) => $query->orderByDesc('first_seen_at'))
        ->when($orden === 'precio-asc', fn ($query) => $query->orderBy('min_price'))
        ->when($orden === 'precio-desc', fn ($query) => $query->orderByDesc('min_price'))
        ->orderByDesc('total_stock')
        ->orderBy('name')
        ->paginate(12)
        ->withQueryString();

    ProductCards::make($products->getCollection(), asset('images/default-hero-banner.png'));

    // Con un color elegido, la tarjeta muestra la foto y el nombre de ese color.
    if ($color !== '') {
        $products->getCollection()->each(function (Producto $product) use ($color): void {
            $match = collect($product->display_colors)->first(fn (array $option) => mb_strtoupper(trim($option['name'])) === $color);

            if ($match) {
                $product->setAttribute('display_image', $match['image']);
                $product->setAttribute('card_color', $match['name']);
            }
        });
    }

    return view('web.search', [
        'products' => $products,
        'search' => $search,
        'marca' => $marca,
        'categoria' => $categoria,
        'orden' => $orden,
        'filters' => array_filter(['q' => $search, 'marca' => $marca, 'categoria' => $categoria, 'talla' => $talla, 'color' => $color, 'min' => $min, 'max' => $max, 'orden' => $orden], fn ($value) => $value !== null && $value !== ''),
        'categoryCounts' => $categoryCounts,
        'sizes' => $sizes,
        'colors' => $colors,
        'priceFloor' => $priceFloor,
        'priceCeil' => $priceCeil,
    ]);
})->name('web.products.search');

// Catálogo de la colección mujer, con el diseño de Overshark Girls.
Route::get('/mujer/productos', function (Request $request) {
    session(['storefront.line' => 'mujer']);
    $search = trim((string) $request->query('q', ''));

    return view('web.girls-products', [
        'search' => $search,
        'products' => ProductCards::make(
            GirlsHome::onlyGirls(Producto::query())
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->where('price', '>', 0)
                ->where('stock', '>', 0)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($searchQuery) use ($search): void {
                        $searchQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('default_code', 'like', "%{$search}%")
                            ->orWhere('color', 'like', "%{$search}%");
                    });
                })
                ->groupBy('zazu_company_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(48)
                ->get(),
            asset('images/default-hero-banner.png'),
        ),
    ]);
})->name('web.girls.products');

Route::get('/explorar', fn () => view('web.explore'))->name('web.explore');

Route::get('/', function (Request $request) {
    $storeLines = ['hombre', 'mujer'];

    if (in_array($request->query('linea'), $storeLines, true)) {
        session(['storefront.line' => $request->query('linea')]);
    }

    $storeLine = session('storefront.line');

    if (! in_array($storeLine, $storeLines, true)) {
        return view('web.explore');
    }

    // "Los más vendidos": mismo orden que Tienda → populares (más stock movido primero), de la línea elegida.
    $bestSellers = ProductCards::make(
        Producto::query()
            ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->tap(fn ($query) => $storeLine === 'mujer' ? GirlsHome::onlyGirls($query) : GirlsHome::exceptGirls($query))
            ->groupBy('zazu_company_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(15)
            ->get(),
        asset('images/default-hero-banner.png'),
    );
    $bravosBrand = \App\Models\Brand::query()->active()->whereRaw('UPPER(name) LIKE ?', ['%BRAVO%'])->first();

    if ($storeLine === 'mujer') {
        return view('web.home-mujer', compact('bestSellers', 'bravosBrand'));
    }

    return view('web.home', [
        'storeLine' => $storeLine,
        'banners' => BannerPortada::activeForHome(),
        'bestSellers' => $bestSellers,
        'bravosBrand' => $bravosBrand,
    ]);
})->name('web.home');
