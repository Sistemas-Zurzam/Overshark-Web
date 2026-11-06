@php
    $brand = \App\Models\BrandSetting::current();
    $brandPageContext = $brandPage ?? null;
    $publicBrandLogo = $brandPageContext?->logoUrl() ?: $brand->logoUrl();
    $combosUrl = $brandPageContext ? route('web.combos.index', ['marca' => $brandPageContext->name]) : route('web.combos.index');
    $menuBrands = \App\Models\Brand::query()
        ->active()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
    $cartItems = collect(session('cart.items', []));
    $cartCount = $cartItems->sum('qty');
    $cartTotal = $cartItems->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
    $storeLine = session('storefront.line') === 'mujer' ? 'mujer' : 'hombre';
    $customerAccount = auth('customer')->user();
    // Al agregar una prenda con sugerencias se abre el modal "Te puede interesar"; sin ellas, el carrito lateral.
    $suggestAdded = session('cart_suggest') ? \App\Models\Admin\Producto::query()->find(session('cart_suggest')) : null;
    $suggestions = $suggestAdded ? \App\Support\CartSuggestions::forProduct($suggestAdded) : collect();
    $cartOpen = session('cart_open') || ($suggestAdded && $suggestions->isEmpty());
    $otherLine = $storeLine === 'mujer' ? 'hombre' : 'mujer';
    $selectedLine = in_array(session('storefront.line'), ['hombre', 'mujer'], true) ? session('storefront.line') : null;
    $homeUrl = route('web.home');
    $onSearch = request()->routeIs('web.products.search');
    // En Bravos (portada, tienda, ficha y outfit), Inicio, Tienda y Arma tu outfit llevan a las páginas de Bravos.
    $bravosContext = $brandPageContext?->slug === 'bravos' || ($onSearch && mb_strtoupper(trim((string) request('marca'))) === 'BRAVOS');
    $storeMenu = [
        $bravosContext && ($bravosNavBrand = $menuBrands->firstWhere('slug', 'bravos'))
            ? ['label' => 'Inicio', 'url' => route('web.brands.show', $bravosNavBrand), 'active' => request()->routeIs('web.brands.show')]
            : ['label' => 'Inicio', 'url' => route('web.home', ['linea' => $storeLine]), 'active' => request()->routeIs('web.home')],
        $bravosContext
            ? ['label' => 'Tienda', 'url' => route('web.products.search', ['marca' => 'BRAVOS']), 'active' => $onSearch]
            : ['label' => 'Tienda', 'url' => route('web.products.search', ['orden' => 'populares']), 'active' => $onSearch],
        $bravosContext
            ? ['label' => '¡Arma tu Outfit!', 'url' => route('web.bravos.outfit'), 'active' => request()->routeIs('web.bravos.outfit')]
            : ['label' => '¡Arma tu Outfit!', 'url' => route('web.outfit'), 'active' => request()->routeIs('web.outfit')],
        ['label' => 'Nosotros', 'url' => route('web.nosotros', $bravosContext ? ['marca' => 'bravos'] : []), 'active' => request()->routeIs('web.nosotros')],
        ['label' => 'Contacto', 'url' => route('web.contact', $bravosContext ? ['marca' => 'bravos'] : []), 'active' => request()->routeIs('web.contact')],
    ];
    $navLinks = collect([
        [
            'label' => 'Novedades',
            'url' => route('web.products.search', ['orden' => 'novedades']),
            'active' => $onSearch && request('orden') === 'novedades' && ! request('categoria'),
        ],
    ])->concat(collect(\App\Support\StorefrontCategories::all())->map(fn (array $category, string $slug) => [
        'label' => $category['label'],
        'url' => route('web.products.search', ['categoria' => $slug]),
        'active' => $onSearch && request('categoria') === $slug,
    ])->values());
