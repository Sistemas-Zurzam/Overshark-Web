<?php

use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Admin\Departamento;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\Admin\TipoDocumento;
use App\Http\Controllers\LibroReclamacionController;
use App\Support\ProductCards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

Route::post('/carrito', function (Request $request) {
    $validated = $request->validate([
        'producto_id' => ['required', 'integer', 'exists:productos,id'],
        'color' => ['required', 'string', 'max:255'],
        'talla' => ['required', 'string', 'max:255'],
        'qty' => ['required', 'integer', 'min:1', 'max:99'],
    ]);

    $producto = Producto::query()->findOrFail($validated['producto_id']);
    $variant = Producto::query()
        ->where('odoo_template_id', $producto->odoo_template_id)
        ->where('color', $validated['color'])
        ->where('talla', $validated['talla'])
        ->where('price', '>', 0)
        ->where('qty_available', '>', 0)
        ->firstOrFail();

    $maxQty = max(1, (int) floor((float) $variant->qty_available));
    $qty = min((int) $validated['qty'], $maxQty);
    $galleryImages = ProductoColorImage::query()
        ->where('odoo_template_id', $variant->odoo_template_id)
        ->where('color', $variant->color)
        ->first()
        ?->imageUrls() ?? [];
    $image = $galleryImages[0] ?? $variant->imageUrl() ?? $producto->imageUrl() ?? asset('images/default-hero-banner.png');
    $items = session('cart.items', []);
    $key = (string) $variant->id;
    $newQty = min(($items[$key]['qty'] ?? 0) + $qty, $maxQty);

    $items[$key] = [
        'producto_id' => $producto->id,
        'variant_id' => $variant->id,
        'producto' => $variant->name,
        'qty' => $newQty,
        'price' => (float) $variant->price,
        'color' => $variant->color,
        'talla' => $variant->talla,
        'image' => $image,
    ];

    session(['cart.items' => $items]);

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
    $shipping = 12;

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

Route::get('/libro-de-reclamaciones', [LibroReclamacionController::class, 'create'])->name('web.claims.create');
Route::post('/libro-de-reclamaciones', [LibroReclamacionController::class, 'store'])->name('web.claims.store');

Route::get('/productos/{producto}', function (Producto $producto) {
    $fallbackImage = asset('images/default-hero-banner.png');
    $variants = Producto::query()
        ->where('odoo_template_id', $producto->odoo_template_id)
        ->where('price', '>', 0)
        ->where('qty_available', '>', 0)
        ->orderBy('color')
        ->orderBy('talla')
        ->get();

    abort_if($variants->isEmpty(), 404);

    $galleries = ProductoColorImage::query()
        ->where('odoo_template_id', $producto->odoo_template_id)
        ->get()
        ->keyBy('color');
    $colorOptions = $variants
        ->whereNotNull('color')
        ->groupBy('color')
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
        ->selectRaw('MIN(id) as id, odoo_template_id, categoria_id, name, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
        ->whereNotNull('odoo_template_id')
        ->where('price', '>', 0)
        ->where('qty_available', '>', 0)
        ->where('odoo_template_id', '!=', $producto->odoo_template_id);

    if ($producto->categoria_id) {
        $recommendedProductsQuery->where('categoria_id', $producto->categoria_id);
    }

    $recommendedProducts = ProductCards::make(
        $recommendedProductsQuery
            ->groupBy('odoo_template_id', 'categoria_id', 'name')
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

Route::get('/buscar', function (Request $request) {
    $fallbackImage = asset('images/default-hero-banner.png');
    $search = trim((string) $request->query('q', ''));
    $products = collect();

    if ($search !== '') {
        $products = ProductCards::make(
            Producto::query()
                ->selectRaw('MIN(id) as id, odoo_template_id, name, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->whereNotNull('odoo_template_id')
                ->where('price', '>', 0)
                ->where('qty_available', '>', 0)
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('default_code', 'like', "%{$search}%")
                        ->orWhere('color', 'like', "%{$search}%")
                        ->orWhere('talla', 'like', "%{$search}%");
                })
                ->groupBy('odoo_template_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(24)
                ->get(),
            $fallbackImage,
        );
    }

    return view('web.search', [
        'products' => $products,
        'search' => $search,
    ]);
})->name('web.products.search');

Route::get('/', function () {
    $fallbackImage = asset('images/default-hero-banner.png');
    $productCacheVersion = Producto::cacheVersion();
    $bestSellingProducts = Cache::remember(
        "home.best_selling_products.{$productCacheVersion}",
        now()->addMinutes(30),
        fn () => ProductCards::make(
            Producto::query()
                ->selectRaw('MIN(id) as id, odoo_template_id, name, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->whereNotNull('odoo_template_id')
                ->where('price', '>', 0)
                ->where('qty_available', '>', 0)
                ->groupBy('odoo_template_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(4)
                ->get(),
            $fallbackImage,
        ),
    );
    $shortSleeveProducts = Cache::remember("home.short_sleeve_products.{$productCacheVersion}", now()->addMinutes(30), function () use ($bestSellingProducts, $fallbackImage) {
        $products = ProductCards::make(
            Producto::query()
                ->selectRaw('MIN(id) as id, odoo_template_id, name, SUM(qty_available) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->whereNotNull('odoo_template_id')
                ->where('price', '>', 0)
                ->where('qty_available', '>', 0)
                ->where(function ($query) {
                    $query->where('name', 'like', '%MANGA CORTA%')
                        ->orWhere('name', 'like', '%CLASICO%')
                        ->orWhere('name', 'like', '%WAFFLE%');
                })
                ->groupBy('odoo_template_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(3)
                ->get(),
            $fallbackImage,
        );

        return $products->isEmpty() ? $bestSellingProducts->take(3) : $products;
    });

    return view('web.home', [
        'banners' => BannerPortada::activeForHome(),
        'combos' => Combo::activeForMenu(),
        'bestSellingProducts' => $bestSellingProducts,
        'shortSleeveProducts' => $shortSleeveProducts,
    ]);
})->name('web.home');

