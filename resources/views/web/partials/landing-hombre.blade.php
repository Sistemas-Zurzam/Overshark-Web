{{--
    Portada de Overshark Hombre (prototipo "overshark-landing", Figma "Overshark — 31/10"), escritorio y celular.
    Estilos: resources/css/landing-hombre.css · Animaciones: resources/js/landing-hombre.js · Fotos: public/images/landing-hombre.
    Lo que se maneja desde el panel sigue funcionando aquí:
    - Admin → Banners (Overshark): con banners activos, su carrusel reemplaza al hero.
    - Admin → Banners → «hero_hombre»: la foto subida reemplaza a los dos modelos del hero.
    - Admin → Contenido de la tienda: textos, botones y categorías del hero (hero.hombre.*), Promo Wafflera (promos.*),
      calidad, telas y confianza (home.qualities / home.fabrics / home.benefits).
    - Admin → Promociones: «Ofertas relámpago» con cuenta regresiva mientras haya una promoción por horas vigente.
    Productos (las dos prendas del hero y los más vendidos) y costos de envío salen de la tienda.
--}}
@php
    $lh = fn (string $file) => asset('images/landing-hombre/'.$file);
    $lhText = fn (string $path, string $fallback) => \App\Support\SiteContent::get($path) ?: $fallback;
    $home = fn (string $path) => \App\Support\SiteContent::text($path);
    $lhCatalog = route('web.products.search', ['orden' => 'populares']);
    $lhSearch = fn (string $category) => route('web.products.search', ['categoria' => $category]);
    $lhMoney = fn (float $amount) => 'S/ '.number_format($amount, 2);
    $lhTitle = fn ($text) => \App\Support\ProductName::title((string) $text);
    // Foto propia de una sección (subida en el panel) o la del diseño.
    $lhCustom = function (string $path, string $fallback) {
        $url = \App\Support\SiteContent::asset($path);

        return str_contains($url, '/storage/') ? $url : $fallback;
    };

    $lhHeroUpload = \App\Support\SiteContent::asset('images/home/hero-model-hombre.webp');
    $lhHeroCustom = str_contains($lhHeroUpload, '/storage/') ? $lhHeroUpload : null;
    $lhCta1 = [$lhText('hero.hombre.cta1_text', 'Ver colección'), $lhText('hero.hombre.cta1_url', $lhCatalog)];
    $lhCta2 = [$lhText('hero.hombre.cta2_text', 'Arma tu outfit'), $lhText('hero.hombre.cta2_url', route('web.outfit'))];
    $lhCategories = collect(explode(',', (string) $lhText('hero.hombre.categories', 'Camisas, Polos, Jeans')))
        ->map(fn ($name) => trim($name))->filter()->take(6)
        ->map(fn (string $name) => [$name, $lhSearch(\Illuminate\Support\Str::slug($name))]);

    // Las dos prendas del hero: la variante más barata a la venta de ese modelo y color.
    $lhProduct = fn (string $name, string $color) => \App\Models\Admin\Producto::query()->inStore()
        ->where('price', '>', 0)->where('stock', '>', 0)
        ->whereRaw('UPPER(name) LIKE ?', ['%'.$name.'%'])->whereRaw('UPPER(color) LIKE ?', ['%'.$color.'%'])
        ->orderBy('price')->first();
    $lhTags = $lhHeroCustom ? [] : [
        ['class' => 'lh-tag--polo', 'depth' => 40, 'image' => 'thumb-polo-clasico-denim.jpg', 'title' => 'Polo Clásico Jersey', 'detail' => 'Denim · Jersey suave', 'product' => $lhProduct('CLASICO', 'DENIM'), 'fallback' => $lhSearch('polos')],
        ['class' => 'lh-tag--waffle', 'depth' => 26, 'image' => 'thumb-camisa-waffle-vino.jpg', 'title' => 'Camisa Waffle', 'detail' => 'Vino · Tejido en relieve', 'product' => $lhProduct('CAMISA WAFFLE', 'VINO'), 'fallback' => $lhSearch('camisas'), 'delay' => '-2.5s'],
    ];

    // Carrusel: los combos de Overshark que están activos y se pueden comprar (Admin → Combos), primero los que tienen
    // imagen propia. Un banner horizontal se muestra completo (con su versión para celular si la tiene); una imagen
    // cuadrada o vertical va dentro de una diapositiva con el nombre, el precio y el botón.
    $lhCombosList = \App\Support\ComboCatalog::forStore('Overshark')
        ->sortByDesc(fn (array $card) => $card['flyer'] ? 1 : 0)
        ->take(12)
        ->map(fn (array $card) => $card + ['label' => $card['title'].': '.$card['quantity'].' prendas por '.\App\Support\SiteContent::money($card['price'])])
        ->values();

    // «Jeans»: la foto que suba el admin; si no, la de un jeans a la venta; si no, la de polos.
    $lhJeans = \App\Support\SiteContent::asset('images/home-hombre/categoria-jeans.webp');
    if (! str_contains($lhJeans, '/storage/')) {
        $lhJeans = \App\Support\ProductCards::make(
            \App\Models\Admin\Producto::query()
                ->selectRaw('MIN(id) as id, zazu_company_id, name, MAX(imagen) as imagen')
                ->where('price', '>', 0)->where('stock', '>', 0)->withColorPhoto()
                ->whereRaw('UPPER(name) LIKE ?', ['%JEAN%'])
                ->tap(fn ($query) => \App\Support\GirlsHome::exceptGirls($query)->exceptBrand('BRAVOS'))
                ->groupBy('zazu_company_id', 'name')->orderByDesc(\Illuminate\Support\Facades\DB::raw('SUM(stock)'))->limit(1)->get(),
            $lh('polos.jpg'),
        )->first()?->display_image ?? $lh('polos.jpg');
    }
    $lhPicks = [
        ['label' => 'Camisas', 'text' => 'Frescas y con caída natural.', 'image' => $lhCustom('images/home-hombre/categoria-camisas.webp', $lh('camisas.jpg')), 'alt' => 'Camisa waffle', 'url' => $lhSearch('camisas')],
        ['label' => 'Polos', 'text' => 'El básico de todos los días.', 'image' => $lhCustom('images/home-hombre/categoria-polos.webp', $lh('polos.jpg')), 'alt' => 'Polo azul marino', 'url' => $lhSearch('polos')],
        ['label' => 'Jeans', 'text' => 'Para combinar con todo.', 'image' => $lhJeans, 'alt' => 'Jean de la colección', 'url' => $lhSearch('jeans')],
    ];

    $lhQualities = collect(range(0, 2))->map(fn ($i) => ['title' => $home("home.qualities.$i.title"), 'text' => $home("home.qualities.$i.text")]);
    $lhFabrics = collect(range(0, 2))->map(fn ($i) => ['name' => $home("home.fabrics.$i.name"), 'text' => $home("home.fabrics.$i.text")]);
    $lhWhatsapp = \App\Models\BrandSetting::current()->whatsappUrl() ?: config('services.storefront.chat_url') ?: route('web.contact');
    $lhBenefits = collect([
        ['icon' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>', 'url' => route('web.contact').'#faq'],
        ['icon' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>', 'url' => route('web.contact').'#faq'],
        ['icon' => '<path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 10h8M8 13h5"/>', 'url' => $lhWhatsapp],
    ])->map(fn (array $benefit, int $i) => $benefit + ['title' => $home("home.benefits.$i.title"), 'text' => $home("home.benefits.$i.text"), 'cta' => $home("home.benefits.$i.cta")]);

    $lhCard = function ($product) use ($lhTitle) {
        $color = $product->card_color ?? (collect($product->display_colors ?? [])->first()['name'] ?? null);
        $category = \App\Support\StorefrontCategories::slugFor($product->name);

        return [
            'name' => $lhTitle($product->name).($color ? ' · '.$lhTitle($color) : ''),
            'category' => $category ? \App\Support\StorefrontCategories::label($category) : null,
        ];
    };
@endphp

@push('head')
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;800;900&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ $lh('oceano.jpg') }}">
    @vite(['resources/css/landing-hombre.css', 'resources/js/landing-hombre.js'])
