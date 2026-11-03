<?php

use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\Brand;
use App\Http\Controllers\LibroReclamacionController;
use App\Support\Checkout;
use App\Support\GirlsHome;
use App\Support\ProductCards;
use App\Support\SessionCart;
use App\Support\StorefrontCategories;
use App\Services\ComboSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Checkout (Figma "04 · Compra compartida"): Carrito → Datos y entrega → Pago → Estado del pedido.
Route::get('/carrito', fn () => view('web.checkout.cart', Checkout::summary()))->name('web.cart.index');

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

// Look de "Arma tu outfit" de Bravos: agrega cada prenda elegida (color y talla) a su precio individual.
Route::post('/carrito/look', function (Request $request) {
    $validated = $request->validate([
        'items' => ['required', 'array', 'min:1', 'max:4'],
        'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
        'items.*.color' => ['required', 'string', 'max:255'],
        'items.*.talla' => ['required', 'string', 'max:255'],
    ], [
        'items.required' => 'Elige las prendas de tu look.',
    ]);

    $variants = collect($validated['items'])->map(function (array $item) {
        $producto = Producto::query()->findOrFail($item['producto_id']);

        return [$producto, Producto::query()
            ->where('name', $producto->name)
            ->when($producto->zazu_company_id !== null, fn ($query) => $query->where('zazu_company_id', $producto->zazu_company_id))
            ->where('color', $item['color'])
            ->where('talla', $item['talla'])
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->first()];
    });

    if ($variants->contains(fn (array $pair) => $pair[1] === null)) {
        return back()->withErrors(['items' => 'Una de las prendas ya no tiene stock en ese color y talla. Elige otra opción.']);
    }

    $variants->each(fn (array $pair) => SessionCart::addVariant($pair[1], 1, $pair[0]));

    return back()
        ->with('status', 'Look agregado al carrito.')
        ->with('cart_open', true);
})->name('web.cart.look');

Route::patch('/carrito/{variantId}', function (Request $request, int $variantId) {
    $validated = $request->validate([
        'action' => ['required', 'in:increment,decrement'],
    ]);
    $items = session('cart.items', []);
    $key = (string) $variantId;

    abort_if(! isset($items[$key]), 404);

    // No se puede pedir más de lo que hay en stock.
    if ($validated['action'] === 'increment' && $items[$key]['qty'] >= (int) (Producto::query()->find($variantId)?->stock ?? 0)) {
        return back()->withErrors(['cart' => 'No hay más unidades disponibles de esa prenda.'])->with('cart_open', ! $request->boolean('stay'));
    }

    $items[$key]['qty'] += $validated['action'] === 'increment' ? 1 : -1;

    if ($items[$key]['qty'] <= 0) {
        unset($items[$key]);
    }

    session(['cart.items' => $items]);

    // Desde la página del carrito (stay=1) no se abre el carrito lateral.
    return back()->with('cart_open', ! $request->boolean('stay'));
})->name('web.cart.update');

Route::delete('/carrito/{variantId}', function (Request $request, int $variantId) {
    $items = session('cart.items', []);
    unset($items[(string) $variantId]);
    session(['cart.items' => $items]);

    return back()->with('cart_open', ! $request->boolean('stay'));
})->name('web.cart.destroy');

Route::delete('/carrito/combo/{itemKey}', function (Request $request, string $itemKey) {
    $items = session('cart.items', []);
    unset($items[$itemKey]);
    session(['cart.items' => $items]);

    return back()->with('cart_open', ! $request->boolean('stay'));
})->name('web.cart.combo.destroy');

Route::get('/checkout/datos-personales', function () {
    $summary = Checkout::summary();

    if ($summary['items']->isEmpty()) {
        return redirect()->route('web.cart.index');
    }

    return view('web.checkout.details', $summary + [
        'customer' => session('checkout.customer', []),
        'ubigeo' => Departamento::query()->with('provincias.distritos:id,provincia_id,name')->orderBy('name')->get(['id', 'name'])
            ->map(fn ($departamento) => [
                'id' => $departamento->id,
                'name' => $departamento->name,
                'provincias' => $departamento->provincias->sortBy('name')->values()->map(fn ($provincia) => [
                    'id' => $provincia->id,
                    'name' => $provincia->name,
                    'distritos' => $provincia->distritos->sortBy('name')->values()->map(fn ($distrito) => ['id' => $distrito->id, 'name' => $distrito->name]),
                ]),
            ]),
    ]);
})->name('web.checkout.personal');