@endphp
<!DOCTYPE html>
<html lang="es" data-line="{{ $bravosContext ? 'bravos' : $storeLine }}" data-bravos-logo="{{ asset('images/bravos/logo-bravos-full-white.svg') }}" data-overshark-logo="{{ asset('images/nav/logo-overshark-full-light.svg') }}" data-girls-logo="{{ asset('images/girls/logo-girls-full-light.svg') }}">
<head>
    {{-- Si se viene de cambiar de colección, la cortina arranca cerrada antes del primer pintado (ver resources/js/line-transition.js). --}}
    <script>try{var l=sessionStorage.getItem('os-line-wipe');if(l==='hombre'||l==='mujer'||l==='bravos'){document.documentElement.classList.add('line-wipe-in');document.documentElement.dataset.lineWipe=l;}}catch(e){}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Overshark, tienda online')">
    <meta name="theme-color" content="#030a1c">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overshark')</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Big+Shoulders+Display:wght@900&family=Josefin+Sans:wght@600;700&family=Manrope:wght@500;700;800&family=Montserrat:ital,wght@0,500;0,600;0,700;0,800;0,900;1,900&family=Playfair+Display:ital,wght@1,400;1,500&display=swap" rel="stylesheet">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="--header-h: 108px" class="@yield('body-class', $storeLine === 'mujer' ? 'theme-neon theme-girls' : 'theme-neon') min-h-screen antialiased">
    <a href="#contenido" class="sr-only z-[80] rounded-md bg-white px-4 py-2 font-bold text-ink focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Saltar al contenido</a>

    <header class="@yield('header-class', 'os-header fixed inset-x-0 top-0 z-50 text-white')">
        @hasSection('header-bar')
            @yield('header-bar')
        @else
        {{-- Barra principal (diseño Figma "Nav Principal / Overshark"): logos de marca · menú central · búsqueda, cuenta y bolsa. Debajo, franja de anuncios. --}}
        <div class="os-nav-bar">
            <div class="mx-auto grid h-[72px] max-w-[1440px] grid-cols-[1fr_auto] items-center gap-4 px-4 sm:px-6 xl:grid-cols-[auto_1fr_auto] xl:gap-8 xl:px-[100px]">
                {{-- Marcas (Figma "Nav Principal"): logos blancos a la misma altura; la marca activa va resaltada
                     (y primero en Bravos, como en "Nav Principal / Bravos"). En celular solo se ve la activa. --}}
                @php
                    $navBravos = $menuBrands->first(fn ($menuBrand) => $menuBrand->slug === 'bravos');
                    $activeBrand = $bravosContext ? 'bravos' : ($storeLine === 'mujer' && ! $brandPageContext ? 'girls' : 'overshark');
                    $navBrandList = collect([
                        'overshark' => ['url' => route('web.home', ['linea' => 'hombre']), 'label' => 'Overshark, colección hombre', 'logo' => 'logo-overshark.svg', 'w' => 95, 'h' => 16.1, 'class' => 'h-[22px]'],
                        'girls' => ['url' => route('web.home', ['linea' => 'mujer']), 'label' => 'Overshark Girls, colección mujer', 'logo' => 'logo-girls.svg', 'w' => 88.3, 'h' => 26, 'class' => 'h-[26px]'],
                        'bravos' => $navBravos ? ['url' => route('web.brands.show', $navBravos), 'label' => 'Bravos, urban clothing', 'logo' => 'logo-bravos.svg', 'w' => 80.2, 'h' => 25.6, 'class' => 'h-[25px]'] : null,
                    ])->filter();
                    if ($activeBrand === 'bravos') {
                        $navBrandList = $navBrandList->sortBy(fn ($brandItem, $key) => $key === 'bravos' ? 0 : 1);
                    }
                @endphp
                <nav class="os-brands flex min-w-0 items-center" aria-label="Nuestras marcas">
                    @foreach ($navBrandList as $key => $brandItem)
                        @unless ($loop->first)
                            <span @class(['os-brands__divider', 'hidden sm:block']) aria-hidden="true"></span>
                        @endunless
                        <a href="{{ $brandItem['url'] }}" @class(['os-brands__item os-focus', 'is-active flex' => $key === $activeBrand, 'hidden sm:flex' => $key !== $activeBrand]) aria-label="{{ $brandItem['label'] }}" @if ($key === $activeBrand) aria-current="true" @endif>
                            <img src="{{ asset('images/nav/'.$brandItem['logo']) }}" alt="" width="{{ $brandItem['w'] }}" height="{{ $brandItem['h'] }}" class="{{ $brandItem['class'] }} w-auto max-w-none">
                        </a>
                    @endforeach
                </nav>

                <nav class="hidden items-center justify-center gap-9 xl:flex" aria-label="Navegación principal">
                    @foreach ($storeMenu as $item)
                        <a href="{{ $item['url'] }}" class="os-store-link os-focus" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center justify-end gap-[22px]">
                    <button type="button" data-search-toggle class="os-focus os-nav-icon" aria-label="Buscar productos" aria-expanded="false" aria-controls="search-panel">
                        <img src="{{ asset('images/nav/buscar.svg') }}" alt="" width="22" height="22">
                    </button>
                    {{-- Cuenta de comprador (opcional): invitados y registrados usan la misma tienda y el mismo carrito. --}}
                    @if ($customerAccount)
                    <details data-account-menu class="os-nav-icon os-nav-icon--account">
                        <summary class="os-focus os-nav-icon relative cursor-pointer list-none [&::-webkit-details-marker]:hidden" aria-label="{{ $customerAccount ? 'Mi cuenta, '.$customerAccount->name : 'Mi cuenta' }}">
                            <img src="{{ asset('images/nav/cuenta.svg') }}" alt="" width="22" height="22">
                            @if ($customerAccount)<span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-[#2fb37f] ring-2 ring-[#061c2d]" aria-hidden="true"></span>@endif
                        </summary>
                        <div class="absolute right-0 top-[calc(100%+14px)] z-[65] w-56 bg-white py-2 text-[14px] font-bold text-[#0b0f14] shadow-[0_12px_40px_rgb(11_15_20/22%)]">
                            @if ($customerAccount)
                                <p class="truncate border-b border-[#eef1f4] px-4 pb-2 text-[12px] font-semibold text-[#65717c]">{{ $customerAccount->email }}</p>
                                <a href="{{ route('customer.account') }}" class="block px-4 py-2.5 hover:bg-[#f5f7f9]">Mi cuenta</a>
                                <a href="{{ route('customer.orders') }}" class="block px-4 py-2.5 hover:bg-[#f5f7f9]">Mis pedidos</a>
                                <a href="{{ route('customer.addresses') }}" class="block px-4 py-2.5 hover:bg-[#f5f7f9]">Mis direcciones</a>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2.5 text-left text-[#a3261c] hover:bg-[#fdecec]">Cerrar sesión</button>
                                </form>
                            @endif
                        </div>
                    </details>
                    @else
                        {{-- Invitado: el ícono lleva directo a iniciar sesión (desde ahí también se crea la cuenta). --}}
                        <a href="{{ route('login') }}" class="os-focus os-nav-icon os-nav-icon--account" aria-label="Iniciar sesión">
                            <img src="{{ asset('images/nav/cuenta.svg') }}" alt="" width="22" height="22">
                        </a>
                    @endif
                    <button type="button" data-cart-open class="os-focus os-nav-icon relative" aria-label="Carrito de compras, {{ $cartCount }} {{ $cartCount === 1 ? 'producto' : 'productos' }}, total S/{{ number_format($cartTotal, 2) }}">
                        <img src="{{ asset('images/nav/bolsa.svg') }}" alt="" width="22" height="22">
                        @if ($cartCount > 0)
                            <span class="os-nav-count" aria-hidden="true">{{ $cartCount }}</span>
                        @endif
                    </button>
                    <button type="button" data-web-menu-toggle class="os-focus grid h-10 w-10 place-items-center rounded-full text-white transition hover:bg-white/10 xl:hidden" aria-label="Abrir menú" aria-expanded="false" aria-controls="web-menu">
                        <svg class="h-6 w-6" data-menu-icon-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                        <svg class="hidden h-6 w-6" data-menu-icon-close viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <a href="{{ route('web.combos.index') }}" class="os-announce" aria-label="Combos 3 por S/ 50, envío desde S/ 15 a todo el Perú, pago contraentrega. Ver combos">
            <span>Combos 3 × S/ 50</span>
            <span class="hidden sm:inline" aria-hidden="true">·</span>
            <span class="hidden sm:inline">Envío desde S/ 15 a todo el Perú</span>
            <span class="hidden md:inline" aria-hidden="true">·</span>
            <span class="hidden md:inline">Pago contraentrega</span>
        </a>
        @endif

        <div id="search-panel" data-search-panel class="hidden border-t border-edge/60 bg-abyss/95 px-4 py-4 sm:px-6">
            <form action="{{ $storeLine === 'mujer' && ! $brandPageContext ? route('web.girls.products') : route('web.products.search') }}" method="GET" class="mx-auto flex max-w-2xl items-center gap-2" role="search">
                <label for="product-search" class="sr-only">Buscar productos</label>
                <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-4 w-4 text-haze" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                    </div>
                    <input type="text" id="product-search" name="q" value="{{ request('q') }}" class="block w-full rounded-full border border-edge-strong bg-panel py-3 pl-10 pr-4 text-sm font-semibold text-white outline-none placeholder:text-haze focus:border-neon-soft focus:ring-4 focus:ring-neon/25" placeholder="Buscar polos, camisas, pantalones..." required>
                </div>
                <button type="submit" class="btn-primary h-11 shrink-0 px-6">Buscar</button>
            </form>
        </div>

        <nav id="web-menu" data-web-menu class="hidden max-h-[calc(100dvh-3.5rem)] overflow-y-auto border-t border-edge/60 bg-abyss px-4 py-4 text-sm font-semibold sm:px-6 xl:hidden" aria-label="Navegación móvil">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                @foreach ($storeMenu as $item)
                    <a href="{{ $item['url'] }}" class="os-focus rounded-lg px-3 py-3 uppercase tracking-wide hover:bg-white/5 {{ $item['active'] ? 'text-electric-soft' : '' }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ $combosUrl }}" class="os-focus rounded-lg px-3 py-3 uppercase tracking-wide hover:bg-white/5 {{ request()->routeIs('web.combos.index') ? 'text-electric-soft' : '' }}">Combos</a>
                <div>
                    <button type="button" data-brands-toggle data-brands-menu-target="brands-menu-mobile" class="os-focus flex w-full items-center justify-between rounded-lg px-3 py-3 text-left uppercase tracking-wide hover:bg-white/5" aria-expanded="false" aria-controls="brands-menu-mobile">
                        Marcas
                        <svg class="h-4 w-4 text-haze transition-transform" data-brands-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </button>
                    <div id="brands-menu-mobile" data-brands-menu data-state="closed" class="brands-panel-mobile ml-3 border-l border-edge pl-3">
                        @forelse ($menuBrands as $menuBrand)
                            <a href="{{ route('web.brands.show', $menuBrand) }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm text-mist hover:bg-white/5 hover:text-white">
                                <span>{{ $menuBrand->name }}</span>
                                <span class="h-3 w-3 rounded-full border border-edge-strong" style="background-color: {{ $menuBrand->primary_color }}" aria-hidden="true"></span>
                            </a>
                        @empty
                            <p class="px-3 py-3 text-sm text-haze">Aún no hay marcas asignadas.</p>
                        @endforelse
                    </div>
                </div>
                <a href="{{ route('web.home', ['linea' => $otherLine]) }}" class="os-focus rounded-lg px-3 py-3 uppercase tracking-wide hover:bg-white/5">Cambiar a {{ $otherLine }}</a>
                <div class="mt-2 flex flex-col gap-1 rounded-lg border border-edge bg-panel/60 px-3 py-3">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-electric-soft">Mi cuenta</p>
                    @if ($customerAccount)
                        <a href="{{ route('customer.account') }}" class="os-focus rounded-lg px-2 py-2 hover:bg-white/5">Mi cuenta</a>
                        <a href="{{ route('customer.orders') }}" class="os-focus rounded-lg px-2 py-2 hover:bg-white/5">Mis pedidos</a>
                        <a href="{{ route('customer.addresses') }}" class="os-focus rounded-lg px-2 py-2 hover:bg-white/5">Mis direcciones</a>
                        <form method="POST" action="{{ route('customer.logout') }}">
                            @csrf
                            <button type="submit" class="os-focus w-full rounded-lg px-2 py-2 text-left hover:bg-white/5">Cerrar sesión</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="os-focus rounded-lg px-2 py-2 hover:bg-white/5">Iniciar sesión</a>
                        <a href="{{ route('customer.register') }}" class="os-focus rounded-lg px-2 py-2 hover:bg-white/5">Crear cuenta</a>
                        <p class="px-2 text-xs font-medium leading-5 text-mist">También puedes comprar como invitado.</p>
                    @endif
                </div>
            </div>
        </nav>
    </header>

    <main id="contenido" class="@yield('main-class', 'pt-[var(--header-h)]')" tabindex="-1">@yield('content')</main>

    {{-- Carrito lateral: mismo sistema visual que las páginas nuevas (.os-hh); en la línea mujer usa la paleta Girls. --}}
    <div data-cart-overlay class="fixed inset-0 z-[60] hidden bg-[#0b0f14]/55" aria-hidden="true"></div>
    <aside data-cart-drawer data-open="{{ $cartOpen ? 'true' : 'false' }}" @class(['cart-drawer os-hh fixed bottom-0 right-0 top-0 z-[70] flex w-full translate-x-full flex-col shadow-[-12px_0_40px_rgb(11_15_20/18%)] transition-transform duration-300 sm:max-w-[440px]', 'os-hh--girls' => $storeLine === 'mujer']) aria-label="Carrito de compras">
        <div class="flex items-center justify-between gap-4 border-b border-[var(--shop-line)] px-5 py-5 sm:px-6">
            <div class="flex items-baseline gap-3">
                <h2 @class(['leading-none', 'os-josefin text-[26px] uppercase' => $storeLine === 'mujer', 'os-hh-display text-[34px]' => $storeLine !== 'mujer'])>Tu carrito</h2>
                <span class="text-[14px] font-bold text-[#545966]">{{ $cartCount }} {{ $cartCount === 1 ? 'prenda' : 'prendas' }}</span>
            </div>
            <button type="button" data-cart-close class="os-focus grid h-10 w-10 place-items-center text-[#0b0f14] transition hover:bg-[var(--hh-soft)] hover:text-[var(--hh-accent)]" aria-label="Cerrar carrito">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        {{-- Avisos de las rutas del carrito (stock insuficiente, cantidad máxima, talla agotada). --}}
        @if (isset($errors) && $errors->has('cart'))
            <p class="bg-[#fdecec] px-5 py-3 text-[14px] font-bold text-[#a3261c] sm:px-6" role="alert">{{ $errors->first('cart') }}</p>
        @elseif (session('cart_notice'))
            <p class="bg-[#fff6e0] px-5 py-3 text-[14px] font-bold text-[#8a5a00] sm:px-6" role="status">{{ session('cart_notice') }}</p>
        @endif

        @if ($cartItems->isNotEmpty())
            <p class="flex flex-wrap items-center justify-center gap-x-2 bg-[var(--hh-soft)] px-5 py-2.5 text-center text-[12px] font-extrabold uppercase tracking-[1px] text-[var(--hh-accent)]">
                <span>Envío desde S/ 15 a todo el Perú</span><span aria-hidden="true">·</span><span>Pago contraentrega</span>
            </p>
        @endif

        <div class="flex-1 overflow-y-auto px-5 sm:px-6">
            @forelse ($cartItems as $itemKey => $item)
                @php
                    $variantId = $item['variant_id'] ?? 0;
                    $isCombo = ($item['kind'] ?? null) === 'combo';
                    $qty = (int) ($item['qty'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);
                    $nice = fn ($text) => \App\Support\ProductName::title((string) $text);
                @endphp
                <div class="flex gap-4 border-b border-[var(--shop-line)] py-5 last:border-b-0">
                    <div class="h-[120px] w-[96px] shrink-0 overflow-hidden bg-[var(--hh-soft)]">
                        <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="" class="h-full w-full object-cover">
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="min-w-0 text-[15px] font-extrabold leading-snug">{{ $nice($item['producto'] ?? 'Producto') }}</h3>
                            <p class="shrink-0 text-right text-[15px] font-extrabold">S/ {{ number_format($price * $qty, 2) }}</p>
                        </div>
                        @if ($isCombo)
                            <p class="mt-1 text-[11px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">Combo</p>
                            @foreach (collect($item['selections'] ?? [])->take(3) as $selection)
                                <p class="mt-0.5 truncate text-[13px] font-medium text-[#545966]">{{ $nice($selection['producto'] ?? 'Producto') }} · {{ $selection['talla'] ?? '-' }} · {{ $nice($selection['color'] ?? '-') }}</p>
                            @endforeach
                        @else
                            <p class="mt-1 text-[13px] font-medium text-[#545966]">{{ $nice($item['color'] ?? '-') }} · Talla {{ $item['talla'] ?? '-' }}</p>
                            @if ($qty > 1)
                                <p class="text-[12px] font-medium text-[#888]">S/ {{ number_format($price, 2) }} c/u</p>
                            @endif
                        @endif

                        <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                            @if ($isCombo)
                                <span class="text-[13px] font-bold text-[#545966]">{{ $qty }} combo{{ $qty === 1 ? '' : 's' }}</span>
                            @else
                                <div class="flex h-9 items-center border border-[var(--shop-check-line)]">
                                    <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="decrement">
                                        <button type="submit" class="os-focus grid h-9 w-9 place-items-center text-[18px] font-bold transition hover:bg-[var(--hh-soft)] hover:text-[var(--hh-accent)]" aria-label="Disminuir cantidad">−</button>
                                    </form>
                                    <span class="w-8 text-center text-[14px] font-extrabold" aria-label="Cantidad">{{ $qty }}</span>
                                    <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="increment">
                                        <button type="submit" class="os-focus grid h-9 w-9 place-items-center text-[18px] font-bold transition hover:bg-[var(--hh-soft)] hover:text-[var(--hh-accent)]" aria-label="Aumentar cantidad">+</button>
                                    </form>
                                </div>
                            @endif

                            <form method="POST" action="{{ $isCombo ? route('web.cart.combo.destroy', $itemKey) : route('web.cart.destroy', $variantId) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="os-focus text-[13px] font-bold text-[#545966] underline-offset-4 transition hover:text-[#c0392b] hover:underline" aria-label="Eliminar {{ $nice($item['producto'] ?? 'producto') }}">Quitar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center gap-3 py-16 text-center">
                    <span class="grid h-16 w-16 place-items-center bg-[var(--hh-soft)] text-[var(--hh-accent)]">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    </span>
                    <p class="text-[18px] font-extrabold">Tu carrito está vacío</p>
                    <p class="max-w-[280px] text-[14px] font-medium text-[#545966]">Agrega tus prendas favoritas y aparecerán aquí.</p>
                    <a href="{{ route('web.products.search') }}" class="os-hh-btn os-focus mt-2 bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Ir a la tienda →</a>
                </div>
            @endforelse
        </div>

        @if ($cartItems->isNotEmpty())
            <div class="border-t border-[var(--shop-line)] px-5 pb-5 pt-4 sm:px-6">
                <a href="{{ $combosUrl }}" class="os-focus mb-4 flex items-center justify-between gap-3 border border-dashed border-[var(--shop-check-line)] px-4 py-3 transition hover:border-[var(--hh-accent)]">
                    <span class="flex flex-col">
                        <span class="text-[14px] font-extrabold">¡Ahorra más comprando en pack!</span>
                        <span class="text-[12px] font-medium text-[#545966]">Mira los combos con mejor precio.</span>
                    </span>
                    <span class="shrink-0 text-[13px] font-extrabold text-[var(--hh-accent)]">Ver combos →</span>
                </a>
                <div class="flex items-baseline justify-between">
                    <span class="text-[16px] font-extrabold">Subtotal</span>
                    <span class="text-[24px] font-extrabold">S/ {{ number_format($cartTotal, 2) }}</span>
                </div>
                <p class="mt-1 text-[12px] font-medium text-[#545966]">El envío se calcula al finalizar la compra.</p>
                <a href="{{ route('web.cart.index') }}" class="os-hh-btn os-focus mt-4 w-full gap-2 bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    Comprar ahora
                </a>
                <button type="button" data-cart-close class="os-focus mt-3 w-full py-2 text-[13px] font-extrabold uppercase tracking-[1.2px] text-[#0b0f14] underline-offset-4 hover:text-[var(--hh-accent)] hover:underline">Seguir comprando</button>
            </div>
        @endif
    </aside>

    @if ($suggestAdded && $suggestions->isNotEmpty())
        @include('web.partials.cart-suggestions-modal', ['added' => $suggestAdded, 'suggestions' => $suggestions, 'suggestionTitle' => $suggestionTitle ?? null])
    @endif

    {{-- Una página puede traer su propio footer (p. ej. la home de Overshark Girls). --}}
    @hasSection('footer')
        @yield('footer')
    @else
    {{-- Pie (diseño Figma "Footer" · Overshark / Overshark Girls): fondo blanco, solo cambia el logo y el gris de los íconos. --}}
    @php
        $footerGirls = $storeLine === 'mujer' && ! $brandPageContext;
        $footerLogo = $brandPageContext?->logoUrl() ?: asset('images/home-hombre/'.($footerGirls ? 'logo-girls.png' : 'logo-overshark.png'));
        $footerBravos = $menuBrands->first(fn ($menuBrand) => str_contains(mb_strtoupper($menuBrand->name), 'BRAVO'));
        $footerFaq = route('web.contact').'#faq';
        $footerCommunity = \App\Support\AboutPage::content()['comunidad'];
        $footerSocials = collect([
            ['Facebook', 'facebook', $footerCommunity['facebook'] ?? null],
            ['TikTok', 'tiktok', $footerCommunity['tiktok'] ?? null],
            ['Instagram', 'instagram', $footerCommunity['instagram'] ?? null],
            ['WhatsApp', 'whatsapp', $brand->whatsappUrl()],
        ])->filter(fn (array $social) => filled($social[2]));
        $footerLink = 'transition hover:text-[var(--ft-accent)]';
    @endphp
    <span id="nosotros" aria-hidden="true"></span>
    <footer id="contacto" @class(['os-footer', 'os-footer--girls' => $footerGirls])>
        <div class="flex justify-center border-b border-[#e5e7eb] py-3">
            <a href="{{ $homeUrl }}" class="os-focus flex h-16 w-[216px] items-center justify-center" aria-label="{{ $brandPageContext?->name ?: ($footerGirls ? 'Overshark Girls' : 'Overshark') }}, inicio">
                <img src="{{ $footerLogo }}" alt="" class="max-h-[42px] max-w-[190px] object-contain">
            </a>
        </div>

        <div class="mx-auto grid max-w-[1094px] grid-cols-2 gap-x-4 gap-y-10 px-4 pb-10 pt-12 sm:gap-x-6 sm:px-6 lg:grid-cols-4 lg:gap-x-16">
            <section class="flex flex-col gap-4">
                <h2 class="text-[16px] font-medium">Comprar</h2>
                <nav class="flex flex-col gap-3 text-[14px] leading-6" aria-label="Comprar">
                    <a href="{{ route('web.home', ['linea' => 'hombre']) }}" class="{{ $footerLink }}">Overshark</a>
                    <a href="{{ $footerBravos ? route('web.brands.show', $footerBravos) : route('web.products.search', ['q' => 'bravos']) }}" class="{{ $footerLink }}">Bravos</a>
                    <a href="{{ route('web.home', ['linea' => 'mujer']) }}" class="{{ $footerLink }}">Overshark Girls</a>
                    <a href="{{ $combosUrl }}" class="{{ $footerLink }}">Combos</a>
                </nav>
            </section>

            <section class="flex flex-col gap-4">
                <h2 class="text-[16px] font-medium">Ayuda</h2>
                <nav class="flex flex-col gap-3 text-[14px] leading-6" aria-label="Ayuda">
                    <a href="{{ $footerFaq }}" class="{{ $footerLink }}">Preguntas frecuentes</a>
                    <a href="{{ $footerFaq }}" class="{{ $footerLink }}">Envíos</a>
                    <a href="{{ $footerFaq }}" class="{{ $footerLink }}">Cambios y devoluciones</a>
                    <a href="{{ $footerFaq }}" class="{{ $footerLink }}">Métodos de pago</a>
                    <a href="{{ route('web.claims.create') }}" class="{{ $footerLink }}">Libro de Reclamaciones</a>
                </nav>
            </section>

            <section class="flex flex-col gap-4 text-[14px] leading-6">
                <h2 class="text-[16px] font-medium">Contacto</h2>
                @if ($whatsappNumber = $brand->whatsappDisplay())
                    <p class="flex flex-col gap-0.5"><span class="text-[var(--ft-muted)]">WhatsApp:</span> <a href="{{ $brand->whatsappUrl() }}" target="_blank" rel="noopener" class="{{ $footerLink }}">{{ $whatsappNumber }}</a></p>
                @endif
                <p class="flex flex-col gap-0.5"><span class="text-[var(--ft-muted)]">Correo:</span> <a href="mailto:ventas@overshark.com" class="{{ $footerLink }} whitespace-nowrap text-[13px] sm:text-[14px]">ventas@overshark.com</a></p>
                <p class="flex flex-col gap-0.5"><span class="text-[var(--ft-muted)]">Horario de atención:</span> 9am a 6pm L-V</p>
            </section>

            <section class="flex flex-col items-start gap-3">
                <h2 class="text-[16px] font-medium">Envíos a todo el Perú</h2>
                <p class="text-[14px] leading-6 text-[var(--ft-muted)]">Lima: delivery S/ 15</p>
                <p class="text-[14px] leading-6 text-[var(--ft-muted)]">Agencia Shalom: S/ 30</p>
                <p class="rounded-[6px] bg-[#e9f7f9] px-2.5 py-1 text-[14px] font-medium leading-[18px] text-[#1c7696]">Recojo en almacén gratis</p>
                <a href="{{ route('web.claims.create') }}" class="os-focus mt-2 block transition hover:opacity-80" aria-label="Libro de Reclamaciones">
                    <img src="{{ asset('images/iconos/libro_reclamaciones.svg') }}" alt="" class="h-[83px] w-[121px] object-contain">
                </a>
            </section>
        </div>

        <div class="border-t border-[#e5e7eb]">
            <div class="mx-auto flex max-w-[1440px] flex-col items-center gap-4 px-6 py-5 text-[14px] leading-6 text-[var(--ft-muted)] sm:grid sm:grid-cols-[1fr_auto_1fr] lg:px-[100px]">
                <div class="flex items-center gap-2 sm:justify-self-start">
                    @foreach ($footerSocials as [$label, $icon, $url])
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="os-focus grid h-11 w-11 place-items-center rounded-full transition hover:bg-[#f2f4f7]" aria-label="{{ $label }}">
                            <img src="{{ asset('images/footer/'.$icon.($footerGirls ? '-girls' : '').'.svg') }}" alt="" width="30" height="30">
                        </a>
                    @endforeach
                </div>
                <p class="text-center">© {{ date('Y') }} Overshark. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    @endif
    {{-- Botón flotante de WhatsApp (número y mensaje en Admin → Identidad visual). --}}
    @if ($whatsappUrl = $brand->whatsappUrl())
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="os-wa os-focus" aria-label="Escríbenos por WhatsApp">
            <svg viewBox="0 0 32 32" width="30" height="30" fill="currentColor" aria-hidden="true"><path d="M16.04 3C8.85 3 3 8.82 3 15.98c0 2.29.6 4.52 1.75 6.49L3 29l6.7-1.75a13.1 13.1 0 0 0 6.34 1.62h.01c7.19 0 13.04-5.82 13.04-12.98C29.09 8.82 23.24 3 16.04 3Zm0 23.68h-.01a10.9 10.9 0 0 1-5.53-1.51l-.4-.24-3.98 1.04 1.06-3.86-.26-.4a10.7 10.7 0 0 1-1.66-5.73c0-5.95 4.87-10.79 10.79-10.79 5.93 0 10.77 4.84 10.77 10.79 0 5.95-4.84 10.7-10.78 10.7Zm5.91-8.05c-.32-.16-1.92-.94-2.21-1.05-.3-.11-.51-.16-.73.16-.21.32-.83 1.05-1.02 1.27-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.6-.96-.85-1.6-1.9-1.79-2.22-.19-.32-.02-.49.14-.65.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.73-1.75-1-2.4-.26-.63-.53-.54-.73-.55h-.62c-.21 0-.56.08-.86.4-.3.32-1.13 1.1-1.13 2.69 0 1.58 1.16 3.11 1.32 3.33.16.21 2.28 3.47 5.52 4.87.77.33 1.37.53 1.84.68.77.24 1.48.21 2.03.13.62-.09 1.92-.78 2.19-1.54.27-.75.27-1.4.19-1.54-.08-.13-.29-.21-.62-.37Z"/></svg>
        </a>
    @endif
    @yield('scripts')
</body>
</html>