@endpush

{{-- Intro: el logo emerge del océano (se quita sola; una vez por sesión) --}}
@if ($banners->isEmpty())
    <div class="lh-intro" data-lh-intro hidden aria-hidden="true" style="background-image: url('{{ $lh('oceano.jpg') }}')">
        <div class="lh-intro__stage">
            <img class="lh-intro__logo" src="{{ $lh('logo-overshark-blanco.png') }}" alt="">
            <div class="lh-intro__wave"></div>
        </div>
        <p class="lh-intro__tag">COLECCIÓN HOMBRE</p>
    </div>
@endif

<div class="os-lh" data-landing-hombre style="--ocean: url('{{ $lh('oceano.jpg') }}')">
    @if ($banners->isNotEmpty())
        {{-- Banners propios (Admin → Banners · Overshark): reemplazan al hero mientras haya al menos uno activo. --}}
        @include('web.partials.banner-carousel', ['banners' => $banners, 'label' => 'Overshark Hombre', 'catalogUrl' => $lhCatalog])
    @else
        {{-- HERO --}}
        <section class="lh-hero" aria-labelledby="lh-title">
            <div class="lh-hero__bg" data-depth="12" style="background-image: url('{{ $lh('oceano.jpg') }}')"></div>
            <div class="lh-hero__rays"></div>
            <div class="lh-hero__shade"></div>
            <div class="lh-bubbles" data-lh-bubbles></div>

            <div class="lh-hero__copy">
                <p class="lh-kicker" data-in>{{ $lhText('hero.hombre.eyebrow', 'Colección hombre') }}</p>
                <h1 id="lh-title" class="lh-title">
                    <span class="ln"><span>{{ $lhText('hero.hombre.title', 'Nacido del') }}</span></span>
                    <span class="ln"><span>{{ $lhText('hero.hombre.accent', 'Océano.') }}</span></span>
                </h1>
                <p class="lh-sub" data-in>Hecho para<br>dominar</p>
                <p class="lh-text" data-in>{{ $lhText('hero.hombre.copy', 'Prendas esenciales con carácter, precisión y fuerza.') }}</p>
                <div class="lh-actions" data-in>
                    <a class="lh-btn" href="{{ $lhCta1[1] }}">{{ $lhCta1[0] }} <svg class="lh-btn__arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
                    <a class="lh-btn lh-btn--ghost" href="{{ $lhCta2[1] }}">{{ $lhCta2[0] }} <svg class="lh-btn__arrow" viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
                </div>
            </div>

            <div class="lh-hero__visual">
                @if ($lhCategories->isNotEmpty())
                    <nav class="lh-pill" data-in aria-label="Categorías">
                        @foreach ($lhCategories as [$categoryLabel, $categoryUrl])
                            <a href="{{ $categoryUrl }}">{{ $categoryLabel }}</a>@unless ($loop->last)<span aria-hidden="true"> · </span>@endunless
                        @endforeach
                    </nav>
                @endif
                @if ($lhHeroCustom)
                    {{-- Foto subida en Admin → Banners («hero_hombre»): reemplaza a los dos modelos del diseño. --}}
                    <div class="lh-model lh-model--custom" data-depth="24"><div class="lh-bob"><img src="{{ $lhHeroCustom }}" alt="Modelos de la colección hombre" fetchpriority="high"></div></div>
                @else
                    <div class="lh-model lh-model--vino" data-depth="18"><div class="lh-bob"><img src="{{ $lh('modelo-vino.webp') }}" alt="Modelo con camisa Waffle color vino" width="571" height="993" fetchpriority="high"></div></div>
                    <div class="lh-model lh-model--denim" data-depth="30"><div class="lh-bob"><img src="{{ $lh('modelo-denim.webp') }}" alt="Modelo con polo Clásico Jersey color denim" width="629" height="1003" fetchpriority="high"></div></div>
                @endif
                <div class="lh-floor"></div>
                @foreach ($lhTags as $tag)
                    <a class="lh-tag {{ $tag['class'] }} lh-tag-in" href="{{ $tag['product'] ? route('web.products.show', $tag['product']->id) : $tag['fallback'] }}" data-depth="{{ $tag['depth'] }}">
                        <span class="lh-dot" aria-hidden="true"></span>
                        <div class="lh-fl" @isset($tag['delay']) style="animation-delay: {{ $tag['delay'] }}" @endisset>
                            <img src="{{ $lh($tag['image']) }}" alt="">
                            <strong>{{ $tag['title'] }}</strong><small>{{ $tag['detail'] }}</small>
                            @if ($tag['product'])
                                <em>{{ $lhMoney((float) $tag['product']->price) }}</em>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CINTA --}}
    <div class="lh-ribbon" aria-label="Novedades">
        <div class="lh-ribbon__track" data-lh-ribbon>
            <span>{{ \App\Support\SiteContent::text('promos.wafflera_title') ?: '9 prendas × S/ 99' }}</span><span>Envíos a todo el Perú</span><span>Polos · Camisas · Jeans</span><span>Paga con Yape o Plin</span><span>Recojo en almacén gratis</span>
        </div>
    </div>

    {{-- OFERTAS RELÁMPAGO: promociones por horas vigentes (Admin → Promociones), con su cuenta regresiva --}}
    @if (! empty($flashDeals) && $flashDeals->isNotEmpty())
        <section class="lh-sec lh-sec--flash" aria-labelledby="lh-flash">
            <div class="lh-wrap">
                <div class="lh-head">
                    <div>
                        <p class="lh-eyebrow rv">Por tiempo limitado</p>
                        <h2 id="lh-flash" class="lh-h2 rv">Ofertas relámpago</h2>
                        <p class="lh-countdown rv">@include('web.partials.promo-countdown', ['promo' => ['ends_at' => $flashDeals->map(fn ($product) => $product->promo['ends_at'])->min()], 'prefix' => 'Termina en '])</p>
                    </div>
                    <a class="lh-btn-dark rv" style="--d:.1s" href="{{ $lhCatalog }}">Ver la tienda →</a>
                </div>
                <div class="lh-grid4">
                    @foreach ($flashDeals->take(4) as $product)
                        @php($card = $lhCard($product))
                        <article class="lh-card rv" style="--d:{{ $loop->index * 0.08 }}s">
                            <a class="lh-card__media" href="{{ route('web.products.show', $product->id) }}">
                                <span class="lh-card__badge">{{ $product->promo['label'] ?: '-'.$product->promo['percent'].'%' }}</span>
                                <img src="{{ $product->display_image }}" alt="{{ $card['name'] }}" loading="lazy">
                                <span class="lh-card__add">Ver prenda</span>
                            </a>
                            <div class="lh-card__row"><span>{{ $card['name'] }}</span><span class="lh-card__price"><s>{{ $lhMoney((float) $product->promo['regular']) }}</s> {{ $lhMoney((float) $product->promo['price']) }}</span></div>
                            @if ($card['category'])<p class="lh-card__cat">{{ $card['category'] }}</p>@endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAVORITOS: los más vendidos de la tienda --}}
    @if ($bestSellers->isNotEmpty())
        <section class="lh-sec" id="favoritos" aria-labelledby="lh-fav">
            <div class="lh-wrap">
                <div class="lh-head">
                    <div>
                        <p class="lh-eyebrow rv">Más vendidos de la semana</p>
                        <h2 id="lh-fav" class="lh-h2 rv">Los favoritos<br>del océano</h2>
                    </div>
                    <a class="lh-btn-dark rv" style="--d:.1s" href="{{ $lhCatalog }}">Ver los más vendidos →</a>
                </div>
                <div class="lh-grid4">
                    @foreach ($bestSellers->take(4) as $product)
                        @php($card = $lhCard($product))
                        <article class="lh-card rv" style="--d:{{ $loop->index * 0.08 }}s">
                            <a class="lh-card__media" href="{{ route('web.products.show', $product->id) }}">
                                <span class="lh-card__num">{{ sprintf('%02d', $loop->iteration) }}</span>
                                <img src="{{ $product->display_image }}" alt="{{ $card['name'] }}" loading="lazy">
                                <span class="lh-card__add">Ver prenda</span>
                            </a>
                            <div class="lh-card__row"><span>{{ $card['name'] }}</span><span>{{ $lhMoney((float) ($product->promo['price'] ?? $product->min_price)) }}</span></div>
                            @if ($card['category'])<p class="lh-card__cat">{{ $card['category'] }}</p>@endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- COMBOS (los activos del panel) --}}
    @if ($lhCombosList->isNotEmpty())
    <section class="lh-combos" id="combos" aria-label="Combos">
        <div class="lh-wrap">
            <div class="lh-slider rv" data-lh-slider aria-roledescription="carrusel">
                <div class="lh-slides" data-lh-slides tabindex="0">
                    @foreach ($lhCombosList as $lhCombo)
                        @if ($lhCombo['flyer_wide'])
                            <a class="lh-slide {{ $lhCombo['flyer_mobile'] ? 'has-mobile' : '' }}" href="{{ $lhCombo['url'] }}" aria-label="{{ $lhCombo['label'] }}">
                                <picture>
                                    @if ($lhCombo['flyer_mobile'])<source media="(max-width: 900px)" srcset="{{ $lhCombo['flyer_mobile'] }}">@endif
                                    <img src="{{ $lhCombo['flyer'] }}" alt="{{ $lhCombo['label'] }}" @unless ($loop->first) loading="lazy" @endunless>
                                </picture>
                            </a>
                        @else
                            <a class="lh-slide lh-slide--card" href="{{ $lhCombo['url'] }}" aria-label="{{ $lhCombo['label'] }}">
                                <span class="lh-slide__copy">
                                    <span class="lh-slide__tag">Combo · {{ $lhCombo['quantity'] }} prendas</span>
                                    <span class="lh-slide__title">{{ $lhCombo['title'] }}</span>
                                    <span class="lh-slide__price">{{ \App\Support\SiteContent::money($lhCombo['price']) }}
                                        @if ($lhCombo['compare_at'])<s>{{ \App\Support\SiteContent::money($lhCombo['compare_at']) }}</s>@endif
                                    </span>
                                    <span class="lh-slide__detail">{{ $lhCombo['detail'] }}</span>
                                    <span class="lh-slide__cta">Arma tu combo <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
                                </span>
                                <span class="lh-slide__media {{ $lhCombo['flyer'] ? 'is-flyer' : 'is-products' }}">
                                    @if ($lhCombo['flyer'])
                                        <picture>
                                            @if ($lhCombo['flyer_mobile'])<source media="(max-width: 900px)" srcset="{{ $lhCombo['flyer_mobile'] }}">@endif
                                            <img src="{{ $lhCombo['flyer'] }}" alt="" @unless ($loop->first) loading="lazy" @endunless>
                                        </picture>
                                    @else
                                        @foreach ($lhCombo['images'] as $lhImage)
                                            <img src="{{ $lhImage }}" alt="" @unless ($loop->first) loading="lazy" @endunless>
                                        @endforeach
                                    @endif
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
                <div class="lh-slider__ui">
                    <button type="button" class="lh-arrow" data-lh-prev aria-label="Combo anterior"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
                    <div class="lh-dots" data-lh-dots></div>
                    <button type="button" class="lh-arrow" data-lh-next aria-label="Combo siguiente"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- CATEGORÍAS: elige tu básico --}}
    <section class="lh-sec lh-sec--top0" aria-labelledby="lh-picks">
        <div class="lh-wrap">
            <div class="lh-head">
                <div>
                    <p class="lh-eyebrow rv">Básicos para todos los días</p>
                    <h2 id="lh-picks" class="lh-h2 rv">Sigue la corriente.<br>Marca tu estilo.</h2>
                </div>
            </div>
            <div class="lh-grid3">
                @foreach ($lhPicks as $pick)
                    <article class="lh-pick rv" style="--d:{{ $loop->index * 0.12 }}s">
                        <div class="lh-pick__media"><img src="{{ $pick['image'] }}" alt="{{ $pick['alt'] }}" loading="lazy"></div>
                        <div class="lh-pick__body"><h3>{{ $pick['label'] }}</h3><p>{{ $pick['text'] }}</p><a class="lh-btn-dark" href="{{ $pick['url'] }}">Ver {{ \Illuminate\Support\Str::lower($pick['label']) }} →</a></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- EDITORIAL: por qué elegir Overshark (calidad editable en el panel) --}}
    <section class="lh-editorial" aria-labelledby="lh-quality">
        <div class="lh-editorial__copy">
            <p class="lh-kicker rv">Por qué elegir Overshark</p>
            <h2 id="lh-quality" class="rv" style="--d:.08s">Más fresco.<br>Más fuerte.</h2>
            <p class="rv" style="--d:.16s">Piqué y waffle: texturas que respiran y mantienen la forma lavado tras lavado.</p>
            <ul class="lh-qualities rv" style="--d:.2s">
                @foreach ($lhQualities as $quality)
                    <li><strong>{{ $quality['title'] }}</strong><span>{{ $quality['text'] }}</span></li>
                @endforeach
            </ul>
            <div class="rv" style="--d:.24s"><a class="lh-btn-white" href="{{ $lhSearch('polos') }}">Descubrir polos →</a></div>
        </div>
        <div class="lh-editorial__media rv-mask"><img data-lh-parallax src="{{ $lhCustom('images/home-hombre/outfit-principal.webp', $lh('editorial.jpg')) }}" alt="Hombre con camisa waffle lila" loading="lazy"></div>
    </section>

    {{-- PROMO WAFFLERA (Admin → Contenido de la tienda → Promos; imagen en Admin → Banners) --}}
    <section class="os-hh lh-promo" aria-labelledby="hh-promo">
        <div class="lh-wrap">
            @include('web.partials.promo-wafflera', ['girls' => false])
        </div>
    </section>

    {{-- OVERSHARK PARA TODOS + TELAS (editables en el panel) --}}
    <section class="lh-all" id="para-todos" aria-label="Overshark para todos">
        <div class="lh-wrap">
            <div>
                <h2 class="rv">Mismo océano.<br>Tu propio estilo.</h2>
                <p class="rv" style="--d:.08s">Overshark también tiene su línea para mujer: Overshark Girls.</p>
                <a class="lh-btn-ghost rv" style="--d:.16s" href="{{ route('web.home', ['linea' => 'mujer']) }}">Explorar Overshark Girls →</a>
            </div>
            <div class="lh-fabric rv" style="--d:.2s" data-lh-fabric aria-label="Nuestras telas">
                @foreach ($lhFabrics as $fabric)
                    <div @class(['lh-fabric__item', 'is-on' => $loop->first]) data-lh-fabric-item><span>{{ $fabric['name'] }}</span><small>{{ $fabric['text'] }}</small></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CONFIANZA + EXPLORAR (textos editables en el panel) --}}
    <section class="lh-sec">
        <div class="lh-wrap">
            <h2 class="lh-h2 rv" style="margin-bottom:32px;font-size:clamp(26px,2.4vw,32px)">Tu compra. Sin complicaciones.</h2>
            <div class="lh-trust">
                @foreach ($lhBenefits as $benefit)
                    <article class="rv" style="--d:{{ $loop->index * 0.1 }}s">
                        <svg viewBox="0 0 24 24" aria-hidden="true">{!! $benefit['icon'] !!}</svg>
                        <h3>{{ \Illuminate\Support\Str::upper($benefit['title']) }}</h3>
                        <p>{{ $benefit['text'] }}</p>
                        @if ($benefit['cta'])
                            <a class="lh-trust__link" href="{{ $benefit['url'] }}" @if (str_starts_with($benefit['url'], 'http') && ! str_contains($benefit['url'], request()->getHost())) target="_blank" rel="noopener" @endif>{{ $benefit['cta'] }} →</a>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="lh-explore rv">
                <h2>Sigue explorando</h2>
                <div>
                    <a class="lh-btn-dark" href="{{ $bravosBrand ? route('web.brands.show', $bravosBrand) : route('web.products.search', ['marca' => 'BRAVOS']) }}">Bravos →</a>
                    <a class="lh-btn-dark" href="{{ route('web.home', ['linea' => 'mujer']) }}">Overshark Girls →</a>
                </div>
            </div>
        </div>
    </section>
</div>