Route::post('/checkout/datos-personales', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email', 'max:255'],
        'nombres' => ['required', 'string', 'max:120'],
        'apellidos' => ['required', 'string', 'max:120'],
        'phone' => ['required', 'regex:/^9\d{8}$/'],
        'doc_type' => ['required', 'in:DNI,CE'],
        'doc_number' => ['required', 'string', 'max:20', $request->input('doc_type') === 'DNI' ? 'regex:/^\d{8}$/' : 'regex:/^[A-Za-z0-9]{6,12}$/'],
        'distrito_id' => ['required', 'integer', 'exists:distritos,id'],
        'address' => ['required', 'string', 'max:255'],
        'reference' => ['nullable', 'string', 'max:255'],
        'delivery' => ['required', 'in:domicilio,agencia'],
        'agency' => ['required_if:delivery,agencia', 'nullable', 'string', 'max:255'],
        'invoice' => ['required', 'in:boleta,factura'],
        'ruc' => ['required_if:invoice,factura', 'nullable', 'regex:/^(10|20)\d{9}$/'],
        'razon_social' => ['required_if:invoice,factura', 'nullable', 'string', 'max:255'],
    ], [
        'email.required' => 'Ingresa tu correo.',
        'email.email' => 'Revisa tu correo.',
        'nombres.required' => 'Ingresa tus nombres.',
        'apellidos.required' => 'Ingresa tus apellidos.',
        'phone.required' => 'Ingresa tu celular.',
        'phone.regex' => 'Ingresa un celular de 9 dígitos que empiece con 9.',
        'doc_number.required' => 'Ingresa tu número de documento.',
        'doc_number.regex' => $request->input('doc_type') === 'DNI' ? 'El DNI tiene 8 dígitos.' : 'Revisa el número de tu carné de extranjería.',
        'distrito_id.required' => 'Elige tu distrito.',
        'address.required' => 'Ingresa tu dirección.',
        'agency.required_if' => 'Elige la agencia donde recogerás tu pedido.',
        'ruc.required_if' => 'Para factura necesitamos el RUC.',
        'ruc.regex' => 'El RUC tiene 11 dígitos y empieza con 10 o 20.',
        'razon_social.required_if' => 'Para factura necesitamos la razón social.',
    ]);

    // Fuera de Lima solo hay recojo en agencia.
    $department = Distrito::query()->with('provincia.departamento')->find($validated['distrito_id'])?->provincia?->departamento?->name;
    if ($validated['delivery'] === 'domicilio' && mb_strtoupper((string) $department) !== 'LIMA') {
        return back()->withInput()->withErrors(['delivery' => 'Fuera de Lima el envío es a una agencia. Elige "Recojo en agencia".']);
    }

    session(['checkout.customer' => $validated]);

    return redirect()->route('web.checkout.payment');
})->name('web.checkout.personal.store');

// La entrega se elige en "Datos y entrega"; la ruta antigua lleva ahí.
Route::get('/checkout/entrega-y-pago', fn () => redirect()->route('web.checkout.personal'))->name('web.checkout.delivery');

Route::get('/checkout/pago', function () {
    $summary = Checkout::summary();

    if ($summary['items']->isEmpty()) {
        return redirect()->route('web.cart.index');
    }

    if (! session('checkout.customer')) {
        return redirect()->route('web.checkout.personal');
    }

    // Un token por intento de compra: si el formulario se envía dos veces, se reutiliza el mismo pedido.
    if (! session('checkout.token')) {
        session(['checkout.token' => Str::random(40)]);
    }

    return view('web.checkout.payment', $summary + [
        'customer' => session('checkout.customer'),
        'methods' => Checkout::paymentMethods(),
        'token' => session('checkout.token'),
        'receiptPath' => session('checkout.payment_receipt_path'),
    ]);
})->name('web.checkout.payment');

Route::post('/checkout/pedido', function (Request $request) {
    $methods = Checkout::paymentMethods();
    $validated = $request->validate([
        'method' => ['required', 'in:'.$methods->pluck('id')->implode(',')],
        'token' => ['required', 'string', 'size:40'],
        'terms' => ['accepted'],
    ], [
        'method.required' => 'Elige cómo vas a pagar.',
        'terms.accepted' => 'Acepta los Términos y condiciones y la Política de privacidad para continuar.',
    ]);

    // Reenvío del mismo checkout (doble clic, volver atrás): se muestra el pedido ya creado.
    if ($existing = Order::query()->where('checkout_token', $validated['token'])->first()) {
        session()->push('checkout.orders', $existing->id);

        return redirect()->route('web.checkout.done', $existing);
    }

    $customer = session('checkout.customer');
    if (! $customer) {
        return redirect()->route('web.checkout.personal');
    }

    if ($validated['token'] !== session('checkout.token')) {
        return redirect()->route('web.checkout.payment')->withErrors(['method' => 'Tu sesión de pago cambió. Revisa tu pedido y vuelve a confirmar.']);
    }

    $method = $methods->firstWhere('id', $validated['method']);
    $receipt = session('checkout.payment_receipt_path');
    if ($method['receipt'] && ! $receipt) {
        return back()->withInput()->withErrors(['receipt' => 'Sube la captura de tu pago para confirmar el pedido.']);
    }

    // Precio, stock y combos se vuelven a validar en el servidor; si algo cambió, el carrito se conserva.
    $cart = Checkout::verifyCart();
    if ($cart['error']) {
        return redirect()->route('web.cart.index')->withErrors(['cart' => $cart['error']]);
    }

    $order = Checkout::place($customer, $method, $method['receipt'] ? $receipt : null, $validated['token'], $cart['lines']);

    session()->forget(['cart.items', 'checkout.token', 'checkout.payment_receipt_path']);
    session()->push('checkout.orders', $order->id);

    return redirect()->route('web.checkout.done', $order);
})->middleware('throttle:10,1')->name('web.checkout.place');

