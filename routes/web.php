<?php

use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Models\Admin\StatusOrder;
use App\Services\Orders\OrderFulfillment;
use App\Services\Orders\OrderPayments;
use App\Services\Payments\CulqiGateway;
use App\Services\Payments\PaymentSimulator;
use App\Services\Payments\YapeQr;
use Illuminate\Validation\Rule;
use App\Models\Brand;
use App\Http\Controllers\LibroReclamacionController;
use App\Support\AccountCheckout;
use App\Support\CartSuggestions;
use App\Support\Checkout;
use App\Support\GirlsHome;
use App\Support\ProductCards;
use App\Support\Promotions;
use App\Support\SessionCart;
use App\Support\StorefrontCategories;
use App\Services\ComboSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
    ], [
        'producto_id.exists' => 'Este producto ya no está disponible.',
        'color.required' => 'Elige un color.',
        'talla.required' => 'Elige una talla.',
        'qty.max' => 'Puedes agregar hasta 99 unidades por vez.',
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
        ->inStore()
        ->where('price', '>', 0)
        ->where('stock', '>', 0)
        ->first();

    // La talla pudo agotarse en Zazu mientras la clienta tenía abierta la ficha.
    if (! $variant) {
        return back()->withErrors(['cart' => 'Esa talla se acaba de agotar en este color. Elige otra opción.']);
    }

    $before = SessionCart::quantityOf($variant->id);
    $after = SessionCart::addVariant($variant, (int) $validated['qty'], $producto);
    $added = $after - $before;

    if ($added <= 0) {
        return back()
            ->withErrors(['cart' => "Ya tienes en tu carrito todas las unidades disponibles de esta prenda ({$after})."])
            ->with('cart_open', true);
    }

    $notice = $added < (int) $validated['qty']
        ? "Solo quedaban {$after} unidades disponibles: agregamos {$added} a tu carrito."
        : null;

    if ($request->boolean('checkout')) {
        return redirect()->route('web.cart.index')->with('cart_notice', $notice);
    }

    // Desde "Te puede interesar" de la página del carrito solo se suma la prenda, sin volver a abrir nada.
    if ($request->boolean('quiet')) {
        return back()->with('status', 'Producto agregado al carrito.')->with('cart_notice', $notice);
    }

    // Con sugerencias disponibles se muestra primero el modal "Agregado"; si no hay, se abre el carrito lateral.
    $hasSuggestions = CartSuggestions::forProduct($producto)->isNotEmpty();

    return back()
        ->with('status', 'Producto agregado al carrito.')
        ->with('cart_notice', $notice)
        ->with($hasSuggestions ? 'cart_suggest' : 'cart_open', $hasSuggestions ? $producto->id : true);
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
            ->inStore()
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

    // Con cuenta: se precargan sus datos y su dirección (la principal, o la que elija con ?direccion=). Lo que ya
    // escribió en esta sesión manda; invitados siguen igual que antes.
    $account = auth('customer')->user();
    $savedAddresses = $account ? $account->addresses()->get() : collect();
    $session = session('checkout.customer', []);
    $address = $account
        ? ($savedAddresses->firstWhere('id', (int) request('direccion')) ?? ($session ? null : $savedAddresses->firstWhere('is_default', true)))
        : null;
    $customer = $session ?: AccountCheckout::contact($account);

    if ($address) {
        $customer = AccountCheckout::withAddress($customer, $address);
    }

    return view('web.checkout.details', $summary + [
        'customer' => $customer,
        'savedAddresses' => $savedAddresses,
        'selectedAddressId' => $address?->id,
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
        'doc_number' => ['required', 'string', 'max:12', \App\Support\DocumentNumber::rule($request->input('doc_type'))],
        // Con recojo en almacén no se pide dirección.
        'distrito_id' => ['exclude_if:delivery,almacen', 'required', 'integer', 'exists:distritos,id'],
        'address' => ['exclude_unless:delivery,domicilio', 'required', 'string', 'max:255'],
        'reference' => ['exclude_unless:delivery,domicilio', 'nullable', 'string', 'max:255'],
        'delivery' => ['required', 'in:domicilio,agencia,almacen'],
        'delivery_lat' => ['exclude_unless:delivery,domicilio', 'required', 'numeric', 'between:-90,90'],
        'delivery_lng' => ['exclude_unless:delivery,domicilio', 'required', 'numeric', 'between:-180,180'],
        // Id de la agencia Shalom elegida (tabla courier).
        'agency' => ['exclude_unless:delivery,agencia', 'required', 'integer', 'exists:courier,id'],
        'invoice' => ['required', 'in:boleta,factura'],
        // Solo con cuenta: guardar esta dirección en la libreta (opción explícita).
        'save_address' => ['nullable', 'boolean'],
        'address_alias' => ['nullable', 'in:Casa,Trabajo,Otro'],
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
        'doc_number.regex' => \App\Support\DocumentNumber::message($request->input('doc_type')),
        'distrito_id.required' => 'Elige tu distrito.',
        'address.required' => 'Ingresa tu dirección.',
        'agency.required' => 'Elige la agencia Shalom donde recogerás tu pedido.',
        'agency.integer' => 'Elige una agencia Shalom de la lista.',
        'agency.exists' => 'Elige una agencia Shalom de la lista.',
        'ruc.required_if' => 'Para factura necesitamos el RUC.',
        'ruc.regex' => 'El RUC tiene 11 dígitos y empieza con 10 o 20.',
        'razon_social.required_if' => 'Para factura necesitamos la razón social.',
    ]);

    if ($validated['delivery'] === 'domicilio' && ! \App\Support\DeliveryCoverage::contains((float) $validated['delivery_lat'], (float) $validated['delivery_lng'])) {
        return back()->withInput()->withErrors(['delivery' => 'La dirección marcada está fuera de la cobertura de delivery. Elige recojo en agencia Shalom o en almacén.']);
    }

    // Se guarda el id (para volver a marcarla) y "Agencia — dirección" (para el pedido y el despacho).
    if ($validated['delivery'] === 'agencia') {
        $agency = DB::table('courier')->find($validated['agency']);
        $validated['agency_id'] = $agency->id;
        // ter_id: el id de la agencia en Shalom, con el que el CRM la reconoce.
        $validated['agency_shalom_id'] = $agency->ter_id;
        $validated['agency'] = 'Shalom '.$agency->lugar_over.' — '.$agency->direccion;
    }

    if (($account = auth('customer')->user()) && $request->boolean('save_address')) {
        AccountCheckout::saveAddress($account, $validated, $request->input('address_alias'));
    }

    session(['checkout.customer' => Arr::except($validated, ['save_address', 'address_alias'])]);

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

    $customer = session('checkout.customer');
    $distrito = Distrito::query()->with('provincia.departamento')->find($customer['distrito_id'] ?? 0);

    return view('web.checkout.payment', $summary + [
        'customer' => $customer,
        // "Distrito, Provincia, Departamento" para la tarjeta "Enviar a".
        'place' => $distrito ? collect([$distrito->name, $distrito->provincia?->name, $distrito->provincia?->departamento?->name])->filter()->implode(', ') : null,
        'deposit' => Checkout::depositAmount($customer['delivery'] ?? null, $summary['total']),
        'shippingDateLabel' => Checkout::shippingDateLabel(Checkout::shippingDate($customer['delivery'] ?? null)),
        'culqiKey' => CulqiGateway::isConfigured() ? CulqiGateway::publicKey() : null,
        'culqiSecureUrl' => CulqiGateway::secureUrl(),
        'simulating' => PaymentSimulator::enabled(),
        'methods' => Checkout::paymentMethods(),
        'token' => session('checkout.token'),
        'receiptPath' => session('checkout.payment_receipt_path'),
    ]);
})->name('web.checkout.payment');

