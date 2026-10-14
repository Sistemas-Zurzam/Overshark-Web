{{-- Header de Overshark Girls (home mujer y catálogo mujer). Va dentro de @section('header-bar'). --}}
@php
    $cartCount = collect(session('cart.items', []))->sum('qty');
    $catalogUrl = route('web.girls.products');
    $onGirlsHome = request()->routeIs('web.home');
@endphp
{{-- Iconos reutilizados en toda la página (<use href="#gl-i-...">). --}}
<svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
    <symbol id="gl-i-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="gl-i-search" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></g></symbol>
    <symbol id="gl-i-user" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="7.5" r="3.75"/><path d="M4.75 20.5c.5-4.5 3.2-7 7.25-7s6.75 2.5 7.25 7"/></g></symbol>
    <symbol id="gl-i-bag" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M5 8h14l-1 12H6Z"/><path d="M9 9V7a3 3 0 0 1 6 0v2"/></g></symbol>
    <symbol id="gl-i-flame" viewBox="0 0 24 24"><path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z" fill="currentColor"/></symbol>
    <symbol id="gl-i-video" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="13" height="12" rx="2.5"/><path d="m16 10 5-3v10l-5-3"/></g></symbol>
    <symbol id="gl-i-headset" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/></g></symbol>
    <symbol id="gl-i-userai" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="8.5" r="3.5"/><path d="M3.5 20.5c.6-4 3.1-6 6.5-6s5.9 2 6.5 6"/><path d="m18.5 2.5.8 1.9 1.9.8-1.9.8-.8 1.9-.8-1.9-1.9-.8 1.9-.8z"/></g></symbol>
    <symbol id="gl-i-drop" viewBox="0 0 24 24"><path d="M12 3s6 6.4 6 11a6 6 0 0 1-12 0c0-4.6 6-11 6-11Z" fill="none" stroke="currentColor" stroke-width="1.6"/></symbol>
    <symbol id="gl-i-shirt" viewBox="0 0 24 24"><path d="M9 3 4 5.5 2.5 10l3 1.2V21h13v-9.8l3-1.2L20 5.5 15 3a3 3 0 0 1-6 0Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></symbol>
    <symbol id="gl-i-spark" viewBox="0 0 24 24"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></symbol>
    <symbol id="gl-i-truck" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M2.5 6h11v10h-11zM13.5 9.5h4l3 3.5V16h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></g></symbol>
</svg>

<div class="gl-hdr__seg-row">
    <nav class="gl-seg" aria-label="Colección">
        <a href="{{ route('web.home', ['linea' => 'hombre']) }}" class="gl-seg__item">Hombre</a>
        <a href="{{ route('web.home', ['linea' => 'mujer']) }}" class="gl-seg__item is-active" aria-current="page">Mujer</a>
    </nav>
</div>
<div class="gl-wrap gl-hdr__bar">
    <nav class="gl-nav" aria-label="Principal">
        <button type="button" class="gl-combos" data-combos-toggle aria-expanded="false" aria-controls="combos-menu">
            <svg class="gl-i" aria-hidden="true"><use href="#gl-i-flame"/></svg>Combos
        </button>
        <a class="gl-nav__link" href="{{ $catalogUrl }}" @if (request()->routeIs('web.girls.products')) aria-current="page" @endif>Productos</a>
        <a class="gl-nav__link" href="{{ $onGirlsHome ? '' : route('web.home', ['linea' => 'mujer']) }}#nosotros">Nosotros</a>
    </nav>
    <a class="gl-brand" href="{{ route('web.explore') }}" aria-label="Overshark Girls, cambiar de colección">
        <img src="{{ asset('images/girls/logo-girls.png') }}" alt="" width="160" height="46">
    </a>
    <div class="gl-tools">
        <button type="button" data-search-toggle aria-label="Buscar productos" aria-expanded="false" aria-controls="search-panel"><svg class="gl-i" aria-hidden="true"><use href="#gl-i-search"/></svg></button>
        <div class="relative">
            <button type="button" data-disclosure-toggle="gl-account" aria-label="Mi cuenta" aria-expanded="false" aria-controls="gl-account"><svg class="gl-i" aria-hidden="true"><use href="#gl-i-user"/></svg></button>
            <div id="gl-account" class="gl-popover hidden">
                <p class="gl-popover__title">Mi cuenta · Próximamente</p>
                <p>Las cuentas de cliente aún no están disponibles. Puedes comprar como invitada.</p>
            </div>
        </div>
        <button type="button" data-cart-open aria-label="Carrito de compras, {{ $cartCount }} {{ $cartCount === 1 ? 'producto' : 'productos' }}">
            <svg class="gl-i" aria-hidden="true"><use href="#gl-i-bag"/></svg>
            @if ($cartCount > 0)
                <span class="gl-cart-count" aria-hidden="true">{{ $cartCount }}</span>
            @endif
        </button>
    </div>
</div>