// Estado del pedido (pendiente o confirmado): solo lo ve quien lo hizo, desde su sesión.
Route::get('/checkout/pedido/{order}', function (Order $order) {
    abort_unless(in_array($order->id, session('checkout.orders', []), true), 404);

    return view('web.checkout.status', [
        'order' => $order->load('statusOrder'),
        'paid' => $order->statusOrder?->name === Checkout::STATUS_PAID,
    ]);
})->name('web.checkout.done');

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
// Nosotros y Contacto: con ?marca=bravos se ven con la paleta de Bravos y el menú de Bravos.
Route::get('/nosotros', fn (Request $request) => view('web.nosotros', [
    'c' => \App\Support\AboutPage::content(),
    'bravosBrand' => Brand::active()->whereRaw('UPPER(name) LIKE ?', ['%BRAVO%'])->first(),
    'brandPage' => $request->query('marca') === 'bravos' ? Brand::active()->where('slug', 'bravos')->first() : null,
]))->name('web.nosotros');

Route::get('/contacto', [\App\Http\Controllers\ContactController::class, 'create'])->name('web.contact');
Route::post('/contacto', [\App\Http\Controllers\ContactController::class, 'store'])->middleware('throttle:5,1')->name('web.contact.store');

// Overshark TV: maqueta del canal en vivo (datos de ejemplo, sin streaming ni chat reales).
Route::view('/tv', 'web.tv')->name('web.tv');

Route::get('/libro-de-reclamaciones', [LibroReclamacionController::class, 'create'])->name('web.claims.create');
Route::post('/libro-de-reclamaciones', [LibroReclamacionController::class, 'store'])->name('web.claims.store');

// Arma tu outfit de Bravos (Figma "03 · Arma tu Outfit Bravos"): una polera y un pantalón a precio individual.
Route::get('/marcas/bravos/arma-tu-outfit', function () {
    $brand = Brand::query()->active()->where('slug', 'bravos')->firstOrFail();

    return view('web.bravos-outfit', [
        'brandPage' => $brand,
        'steps' => \App\Support\BravosOutfit::steps($brand),
    ]);
})->name('web.bravos.outfit');

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
            ->ofBrand($brand->name)
            ->groupBy('zazu_company_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(48)
            ->get(),
        $fallbackImage,
        $brand->name,
    );

    // Bravos tiene su propia portada (diseño Figma "01 · Home Bravos · Black & White").
    if ($brand->slug === 'bravos') {
        $range = Producto::query()->ofBrand($brand->name)->where('price', '>', 0)->where('stock', '>', 0);

        return view('web.bravos', [
            'brandPage' => $brand,
            'favorites' => $products->take(4),
            'modelCount' => (clone $range)->distinct()->count('name'),
            'colorCount' => (clone $range)->whereNotNull('color')->distinct()->count('color'),
        ]);
    }

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

    // Productos de Bravos: ficha con el diseño de la marca (Figma "PDP · Polera Neru · Desktop").
    $bravosBrand = Producto::query()->whereKey($producto->id)->ofBrand('BRAVOS')->exists()
        ? Brand::query()->active()->where('slug', 'bravos')->first()
        : null;

    if ($bravosBrand) {
        $recommendedProducts = ProductCards::make(
            Producto::query()
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->ofBrand($bravosBrand->name)
                ->where('price', '>', 0)
                ->where('stock', '>', 0)
                ->where('name', '!=', $producto->name)
                ->groupBy('zazu_company_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(3)
                ->get(),
            $fallbackImage,
        );
    }

    return view($bravosBrand ? 'web.product-show-bravos' : 'web.product-show', [
        'brandPage' => $bravosBrand,
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
        ->when($marca !== '', fn ($query) => $query->ofBrand($marca))
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

    // La tienda de Bravos tiene su propio diseño (Figma "02 · Tienda Bravos · Todos").
    $bravosBrand = mb_strtoupper($marca) === 'BRAVOS' ? Brand::query()->active()->where('slug', 'bravos')->first() : null;

    return view($bravosBrand ? 'web.search-bravos' : 'web.search', [
        'bravosBrand' => $bravosBrand,
        'allCount' => $bravosBrand ? $filtered(false)->distinct()->count('name') : null,
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
