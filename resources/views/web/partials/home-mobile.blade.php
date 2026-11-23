{{--
    Inicio Hombre en celular (Figma "02 · MOBILE" → "01 · Inicio / Mobile / Overshark", nodo 304:25665).
    Solo se ve por debajo de md; desde tablet se usa la portada de escritorio (home.blade.php + home-sections).
    Textos del hero y categorías: los de Admin → Contenido de la tienda (hero.hombre.*). Productos y combos: de la tienda.
    $showHero: false cuando el admin cargó banners propios (en ese caso el carrusel de banners hace de hero).
--}}
@php
    $mContent = fn (string $path, string $fallback) => \App\Support\SiteContent::get($path) ?: $fallback;
    $mOcean = fn (string $file) => \App\Support\SiteContent::asset('images/hero-oceano/'.$file);
    $mModelsUploaded = \App\Support\SiteContent::asset('images/home/hero-model-hombre.webp');
    $mModels = str_contains($mModelsUploaded, '/storage/') ? $mModelsUploaded : $mOcean('modelos-azul-vino.webp');
    $mCatalogUrl = route('web.products.search', ['orden' => 'populares']);
    $mCta = [$mContent('hero.hombre.cta1_text', 'Ver colección'), $mContent('hero.hombre.cta1_url', $mCatalogUrl)];
    $mCategories = collect(explode(',', (string) $mContent('hero.hombre.categories', 'Polos, Poleras, Camisas, Camiseros, Pantalones')))
        ->map(fn (string $name) => trim($name))->filter()->take(8)
        ->map(fn (string $name) => [$name, route('web.products.search', ['categoria' => \Illuminate\Support\Str::slug($name)])]);
    // Carrusel «Combos Hombre»: los combos con flyer propio (los de la campaña); cada uno con su foto panorámica
    // del diseño si existe (public/images/combos/oceano/<nombre>.webp) y si no, su flyer.
    $mCombos = \App\Support\ComboCatalog::forStore('Overshark');
    $mCombos = ($mCombos->whereNotNull('flyer')->isNotEmpty() ? $mCombos->whereNotNull('flyer') : $mCombos)->values()->take(12)
        ->map(function (array $combo) {
            $panorama = 'images/combos/oceano/'.\Illuminate\Support\Str::slug($combo['title']).'.webp';
            $combo['photo'] = file_exists(public_path($panorama)) ? asset($panorama) : ($combo['flyer'] ?? $combo['images'][0] ?? null);
            $combo['panorama'] = file_exists(public_path($panorama));

            return $combo;
        });
    $mMoney = fn (float $amount) => 'S/ '.(floor($amount) === $amount ? number_format($amount, 0) : number_format($amount, 2));
@endphp

