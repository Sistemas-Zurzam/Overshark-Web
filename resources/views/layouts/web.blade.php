@php
    $brand = \App\Models\BrandSetting::current();
    $menuCombos = \App\Models\Admin\Combo::activeForMenu();
    $cartItems = collect(session('cart.items', []));
    $cartCount = $cartItems->sum('qty');
    $cartTotal = $cartItems->sum(fn ($item) => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0)));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Overshark, tienda online">
    <title>@yield('title', 'Overshark')</title>
    @if ($brand->iconUrl())
        <link rel="icon" href="{{ $brand->iconUrl() }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200 bg-white/95 text-slate-950 shadow-sm backdrop-blur">
        <div class="relative mx-auto flex h-[76px] max-w-[1440px] items-center justify-between px-3 sm:h-[84px] sm:px-5 lg:px-12">
            <div class="flex items-center gap-8">
                <button type="button" data-combos-toggle class="combo-trigger group hidden text-base font-semibold sm:flex" aria-expanded="false" aria-controls="combos-menu">
                    <img data-combo-flame class="combo-fire-icon" src="{{ asset('images/iconos/fuego_combo.svg') }}" alt="" aria-hidden="true">
                    Combos
                </button>

                <nav class="hidden items-center gap-8 text-base font-medium lg:flex" aria-label="Navegación principal">
                    <a href="#productos" class="flex items-center gap-1.5 transition hover:text-cyan-600">
                        Productos
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </a>
                    <a href="#categorias" class="transition hover:text-cyan-600">Categorías</a>
                    <a href="#contacto" class="transition hover:text-cyan-600">Nosotros</a>
                </nav>
            </div>

            <a href="{{ route('web.home') }}" class="absolute left-1/2 flex h-[70px] w-40 -translate-x-1/2 items-center justify-center overflow-hidden text-xl font-black tracking-normal sm:h-[76px] sm:w-56 sm:text-2xl lg:w-72 lg:text-3xl" aria-label="Overshark inicio">
                @if ($brand->logoUrl())
                    <img src="{{ $brand->logoUrl() }}" alt="Overshark" class="h-14 w-full object-contain sm:h-16 sm:scale-125 lg:h-20 lg:scale-150">
                @else
                    OVER<span class="text-cyan-600">SHARK</span>
                @endif
            </a>

            <div class="flex items-center gap-0.5 sm:gap-3">
                <form action="{{ route('web.products.search') }}" method="GET" class="hidden w-64 items-center gap-2 xl:flex">
                    <label for="product-search" class="sr-only">Buscar productos</label>
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8v8m0-8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8-8a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 0a4 4 0 0 1-4 4h-1a3 3 0 0 0-3 3"/></svg>
                        </div>
                        <input type="text" id="product-search" name="q" value="{{ request('q') }}" class="block w-full rounded-lg border border-slate-300 bg-[#F7F7F7] py-2.5 pl-9 pr-3 text-sm font-semibold text-slate-950 outline-none placeholder:text-slate-500 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100" placeholder="Buscar producto..." required>
                    </div>
                    <button type="submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-white shadow-sm transition hover:bg-cyan-600 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                        <span class="sr-only">Buscar</span>
                    </button>
                </form>
                <a href="{{ route('web.products.search') }}" class="grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 xl:hidden" aria-label="Buscar productos">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="hidden h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 sm:grid" aria-label="Mi cuenta">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="7" r="3.5"/><path d="M5.5 21c.4-5 2.6-7.5 6.5-7.5s6.1 2.5 6.5 7.5"/></svg>
                </a>
                <button type="button" data-cart-open class="relative grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100" aria-label="Bolsa de compras">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    @if ($cartCount > 0)
                        <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-red-600 px-1 text-[11px] font-black leading-none text-white">{{ $cartCount }}</span>
                    @endif
                </button>
                <button type="button" data-web-menu-toggle class="grid h-10 w-10 place-items-center rounded-full transition hover:bg-slate-100 lg:hidden" aria-label="Abrir menú" aria-expanded="false">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div id="combos-menu" data-combos-menu data-state="closed" class="combos-panel absolute left-0 right-0 top-full border-t border-slate-100 bg-white shadow-2xl">
            <div class="mx-auto grid max-w-[1440px] gap-4 px-5 py-6 sm:grid-cols-2 lg:grid-cols-3 lg:px-12">
                @forelse ($menuCombos as $combo)
                    <a href="{{ $combo->url }}" class="combo-card group relative min-h-48 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                        <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/25 to-transparent"></div>
                        <div class="relative flex h-full min-h-48 items-end p-5">
                            <div>
                                <span class="text-xs font-black uppercase tracking-[0.18em] text-cyan-300">Ver combo</span>
                                <h2 class="mt-1 text-xl font-black text-white">{{ $combo->name }}</h2>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center text-sm font-semibold text-slate-500">
                        Aún no hay combos activos.
                    </div>
                @endforelse
            </div>
        </div>

        <nav data-web-menu class="hidden border-t border-slate-100 bg-white px-5 py-4 text-sm font-semibold lg:hidden" aria-label="Navegación móvil">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <button type="button" data-combos-toggle class="flex items-center justify-between rounded-lg px-3 py-3 text-left hover:bg-slate-100" aria-expanded="false" aria-controls="combos-menu">
                    Combos y productos
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                </button>
                <a href="#categorias" class="rounded-lg px-3 py-3 hover:bg-slate-100">Categorías</a>
                <a href="#contacto" class="rounded-lg px-3 py-3 hover:bg-slate-100">Nosotros</a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-3 hover:bg-slate-100">Mi cuenta</a>
            </div>
        </nav>
    </header>

    <main class="pt-[76px] sm:pt-[84px]">@yield('content')</main>

    <div data-cart-overlay class="fixed inset-0 z-[60] hidden bg-black/45" aria-hidden="true"></div>
    <aside data-cart-drawer data-open="{{ session('cart_open') ? 'true' : 'false' }}" class="cart-drawer fixed bottom-0 right-0 top-0 z-[70] flex w-full max-w-full translate-x-full flex-col bg-white text-slate-950 shadow-2xl transition-transform duration-300 sm:max-w-[430px]" aria-label="Carrito de compras">
        <div class="flex h-[76px] items-center justify-between border-b border-slate-100 px-5 sm:h-[84px] sm:px-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-cyan-700">Tu carrito</p>
                <h2 class="text-xl font-black">{{ $cartCount }} producto{{ $cartCount === 1 ? '' : 's' }}</h2>
            </div>
            <button type="button" data-cart-close class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 transition hover:border-slate-950" aria-label="Cerrar carrito">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5 sm:px-6">
            @forelse ($cartItems as $item)
                @php
                    $variantId = $item['variant_id'] ?? 0;
                    $qty = (int) ($item['qty'] ?? 1);
                    $price = (float) ($item['price'] ?? 0);
                    $oldPrice = $price > 0 ? $price / 0.8 : 0;
                @endphp
                <div class="grid grid-cols-[76px_1fr] gap-3 border-b border-blue-500 py-4 last:border-b-0 sm:grid-cols-[84px_1fr] sm:gap-4">
                    <div class="h-[110px] w-[76px] overflow-hidden rounded-lg bg-slate-50 sm:h-[122px] sm:w-[84px]">
                        <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain object-center">
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-black">{{ $item['producto'] ?? 'Producto' }}</h3>
                                <p class="mt-1 text-sm text-slate-600">Color: {{ $item['color'] ?? '-' }}</p>
                                <p class="mt-1 text-sm text-slate-600">Talla: {{ $item['talla'] ?? '-' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-red-600">S/ {{ number_format($price, 2) }}</p>
                                @if ($oldPrice > 0)
                                    <p class="mt-1 text-xs text-slate-400 line-through">S/ {{ number_format($oldPrice, 2) }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="grid w-28 grid-cols-3 overflow-hidden rounded-md border border-slate-200">
                                <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="increment">
                                    <button type="submit" class="grid h-9 w-full place-items-center text-xl font-bold text-slate-500 transition hover:bg-slate-100">+</button>
                                </form>
                                <span class="grid h-9 place-items-center text-sm text-slate-500">{{ $qty }}</span>
                                <form method="POST" action="{{ route('web.cart.update', $variantId) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="decrement">
                                    <button type="submit" class="grid h-9 w-full place-items-center text-xl font-bold text-slate-500 transition hover:bg-slate-100">-</button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('web.cart.destroy', $variantId) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="grid h-9 w-9 place-items-center rounded-md text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Eliminar producto">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 7h14"/><path d="M10 11v6M14 11v6"/><path d="M8 7l1-3h6l1 3"/><path d="M6.5 7 8 21h8l1.5-14"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <div class="grid h-16 w-16 place-items-center rounded-full bg-slate-100">
                        <svg class="h-8 w-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M5 8h14l1 13H4Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    </div>
                    <p class="mt-4 text-lg font-black">Tu carrito esta vacio</p>
                    <p class="mt-1 text-sm text-slate-500">Agrega productos para verlos aqui.</p>
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-100 bg-white px-4 py-5 sm:px-6">
            @if ($cartItems->isNotEmpty())
                <div class="mb-5 rounded-2xl bg-white px-4 py-4 shadow-[0_12px_35px_rgba(17,17,17,0.08)]">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-red-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/></svg>
                        <div>
                            <p class="text-sm font-black">Ahorra mas comprando en pack!</p>
                            <p class="mt-1 text-xs text-slate-500">Agrega un producto adicional y obten mejor precio.</p>
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

    <footer id="contacto" class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50 text-slate-900">
        <div class="border-b border-slate-200 px-5 py-7">
            <a href="{{ route('web.home') }}" class="mx-auto flex w-fit items-center justify-center" aria-label="Overshark inicio">
                @if ($brand->logoUrl())
                    <img src="{{ $brand->logoUrl() }}" alt="Overshark" class="h-16 w-56 object-contain sm:w-64">
                @else
                    <span class="text-3xl font-black tracking-[-0.07em]">OVER<span class="text-cyan-600">SHARK</span></span>
                @endif
            </a>
        </div>

        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <section>
                <h2 class="text-lg font-black">Comprar</h2>
                <nav class="mt-5 flex flex-col gap-3 text-sm text-slate-600">
                    <a href="#productos" class="transition hover:text-cyan-700">Polos</a>
                    <a href="#productos" class="transition hover:text-cyan-700">Medias</a>
                    <a href="#combos" class="flex items-center gap-2 transition hover:text-cyan-700">
                        Combos
                        <img src="{{ asset('images/iconos/Fire.svg') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">
                    </a>
                    <a href="#productos" class="flex items-center gap-2 transition hover:text-cyan-700">
                        Para ellas
                        <img src="{{ asset('images/iconos/Heart.svg') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">
                    </a>
                </nav>
            </section>

            <section>
                <h2 class="text-lg font-black">Ayuda</h2>
                <nav class="mt-5 flex flex-col gap-3 text-sm text-slate-600">
                    <a href="#contacto" class="transition hover:text-cyan-700">Envíos</a>
                    <a href="#contacto" class="transition hover:text-cyan-700">Cambios y devoluciones</a>
                    <a href="#contacto" class="transition hover:text-cyan-700">Métodos de pago</a>
                </nav>
            </section>

            <section>
                <h2 class="text-lg font-black">Contacto</h2>
                <div class="mt-5 space-y-5 text-sm leading-6 text-slate-600">
                    <p><span class="block font-bold text-slate-800">Correo:</span> ventas@overshark.com</p>
                    <p><span class="block font-bold text-slate-800">Horario de atención:</span> 9am a 6pm L-V</p>
                </div>
            </section>

            <section>
                <h2 class="text-lg font-black">Envíos a todo el Perú</h2>
                <div class="mt-5 space-y-2 text-sm text-slate-500">
                    <p>Lima: delivery o agencia</p>
                    <p>Provincia: agencia</p>
                    <p class="mt-3 w-fit rounded-full bg-blue-100 px-4 py-1.5 font-bold text-blue-700">S/14 costo único</p>
                </div>
                <a href="{{ route('web.claims.create') }}" class="mt-5 block w-fit transition hover:opacity-80" aria-label="Abrir libro de reclamaciones">
                    <img src="{{ asset('images/iconos/libro_reclamaciones.svg') }}" alt="Libro de reclamaciones" class="h-auto w-44">
                </a>
            </section>
        </div>

        <div class="border-t border-slate-200">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-6 py-6 text-sm text-slate-500 sm:flex-row lg:px-8">
                <div class="flex items-center gap-4">
                    @foreach ([
                        ['Facebook', 'facebook.svg'],
                        ['Instagram', 'instagram.svg'],
                        ['TikTok', 'tiktok.svg'],
                        ['WhatsApp', 'whatsapp.svg'],
                    ] as [$social, $icon])
                        <a href="#" class="grid h-9 w-9 place-items-center rounded-full bg-slate-600 text-white transition hover:-translate-y-0.5 hover:bg-cyan-600" aria-label="{{ $social }}">
                            <img src="{{ asset('images/iconos/'.$icon) }}" alt="" class="h-5 w-5 object-contain brightness-0 invert" aria-hidden="true">
                        </a>
                    @endforeach
                </div>
                <p>© {{ date('Y') }} Overshark. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>