Route::post('/checkout/pedido', function (Request $request, CulqiGateway $culqi) {
    $methods = Checkout::availableMethods(); // los "Muy pronto" no se pueden elegir
    $online = Checkout::onlineMethods();
    $deposit = Checkout::depositAmount(session('checkout.customer.delivery'), Checkout::summary()['total']);
    $validated = $request->validate([
        'method' => ['required', 'in:'.$methods->pluck('id')->implode(',')],
        // Contraentrega con separo: con qué se paga el separo ahora (tarjeta o Yape/Plin).
        'deposit_method' => [Rule::requiredIf(fn () => $request->input('method') === Checkout::CASH_ON_DELIVERY && $deposit > 0), 'nullable', 'in:'.$online->pluck('id')->implode(',')],
        // Token de la tarjeta generado por Culqi Checkout en el navegador.
        'culqi_token' => ['nullable', 'string', 'max:100'],
        'token' => ['required', 'string', 'size:40'],
        'terms' => ['accepted'],
        // Total que la clienta vio en la pantalla de pago: el pedido solo se crea por ese importe.
        'expected_total' => ['required', 'numeric', 'min:0'],
    ], [
        'method.required' => 'Elige cómo vas a pagar.',
        'deposit_method.required' => 'Elige cómo pagarás el separo.',
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
    $isCod = $method['id'] === Checkout::CASH_ON_DELIVERY;
    // Medio con el que se paga en línea: el elegido, o el del separo en contraentrega (sin separo: nada).
    $payWith = $isCod ? ($deposit > 0 ? $online->firstWhere('id', $validated['deposit_method'] ?? null) : null) : $method;

    $receipt = session('checkout.payment_receipt_path');
    if (($payWith['receipt'] ?? false) && ! $receipt) {
        return back()->withInput()->withErrors(['receipt' => $isCod ? 'Sube la captura del pago de tu separo para confirmar el pedido.' : 'Sube la captura de tu pago para confirmar el pedido.']);
    }

    // La tarjeta se cobra con Culqi: el navegador ya creó el token con los datos de la clienta.
    $byCulqi = ($payWith['kind'] ?? null) === 'card';
    if ($byCulqi && blank($validated['culqi_token'] ?? null)) {
        return back()->withInput()->withErrors(['method' => 'Ingresa los datos de tu tarjeta para continuar.']);
    }

    // Precio, stock y combos se vuelven a validar en el servidor; si algo cambió, el carrito se conserva.
    $cart = Checkout::verifyCart();
    if ($cart['error']) {
        return redirect()->route('web.cart.index')->withErrors(['cart' => $cart['error']]);
    }

    // Si un precio cambió mientras pagaba, no se confirma un importe distinto: se actualiza el resumen
    // y se pide confirmar de nuevo el total nuevo.
    $total = Checkout::totalFor($cart['lines'], $customer['delivery'] ?? null);
    if (abs($total - round((float) $validated['expected_total'], 2)) >= 0.01) {
        Checkout::syncPrices();

        return redirect()->route('web.checkout.payment')->withErrors([
            'method' => 'El precio de tu pedido cambió. El nuevo total es S/ '.number_format($total, 2).'. Revisa el resumen y confirma de nuevo.',
        ]);
    }

    // El pedido se crea antes de cobrar: así nunca queda un cobro sin pedido.
    $order = Checkout::place($customer, $method, $payWith, ($payWith['receipt'] ?? false) ? $receipt : null, $validated['token'], $cart['lines'], auth('customer')->id());

    if ($order->wasRecentlyCreated) {
        // Tarjeta: se cobra el total (o el separo) con Culqi. Si la rechaza, el pedido no queda y puede reintentar.
        if ($byCulqi) {
            $charge = $culqi->charge((float) $order->pago_monto, $validated['culqi_token'], $order->email, "Pedido {$order->number_zazu} Overshark", ['pedido' => $order->number_zazu]);

            if (! $charge['ok']) {
                $order->delete();

                return redirect()->route('web.checkout.payment')->withErrors(['method' => $charge['message']]);
            }

            OrderPayments::confirm($order, $charge['id']);
        } elseif (($payWith['kind'] ?? null) === 'yape') {
            // Yape: se genera el QR y el pedido espera el pago (la página del pedido muestra el QR).
            OrderPayments::startQr($order, app(YapeQr::class));
        } elseif (! $payWith) {
            // Contraentrega sin nada que pagar en línea (recojo en almacén): se procesa ya; se cobra al entregar.
            OrderFulfillment::start($order);
        }
        // Captura de Yape/Plin del admin: queda pendiente hasta que el equipo confirme el pago en el admin.
    }

    session()->forget(['cart.items', 'checkout.token', 'checkout.payment_receipt_path']);
    session()->push('checkout.orders', $order->id);

    return redirect()->route('web.checkout.done', $order);
})->middleware('throttle:10,1')->name('web.checkout.place');

// Estado del pedido (pendiente o confirmado): solo lo ve quien lo hizo, desde su sesión.
Route::get('/checkout/pedido/{order}', function (Order $order) {
    // Lo ve quien lo hizo desde esta sesión o la cuenta dueña del pedido (nunca otra cuenta ni otro invitado).
    $ownsOrder = auth('customer')->check() && $order->customer_account_id === auth('customer')->id();
    abort_unless($ownsOrder || in_array($order->id, session('checkout.orders', []), true), 404);

    return view('web.checkout.status', [
        'order' => $order->load('statusOrder'),
        'paid' => $order->statusOrder?->name === Checkout::STATUS_PAID,
        'depositPaid' => $order->statusOrder?->name === Checkout::STATUS_DEPOSIT_PAID,
        'awaitingQr' => $order->statusOrder?->name === Checkout::STATUS_AWAITING_QR,
        'cancelled' => $order->statusOrder?->name === Checkout::STATUS_CANCELLED,
        'simulating' => PaymentSimulator::enabled(),
    ]);
})->name('web.checkout.done');

// Modo simulación: "Simular pago" del QR de Yape (en real lo confirma Ligo Pay vía Zazu).
Route::post('/checkout/pedido/{order}/simular-pago', function (Order $order) {
    abort_unless(PaymentSimulator::enabled() && in_array($order->id, session('checkout.orders', []), true), 404);

    if ($order->load('statusOrder')->statusName() === Checkout::STATUS_AWAITING_QR) {
        OrderPayments::confirm($order, $order->pago_referencia);
    }

    return redirect()->route('web.checkout.done', $order);
})->name('web.checkout.simulate-qr');

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

// Agencias Shalom (tabla courier) para "Recojo en agencia Shalom": las de la provincia elegida (o del
// departamento si la provincia no tiene), primero las del distrito de la clienta. ?q= filtra por nombre,
// dirección o zona.
Route::get('/checkout/courier-agencies', function (Request $request) {
    $normalize = function (?string $value): string {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value);

        return trim(preg_replace('/[\s\-]+/', ' ', strtolower($value ?: '')));
    };

    $department = $normalize($request->query('departamento'));
    $province = $normalize($request->query('provincia'));
    $district = $normalize($request->query('distrito'));
    $terms = collect(explode(' ', $normalize($request->query('q'))))->filter(fn ($term) => strlen($term) >= 2);

    $all = DB::table('courier')
        ->select('id', 'direccion', 'zona', 'provincia', 'departamento', 'lugar_over')
        ->whereNotNull('direccion')
        ->where('direccion', '!=', '')
        ->whereNotNull('lugar_over')
        ->get()
        ->each(function ($agency) use ($normalize) {
            $agency->dep = $normalize($agency->departamento);
            $agency->prov = $normalize($agency->provincia);
            $agency->zone = $normalize($agency->zona);
            $agency->haystack = $normalize("{$agency->lugar_over} {$agency->direccion} {$agency->zona} {$agency->provincia}");
        });

    $inDepartment = $department === '' ? $all : $all->where('dep', $department);
    $inProvince = $province === '' ? collect() : $inDepartment->where('prov', $province);
    $pool = $inProvince->isNotEmpty() ? $inProvince : $inDepartment;

    $agencies = $pool
        ->filter(fn ($agency) => $terms->every(fn ($term) => str_contains($agency->haystack, $term)))
        ->map(function ($agency) use ($district) {
            $agency->near = $district !== '' && $agency->zone !== '' && (str_contains($agency->zone, $district) || str_contains($district, $agency->zone));

            return $agency;
        })
        ->sortBy(fn ($agency) => [$agency->near ? 0 : 1, $agency->zona, $agency->lugar_over])
        ->take(200)
        ->values()
        ->map(fn ($agency) => [
            'id' => $agency->id,
            'name' => $agency->lugar_over,
            'address' => $agency->direccion,
            'zone' => $agency->zona,
            'near' => $agency->near,
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

Route::view('/terminos-y-condiciones', 'web.legal-page', ['document' => 'terms'])->name('web.terms');
Route::view('/politica-de-privacidad', 'web.legal-page', ['document' => 'privacy'])->name('web.privacy');

Route::get('/libro-de-reclamaciones', [LibroReclamacionController::class, 'create'])->name('web.claims.create');
Route::post('/libro-de-reclamaciones', [LibroReclamacionController::class, 'review'])->name('web.claims.submit');
Route::get('/libro-de-reclamaciones/revisar', [LibroReclamacionController::class, 'showReview'])->name('web.claims.review');
Route::get('/libro-de-reclamaciones/editar', [LibroReclamacionController::class, 'edit'])->name('web.claims.edit');
Route::post('/libro-de-reclamaciones/confirmar', [LibroReclamacionController::class, 'store'])->name('web.claims.store');
Route::get('/libro-de-reclamaciones/no-se-pudo-enviar', [LibroReclamacionController::class, 'error'])->name('web.claims.error');
Route::get('/libro-de-reclamaciones/solicitud-registrada', [LibroReclamacionController::class, 'registered'])->name('web.claims.registered');
Route::get('/libro-de-reclamaciones/constancia', [LibroReclamacionController::class, 'receipt'])->name('web.claims.receipt');

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
            ->where('stock', '>', 0)->withColorPhoto()
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
        $range = Producto::query()->ofBrand($brand->name)->where('price', '>', 0)->where('stock', '>', 0)->withColorPhoto();

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
        ->where('stock', '>', 0)->withColorPhoto()
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
        // Mismo orden que la tarjeta del catálogo: primero los colores con foto propia y, entre ellos, el de más stock.
        ->sortByDesc(fn ($items, string $color) => [$galleries->has($color) ? 1 : 0, $items->sum('stock')])
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
        ->where('stock', '>', 0)->withColorPhoto()
        ->where('name', '!=', $producto->name)
        // Recomendaciones de la misma línea (Overshark Girls u Overshark hombre).
        ->tap(fn ($query) => GirlsHome::isGirls($producto->name, $producto->marca) ? GirlsHome::onlyGirls($query) : GirlsHome::exceptGirls($query));

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

    // Ficha con el diseño de Figma "PDP": paleta de Bravos, Overshark Girls u Overshark según el producto.
    $bravosBrand = Producto::query()->whereKey($producto->id)->ofBrand('BRAVOS')->exists()
        ? Brand::query()->active()->where('slug', 'bravos')->first()
        : null;

    if ($bravosBrand) {
        $recommendedProducts = ProductCards::make(
            Producto::query()
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->ofBrand($bravosBrand->name)
                ->where('price', '>', 0)
                ->where('stock', '>', 0)->withColorPhoto()
                ->where('name', '!=', $producto->name)
                ->groupBy('zazu_company_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(3)
                ->get(),
            $fallbackImage,
        );
    }

    return view('web.product-show', [
        'brandPage' => $bravosBrand,
        'line' => $bravosBrand ? 'bravos' : (GirlsHome::isGirls($producto->name, $producto->marca) ? 'girls' : 'hombre'),
        'producto' => $producto,
        'variants' => $variants,
        'colorOptions' => $colorOptions,
        'sizes' => $variants->pluck('talla')->filter()->unique()->values(),
        'mainImages' => $mainImages,
        'price' => (float) $variants->min('price') ?: (float) $producto->price,
        'promo' => Promotions::best((string) $producto->name, $producto->marca, $producto->empresa_nombre, (float) $variants->min('price') ?: (float) $producto->price),
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
    // En Zazu la Baby Tee se llama "Baby Ty": la búsqueda acepta el nombre con el que se vende.
    $searchTerm = preg_replace('/\bbaby\s*tees?\b/iu', 'baby ty', $search);
    $marca = trim((string) $request->query('marca', ''));
    $categoria = StorefrontCategories::find($request->query('categoria')) ? (string) $request->query('categoria') : '';
    $orden = in_array($request->query('orden'), ['novedades', 'populares', 'precio-asc', 'precio-desc'], true) ? (string) $request->query('orden') : '';
    $line = session('storefront.line') === 'mujer' ? 'mujer' : 'hombre';

    // Variantes a la venta de la línea actual (en hombre sin Girls, salvo que se busque algo concreto).
    $available = fn () => Producto::query()
        ->where('price', '>', 0)
        ->where('stock', '>', 0)->withColorPhoto()
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
        ->when($search !== '', function ($query) use ($searchTerm): void {
            $query->where(function ($searchQuery) use ($searchTerm): void {
                $searchQuery->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('default_code', 'like', "%{$searchTerm}%")
                    ->orWhere('color', 'like', "%{$searchTerm}%")
                    ->orWhere('talla', 'like', "%{$searchTerm}%");
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
    // En Zazu la Baby Tee se llama "Baby Ty": la búsqueda acepta el nombre con el que se vende.
    $searchTerm = preg_replace('/\bbaby\s*tees?\b/iu', 'baby ty', $search);

    return view('web.girls-products', [
        'search' => $search,
        'products' => ProductCards::make(
            GirlsHome::onlyGirls(Producto::query())
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->where('price', '>', 0)
                ->where('stock', '>', 0)->withColorPhoto()
                ->when($search !== '', function ($query) use ($searchTerm): void {
                    $query->where(function ($searchQuery) use ($searchTerm): void {
                        $searchQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('default_code', 'like', "%{$searchTerm}%")
                            ->orWhere('color', 'like', "%{$searchTerm}%");
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
            ->where('stock', '>', 0)->withColorPhoto()
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

    $overshark = \App\Models\Brand::query()->active()->whereRaw('UPPER(name) = ?', ['OVERSHARK'])->first();

    // "Ofertas relámpago": prendas de la línea con una promoción por horas vigente, las más baratas primero.
    $flashDeals = ProductCards::make(
        Promotions::constrain(Producto::query())
            ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
            ->where('price', '>', 0)
            ->where('stock', '>', 0)->withColorPhoto()
            ->tap(fn ($query) => GirlsHome::exceptGirls($query))
            ->groupBy('zazu_company_id', 'name')
            ->orderByDesc('total_stock')
            ->limit(8)
            ->get(),
        asset('images/default-hero-banner.png'),
    )->filter(fn (Producto $product) => $product->promo !== null)->values();

    return view('web.home', [
        'storeLine' => $storeLine,
        'banners' => BannerPortada::activeForHome($overshark?->id),
        'bestSellers' => $bestSellers,
        'flashDeals' => $flashDeals,
        'bravosBrand' => $bravosBrand,
    ]);
})->name('web.home');

// Cuentas de compradores (registro, acceso, Mi cuenta).
require __DIR__.'/customer.php';