<div class="os-m md:hidden">
    @if ($showHero ?? true)
        {{-- Hero (Figma "Hero / Mobile / Overshark"): fondo submarino, modelos arriba y el texto sobre un degradado. --}}
        <section class="os-m-hero" aria-labelledby="m-hero-title">
            <img src="{{ $mOcean('fondo.webp') }}" alt="" class="os-m-hero__bg" fetchpriority="high" aria-hidden="true">
            <img src="{{ $mModels }}" alt="Modelos con polo clásico denim y camisa waffle vino de la colección hombre" class="os-m-hero__models" width="1536" height="1024">
            <span class="os-m-hero__shade" aria-hidden="true"></span>
            <div class="os-m-hero__copy">
                <p class="os-m-hero__lead">{{ $mContent('hero.hombre.copy', 'Prendas esenciales con carácter, precisión y fuerza.') }}</p>
                <h1 id="m-hero-title" class="os-m-hero__title">
                    <span class="block">{{ $mContent('hero.hombre.title', 'Del océano.') }}</span>
                    <span class="block text-[#4aa8ff]">{{ $mContent('hero.hombre.accent', 'Con carácter.') }}</span>
                </h1>
                <a href="{{ $mCta[1] }}" class="os-m-btn os-m-btn--sky os-focus">{{ $mCta[0] }} →</a>
            </div>
        </section>
    @endif

    {{-- Encuentra tu estilo: categorías en botones de 2 columnas --}}
    @if ($mCategories->isNotEmpty())
        <section class="os-m-section gap-3" aria-labelledby="m-cat">
            <h2 id="m-cat" class="os-m-title">Encuentra tu estilo</h2>
            <ul class="grid w-full grid-cols-2 gap-3">
                @foreach ($mCategories as [$categoryLabel, $categoryUrl])
                    <li><a href="{{ $categoryUrl }}" class="os-m-btn os-m-btn--soft os-focus">{{ $categoryLabel }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Ofertas relámpago (solo si hay promociones por horas vigentes; panel → Promociones) --}}
    @if (! empty($flashDeals) && $flashDeals->isNotEmpty())
        <section class="os-m-section gap-5 pb-9 pt-7" aria-labelledby="m-flash">
            <div class="flex flex-col gap-1">
                <h2 id="m-flash" class="os-m-title">Ofertas relámpago</h2>
                <p class="text-[13px] font-bold">
                    @include('web.partials.promo-countdown', ['promo' => ['ends_at' => $flashDeals->map(fn ($product) => $product->promo['ends_at'])->min()], 'prefix' => 'Termina en ', 'class' => 'font-extrabold text-[#b42318]'])
                </p>
            </div>
            <ul class="os-m-grid">
                @foreach ($flashDeals->take(4) as $product)
                    <li>@include('web.partials.m-product-card')</li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Los más vendidos: 4 prendas en 2 columnas + «Ver toda la tienda» --}}
    @if ($bestSellers->isNotEmpty())
        <section class="os-m-section gap-5 pb-9 pt-7" aria-labelledby="m-top">
            <h2 id="m-top" class="os-m-title">Los más vendidos</h2>
            <ul class="os-m-grid">
                @foreach ($bestSellers->take(4) as $product)
                    <li>@include('web.partials.m-product-card')</li>
                @endforeach
            </ul>
            <a href="{{ $mCatalogUrl }}" class="os-m-btn os-m-btn--dark os-focus">Ver toda la tienda</a>
        </section>
    @endif

    {{-- Combos Hombre (Figma "Combos / Carrusel mobile / h"): una tarjeta por combo, se desliza con el dedo o las flechas. --}}
    @if ($mCombos->isNotEmpty())
        <section class="os-m-section gap-3 bg-[#f0f7ff]" aria-labelledby="m-combos" data-m-carousel>
            <h2 id="m-combos" class="os-m-title">Combos hombre</h2>
            <div class="os-m-combos">
                <ul class="os-m-combos__track" data-m-track>
                    @foreach ($mCombos as $combo)
                        <li class="os-m-combo" aria-roledescription="diapositiva" aria-label="{{ $loop->iteration }} de {{ $mCombos->count() }}: {{ $combo['title'] }}">
                            @if ($combo['photo'])
                                <img src="{{ $combo['photo'] }}" alt="" class="os-m-combo__photo {{ $combo['panorama'] ? 'object-cover' : 'object-contain' }}" loading="lazy" decoding="async">
                            @endif
                            <div class="os-m-combo__copy">
                                <p class="text-[10px] leading-3">Overshark <span class="text-[#368fff]">Hombre</span></p>
                                <p class="os-m-combo__label">{{ \Illuminate\Support\Str::upper($combo['title']) }}</p>
                                <p class="text-[19px] leading-[23px]">{{ $combo['quantity'] }} prendas ×</p>
                                <p class="text-[37px] leading-[39px]">{{ $mMoney($combo['price']) }}</p>
                                <p class="normal-case font-normal text-[10px] leading-3">Elige tus colores y tallas.</p>
                                <a href="{{ $combo['url'] }}" class="os-m-combo__cta os-focus">Arma tu combo →</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="os-m-combos__nav">
                    <button type="button" class="os-m-combos__arrow os-focus" data-m-prev aria-label="Combo anterior">
                        <img src="{{ asset('images/nav/mobile/anterior.svg') }}" alt="" width="16" height="16">
                    </button>
                    <span class="os-m-combos__progress" aria-hidden="true"><span data-m-progress style="width: {{ 100 / $mCombos->count() }}%"></span></span>
                    <button type="button" class="os-m-combos__arrow os-focus" data-m-next aria-label="Combo siguiente">
                        <img src="{{ asset('images/nav/mobile/siguiente.svg') }}" alt="" width="16" height="16">
                    </button>
                </div>
            </div>
        </section>
    @endif

    {{-- Arma tu outfit --}}
    <section class="os-m-section gap-5 py-9" aria-labelledby="m-outfit">
        <h2 id="m-outfit" class="os-m-title text-[30px]">Tu próximo look<br>empieza aquí.</h2>
        <p class="text-[16px] leading-[1.35]">Combina una prenda de arriba y una de abajo. Hazlo a tu manera.</p>
        <a href="{{ route('web.outfit') }}" class="os-m-btn os-m-btn--dark os-focus">Arma tu outfit →</a>
    </section>

    {{-- Compra con confianza --}}
    <section class="os-m-section gap-5 bg-[#f0f7ff] py-9 leading-[1.35]" aria-label="Compra con confianza">
        <dl class="flex flex-col gap-5">
            <dt class="text-[15px] font-extrabold uppercase">Envíos a todo el Perú</dt>
            <dd class="text-[14px]">{{ \App\Support\SiteContent::t('Desde {desde}. Lima: delivery {lima}.') }}</dd>
            <dt class="text-[15px] font-extrabold uppercase">Pago seguro</dt>
            <dd class="text-[14px]">Tarjetas, Yape y Plin en el checkout.</dd>
            <dt class="text-[15px] font-extrabold uppercase">Tres marcas. Tu estilo.</dt>
            <dd class="text-[14px]">Overshark, Overshark Girls y Bravos.</dd>
        </dl>
    </section>
</div>
