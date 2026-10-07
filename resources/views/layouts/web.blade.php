@php
    $brand = \App\Models\BrandSetting::current();
    $menuCombos = \App\Models\Admin\Combo::activeForMenu();
    $brandPageContext = $brandPage ?? null;
    $publicBrandLogo = $brandPageContext?->logoUrl() ?: $brand->logoUrl();
    if ($brandPageContext) {
        $brandPageName = mb_strtoupper(trim($brandPageContext->name));
        $menuCombos = $menuCombos
            ->filter(fn ($menuCombo) => mb_strtoupper(trim((string) $menuCombo->brand)) === $brandPageName)
            ->values();
    }
    $menuBrands = \App\Models\Brand::query()
        ->active()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
    $cartItems = collect(session('cart.items', []));
    $cartCount = $cartItems->sum('qty');
    $cartTotal = $cartItems->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
    $storeLine = session('storefront.line') === 'mujer' ? 'mujer' : 'hombre';
    $homeUrl = route('web.home');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Overshark, tienda online">
    <meta name="theme-color" content="#030a1c">
    <title>@yield('title', 'Overshark')</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,400;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="theme-neon min-h-screen antialiased">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-edge/60 bg-ink/85 text-white backdrop-blur-md">
        <div class="flex h-9 items-center justify-center sm:h-10">
            <nav class="flex rounded-full border border-edge bg-panel/80 p-0.5 text-[10px] font-bold uppercase tracking-[0.22em] sm:text-[11px]" aria-label="Línea de colección">
                <a href="{{ route('web.home', ['linea' => 'hombre']) }}" @class([
                    'rounded-full px-5 py-1 transition sm:px-7',
                    'bg-white text-ink' => $storeLine === 'hombre',
                    'text-mist hover:text-white' => $storeLine !== 'hombre',
                ]) @if ($storeLine === 'hombre') aria-current="page" @endif>Hombre</a>
                <a href="{{ route('web.home', ['linea' => 'mujer']) }}" @class([
                    'rounded-full px-5 py-1 transition sm:px-7',
                    'bg-white text-ink' => $storeLine === 'mujer',
                    'text-mist hover:text-white' => $storeLine !== 'mujer',
                ]) @if ($storeLine === 'mujer') aria-current="page" @endif>Mujer</a>
            </nav>
        </div>

        <div class="relative mx-auto flex h-16 max-w-[1440px] items-center justify-between px-3 sm:h-[72px] sm:px-5 lg:px-12">
            <div class="flex items-center gap-8">
                <button type="button" data-combos-toggle class="combo-trigger group hidden text-sm font-semibold sm:flex" aria-expanded="false" aria-controls="combos-menu">
                    <img data-combo-flame class="combo-fire-icon" src="{{ asset('images/iconos/fuego_combo.svg') }}" alt="" aria-hidden="true">
                    Combos
                </button>

                <nav class="hidden items-center gap-8 text-[15px] font-medium text-mist lg:flex" aria-label="Navegación principal">
                    <a href="{{ $homeUrl }}#productos" class="transition hover:text-white">Productos</a>
                    <div class="relative">
                        <button type="button" data-brands-toggle data-brands-menu-target="brands-menu-desktop" class="flex items-center gap-1.5 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-neon-soft" aria-expanded="false" aria-haspopup="true" aria-controls="brands-menu-desktop">
                            Marcas
                            <svg class="h-4 w-4 text-haze transition-transform" data-brands-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </button>
                        <div id="brands-menu-desktop" data-brands-menu data-state="closed" class="brands-panel absolute left-1/2 top-[calc(100%+22px)] z-50 w-72 -translate-x-1/2 rounded-2xl border border-edge bg-panel p-3 text-left shadow-2xl shadow-black/50">
                            <div class="border-b border-edge px-3 pb-3">
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-neon-soft">Nuestras marcas</p>
                                <p class="mt-1 text-xs text-haze">Explora productos por marca.</p>
                            </div>
                            <div class="mt-2 max-h-64 overflow-y-auto">
                                @forelse ($menuBrands as $menuBrand)
                                    <a href="{{ route('web.brands.show', $menuBrand) }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-bold text-white transition hover:bg-white/5 hover:text-neon-soft">
                                        <span>{{ $menuBrand->name }}</span>
                                        <span class="h-3 w-3 rounded-full border border-edge-strong" style="background-color: {{ $menuBrand->primary_color }}" aria-hidden="true"></span>
                                        <span class="text-neon-soft" aria-hidden="true">-&gt;</span>
                                    </a>
                                @empty
                                    <p class="px-3 py-4 text-sm text-haze">Aún no hay marcas asignadas.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <a href="{{ $homeUrl }}#nosotros" class="transition hover:text-white">Nosotros</a>
                </nav>
            </div>

            <a href="{{ $homeUrl }}" class="absolute left-1/2 flex h-14 w-40 -translate-x-1/2 items-center justify-center overflow-hidden text-xl font-black tracking-normal sm:h-16 sm:w-56 sm:text-2xl lg:w-64 lg:text-[28px]" aria-label="Overshark inicio">
                @if ($publicBrandLogo)
                    <img src="{{ $publicBrandLogo }}" alt="{{ $brandPageContext?->name ?: 'Overshark' }}" class="h-12 w-full object-contain sm:h-14 sm:scale-125 lg:h-16 lg:scale-150">
                @else
                    <span class="uppercase italic tracking-tight">Overshark</span>
                @endif
            </a>

            <div class="flex items-center gap-0.5 sm:gap-2">
                <button type="button" data-search-toggle class="grid h-10 w-10 place-items-center rounded-full text-mist transition hover:bg-white/5 hover:text-white" aria-label="Buscar productos" aria-expanded="false" aria-controls="search-panel">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="hidden h-10 w-10 place-items-center rounded-full text-mist transition hover:bg-white/5 hover:text-white sm:grid" aria-label="Mi cuenta">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5.5 21c.4-5 2.6-7.5 6.5-7.5s6.1 2.5 6.5 7.5"/></svg>
                </a>
                <button type="button" data-cart-open class="relative grid h-10 w-10 place-items-center rounded-full text-mist transition hover:bg-white/5 hover:text-white" aria-label="Bolsa de compras">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    @if ($cartCount > 0)
                        <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-neon px-1 text-[11px] font-black leading-none text-white">{{ $cartCount }}</span>
                    @endif
                </button>
                <button type="button" data-web-menu-toggle class="grid h-10 w-10 place-items-center rounded-full text-mist transition hover:bg-white/5 hover:text-white lg:hidden" aria-label="Abrir menú" aria-expanded="false">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div id="search-panel" data-search-panel class="hidden border-t border-edge/60 bg-ink/95 px-3 py-4 sm:px-5">
            <form action="{{ route('web.products.search') }}" method="GET" class="mx-auto flex max-w-2xl items-center gap-2">
                <label for="product-search" class="sr-only">Buscar productos</label>
                <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-4 w-4 text-haze" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                    </div>
                    <input type="text" id="product-search" name="q" value="{{ request('q') }}" class="block w-full rounded-full border border-edge-strong bg-panel py-3 pl-10 pr-4 text-sm font-semibold text-white outline-none placeholder:text-haze focus:border-neon-soft focus:ring-4 focus:ring-neon/25" placeholder="Buscar polos, camiseros, waffle..." required>
                </div>
                <button type="submit" class="btn-primary h-11 shrink-0 px-6">Buscar</button>
            </form>
        </div>

        <div id="combos-menu" data-combos-menu data-state="closed" class="combos-panel absolute left-0 right-0 top-full border-t border-edge/60 bg-ink shadow-2xl shadow-black/60">
            <div class="mx-auto grid max-w-[1440px] gap-4 px-5 py-6 sm:grid-cols-2 lg:grid-cols-3 lg:px-12">
                @forelse ($menuCombos as $combo)
                    <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="combo-card group relative min-h-48 overflow-hidden rounded-2xl border border-edge bg-panel transition hover:border-neon-soft">
                        <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/30 to-transparent"></div>
                        <div class="relative flex h-full min-h-48 items-end p-5">
                            <div>
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-neon-soft">Ver combo</span>
                                <h2 class="mt-1 text-xl font-black text-white">{{ $combo->name }}</h2>
                                @if ($combo->price !== null)
                                    <p class="mt-1 text-sm font-black text-white/85">S/ {{ number_format((float) $combo->price, 2) }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-edge bg-panel px-5 py-10 text-center text-sm font-semibold text-haze">
                        Aún no hay combos activos.
                    </div>
                @endforelse
            </div>
        </div>

        <nav data-web-menu class="hidden border-t border-edge/60 bg-ink px-5 py-4 text-sm font-semibold lg:hidden" aria-label="Navegación móvil">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <button type="button" data-combos-toggle class="flex items-center justify-between rounded-lg px-3 py-3 text-left hover:bg-white/5" aria-expanded="false" aria-controls="combos-menu">
                    Combos
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <a href="{{ $homeUrl }}#productos" class="rounded-lg px-3 py-3 hover:bg-white/5">Productos</a>
                <a href="{{ $homeUrl }}#categorias" class="rounded-lg px-3 py-3 hover:bg-white/5">Categorías</a>
                <div>
                    <button type="button" data-brands-toggle data-brands-menu-target="brands-menu-mobile" class="flex w-full items-center justify-between rounded-lg px-3 py-3 text-left hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-neon-soft" aria-expanded="false" aria-haspopup="true" aria-controls="brands-menu-mobile">
                        Marcas
                        <svg class="h-4 w-4 text-haze transition-transform" data-brands-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </button>
                    <div id="brands-menu-mobile" data-brands-menu data-state="closed" class="brands-panel-mobile ml-3 border-l border-edge pl-3">
                        @forelse ($menuBrands as $menuBrand)
                            <a href="{{ route('web.brands.show', $menuBrand) }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm text-mist hover:bg-white/5 hover:text-white">
                                <span>{{ $menuBrand->name }}</span>
                                <span class="h-3 w-3 rounded-full border border-edge-strong" style="background-color: {{ $menuBrand->primary_color }}" aria-hidden="true"></span>
                                <span aria-hidden="true">-&gt;</span>
                            </a>
                        @empty
                            <p class="px-3 py-3 text-sm text-haze">Aún no hay marcas asignadas.</p>
                        @endforelse
                    </div>
                </div>
                <a href="{{ $homeUrl }}#nosotros" class="rounded-lg px-3 py-3 hover:bg-white/5">Nosotros</a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-3 hover:bg-white/5">Mi cuenta</a>
            </div>
        </nav>
    </header>

    <main class="pt-[100px] sm:pt-[112px]">@yield('content')</main>

    <div data-cart-overlay class="fixed inset-0 z-[60] hidden bg-black/60" aria-hidden="true"></div>
    <aside data-cart-drawer data-open="{{ session('cart_open') ? 'true' : 'false' }}" class="cart-drawer fixed bottom-0 right-0 top-0 z-[70] flex w-full max-w-full translate-x-full flex-col border-l border-edge bg-ink text-white shadow-2xl shadow-black/60 transition-transform duration-300 sm:max-w-[430px]" aria-label="Carrito de compras">
        <div class="flex h-[76px] items-center justify-between border-b border-edge px-5 sm:h-[84px] sm:px-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-neon-soft">Tu carrito</p>
                <h2 class="text-xl font-black">{{ $cartCount }} producto{{ $cartCount === 1 ? '' : 's' }}</h2>
            </div>
            <button type="button" data-cart-close class="grid h-10 w-10 place-items-center rounded-full border border-edge text-mist transition hover:border-neon-soft hover:text-white" aria-label="Cerrar carrito">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5 sm:px-6">
            @forelse ($cartItems as $itemKey => $item)
                @php
                    $variantId = $item['variant_id'] ?? 0;
                    $isCombo = ($item['kind'] ?? null) === 'combo';
                    $qty = (int) ($item['qty'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);
                    $oldPrice = $price > 0 ? $price / 0.8 : 0;
                @endphp
                <div class="grid grid-cols-[76px_1fr] gap-3 border-b border-edge py-4 last:border-b-0 sm:grid-cols-[84px_1fr] sm:gap-4">
                    <div class="h-[110px] w-[76px] overflow-hidden rounded-lg bg-frame sm:h-[122px] sm:w-[84px]">
                        <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain object-center">
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-black">{{ $item['producto'] ?? 'Producto' }}</h3>
                                @if ($isCombo)
                                    <p class="mt-1 text-xs font-black uppercase tracking-wide text-neon-soft">Combo · {{ $qty }} unidad{{ $qty === 1 ? '' : 'es' }}</p>
                                    @foreach (collect($item['selections'] ?? [])->take(3) as $selection)
                                        <p class="mt-1 truncate text-xs text-mist">{{ $selection['producto'] ?? 'Producto' }} · {{ $selection['talla'] ?? '-' }} · {{ $selection['color'] ?? '-' }}</p>
                                    @endforeach
                                @else
                                    <p class="mt-1 text-sm text-mist">Color: {{ $item['color'] ?? '-' }}</p>
                                    <p class="mt-1 text-sm text-mist">Talla: {{ $item['talla'] ?? '-' }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-white">S/ {{ number_format($price, 2) }}</p>
                                @if ($oldPrice > 0)
                                    <p class="mt-1 text-xs text-haze line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            @if ($isCombo)
                                <span class="rounded-md bg-panel-2 px-3 py-2 text-xs font-black text-mist">{{ $qty }} combo{{ $qty === 1 ? '' : 's' }}</span>
                            @else
                                <div class="grid w-28 grid-cols-3 overflow-hidden rounded-full border border-edge">
                                    <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="increment">
                                        <button type="submit" class="grid h-9 w-full place-items-center text-xl font-bold text-mist transition hover:bg-white/5 hover:text-white" aria-label="Aumentar cantidad">+</button>
                                    </form>
                                    <span class="grid h-9 place-items-center text-sm text-white">{{ $qty }}</span>
                                    <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="decrement">
                                        <button type="submit" class="grid h-9 w-full place-items-center text-xl font-bold text-mist transition hover:bg-white/5 hover:text-white" aria-label="Disminuir cantidad">-</button>
                                    </form>
                                </div>
                            @endif

                            <form method="POST" action="{{ $isCombo ? route('web.cart.combo.destroy', $itemKey) : route('web.cart.destroy', $variantId) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="grid h-9 w-9 place-items-center rounded-md text-haze transition hover:bg-red-500/10 hover:text-red-400" aria-label="Eliminar producto">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14"/><path d="M10 11v6M14 11v6"/><path d="M8 7l1-3h6l1 3"/><path d="M6.5 7 8 21h8l1.5-14"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="grid h-16 w-16 place-items-center rounded-full border border-edge bg-panel">
                        <svg class="h-8 w-8 text-haze" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    </div>
                    <p class="mt-4 text-lg font-black">Tu carrito está vacío</p>
                    <p class="mt-1 text-sm text-haze">Agrega productos para verlos aquí.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-edge bg-ink px-4 py-5 sm:px-6">
            @if ($cartItems->isNotEmpty())
                <div class="mb-5 rounded-2xl border border-edge bg-panel px-4 py-4">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-live" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/></svg>
                        <div>
                            <p class="text-sm font-black">¡Ahorra más comprando en pack!</p>
                            <p class="mt-1 text-xs text-haze">Agrega un producto adicional y obtén mejor precio.</p>
                        </div>
                    </div>
                </div>
                <div class="mb-4 flex items-center justify-between text-sm font-black">
                    <span>Total</span>
                    <span>S/ {{ number_format($cartTotal, 2) }}</span>
                </div>
                <a href="{{ route('web.cart.index') }}" class="btn-primary w-full gap-2 px-5 py-4">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    Comprar ahora
                </a>
            @endif
            <button type="button" data-cart-close class="btn-secondary mt-3 w-full px-5 py-4">Seguir comprando</button>
        </div>
    </aside>

    <footer id="contacto" class="border-t border-edge bg-ink text-white">
        <div class="border-b border-edge px-5 py-7">
            <a href="{{ $homeUrl }}" class="mx-auto flex w-fit items-center justify-center" aria-label="Overshark inicio">
                @if ($publicBrandLogo)
                    <img src="{{ $publicBrandLogo }}" alt="{{ $brandPageContext?->name ?: 'Overshark' }}" class="h-16 w-56 object-contain sm:w-64">
                @else
                    <span class="text-3xl font-black uppercase italic tracking-tight">Overshark</span>
                @endif
            </a>
        </div>

        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-x-6 gap-y-10 px-6 py-12 lg:grid-cols-4 lg:px-8">
            <section>
                <h2 class="text-xs font-bold uppercase tracking-[0.22em] text-neon-soft">Comprar</h2>
                <nav class="mt-5 flex flex-col gap-3 text-sm text-mist">
                    <a href="{{ route('web.products.search', ['q' => 'polo']) }}" class="transition hover:text-white">Polos</a>
                    <a href="{{ route('web.products.search', ['q' => 'media']) }}" class="transition hover:text-white">Medias</a>
                    <a href="{{ $homeUrl }}#combos" class="flex items-center gap-2 transition hover:text-white">
                        Combos
                        <img src="{{ asset('images/iconos/Fire.svg') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">
                    </a>
                    <a href="{{ route('web.home', ['linea' => 'mujer']) }}" class="flex items-center gap-2 transition hover:text-white">
                        Para ellas
                        <img src="{{ asset('images/iconos/Heart.svg') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">
                    </a>
                </nav>
            </section>

            <section>
                <h2 class="text-xs font-bold uppercase tracking-[0.22em] text-neon-soft">Ayuda</h2>
                <nav class="mt-5 flex flex-col gap-3 text-sm text-mist">
                    <a href="#contacto" class="transition hover:text-white">Envíos</a>
                    <a href="#contacto" class="transition hover:text-white">Cambios y devoluciones</a>
                    <a href="#contacto" class="transition hover:text-white">Métodos de pago</a>
                </nav>
            </section>

            <section>
                <h2 class="text-xs font-bold uppercase tracking-[0.22em] text-neon-soft">Contacto</h2>
                <div class="mt-5 space-y-5 text-sm leading-6 text-mist">
                    <p><span class="block font-bold text-white">Correo:</span> ventas@overshark.com</p>
                    <p><span class="block font-bold text-white">Horario de atención:</span> 9am a 6pm L-V</p>
                </div>
            </section>

            <section>
                <h2 class="text-xs font-bold uppercase tracking-[0.22em] text-neon-soft">Envíos a todo el Perú</h2>
                <div class="mt-5 space-y-2 text-sm text-mist">
                    <p>Lima: delivery o agencia</p>
                    <p>Provincia: agencia</p>
                    <p class="mt-3 w-fit rounded-full border border-neon/50 bg-neon/15 px-4 py-1.5 font-bold text-neon-soft">S/14 costo único</p>
                </div>
                <a href="{{ route('web.claims.create') }}" class="mt-5 block w-fit rounded-lg bg-white p-1 transition hover:opacity-80" aria-label="Abrir libro de reclamaciones">
                    <img src="{{ asset('images/iconos/libro_reclamaciones.svg') }}" alt="Libro de reclamaciones" class="h-auto w-44">
                </a>
            </section>
        </div>

        <div class="border-t border-edge">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-6 py-6 text-sm text-haze sm:flex-row lg:px-8">
                <div class="flex items-center gap-4">
                    @foreach ([
                        ['Facebook', 'facebook.svg'],
                        ['Instagram', 'instagram.svg'],
                        ['TikTok', 'tiktok.svg'],
                        ['WhatsApp', 'whatsapp.svg'],
                    ] as [$social, $icon])
                        <a href="#" class="grid h-9 w-9 place-items-center rounded-full border border-edge bg-panel text-white transition hover:-translate-y-0.5 hover:border-neon-soft hover:bg-neon" aria-label="{{ $social }}">
                            <img src="{{ asset('images/iconos/'.$icon) }}" alt="" class="h-5 w-5 object-contain brightness-0 invert" aria-hidden="true">
                        </a>
                    @endforeach
                </div>
                <p>© {{ date('Y') }} Overshark. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    @yield('scripts')
</body>
</html>
