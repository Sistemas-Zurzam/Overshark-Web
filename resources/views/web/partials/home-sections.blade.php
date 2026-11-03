{{--
    Inicio debajo del hero (diseños Figma "01 · Home Hombre" y "01 · Home Mujer"): los más vendidos,
    banner Promo Wafflera, categorías, por qué elegir Overshark (calidad y telas), teaser Arma tu outfit,
    compra con confianza y familia Overshark. $line ('hombre' | 'mujer') elige fotos, textos y paleta
    (.os-hh--girls en app.css). Fotos fijas en public/images/home-hombre y home-mujer; los productos vienen de la tienda.
--}}
@php
    $girls = ($line ?? 'hombre') === 'mujer';
    $shared = fn (string $file) => asset('images/home-hombre/'.$file);
    $img = fn (string $file) => asset('images/'.($girls ? 'home-mujer' : 'home-hombre').'/'.$file);
    $search = fn (array $query) => route('web.products.search', $query);
    $categories = $girls
        ? [
            ['label' => 'Baby tees', 'image' => 'categoria-baby-tees.webp', 'url' => $search(['categoria' => 'baby-tees'])],
            ['label' => 'Tops', 'image' => 'categoria-tops.webp', 'url' => $search(['categoria' => 'tops'])],
            ['label' => 'Poleras', 'image' => 'categoria-poleras.webp', 'url' => $search(['categoria' => 'poleras'])],
            ['label' => 'Joggers', 'image' => 'categoria-joggers.webp', 'url' => $search(['categoria' => 'joggers'])],
        ]
        : [
            ['label' => 'Polos', 'image' => 'categoria-polos.webp', 'url' => $search(['categoria' => 'polos'])],
            ['label' => 'Camisas', 'image' => 'categoria-camisas.webp', 'url' => $search(['categoria' => 'camisas']), 'shade' => true],
            ['label' => 'Poleras', 'image' => 'categoria-poleras.webp', 'url' => $search(['categoria' => 'poleras'])],
            ['label' => 'Joggers', 'image' => 'categoria-joggers.webp', 'url' => $search(['categoria' => 'pantalones'])],
        ];
    $qualities = [
        ['icon' => 'icon-no-destine.svg', 'title' => 'No destiñe', 'text' => 'Mantiene su color lavado tras lavado.'],
        ['icon' => 'icon-no-encoge.svg', 'title' => 'No encoge', 'text' => 'Conserva su forma original siempre.'],
        ['icon' => 'icon-no-bolitas.svg', 'title' => 'No hace bolitas', 'text' => 'Mayor durabilidad en el uso diario.'],
    ];
    $fabrics = [
        ['name' => 'Pique', 'image' => 'tela-pique.webp', 'text' => 'Textura de nido de abeja, transpirable'],
        ['name' => 'Waffle', 'image' => 'tela-waffle.webp', 'text' => 'Relieve en cuadros que mantiene la forma'],
        ['name' => 'Jersey', 'image' => 'tela-jersey.webp', 'text' => 'Suave, ligero y con caída natural'],
    ];
    $benefits = [
        ['image' => 'confianza-envios.webp', 'icon' => 'icon-envio.svg', 'title' => 'Envíos a todo el Perú', 'text' => 'Lima: delivery o agencia. Provincia: agencia. Costo único de S/ 14.', 'cta' => 'Ver políticas de envío', 'url' => route('web.contact')],
        ['image' => 'confianza-pago.webp', 'icon' => 'icon-pago.svg', 'title' => 'Pago contraentrega', 'text' => 'Paga al recibir o con Yape, Plin y cualquier billetera que lea QR.', 'cta' => 'Ver métodos de pago', 'url' => route('web.contact')],
        ['image' => 'confianza-atencion.webp', 'icon' => 'icon-atencion.svg', 'title' => 'Atención al cliente', 'text' => 'Te ayudamos antes, durante y después de tu compra.', 'cta' => 'Escríbenos por WhatsApp', 'url' => \App\Models\BrandSetting::current()->whatsappUrl() ?: config('services.storefront.chat_url') ?: route('web.contact')],
    ];
    $stores = [
        ['logo' => 'logo-overshark.png', 'logoClass' => 'h-[32px]', 'name' => 'Hombre', 'text' => 'Polos, camisas, joggers y básicos', 'cta' => 'Ver tienda hombre', 'url' => route('web.home', ['linea' => 'hombre'])],
        ['logo' => 'logo-girls.png', 'logoClass' => 'h-[39px]', 'name' => 'Mujer', 'text' => 'Baby tees, tops, joggers y básicos', 'cta' => 'Ver tienda mujer', 'url' => route('web.home', ['linea' => 'mujer'])],
        ['logo' => 'logo-bravos.svg', 'logoClass' => 'h-[42px]', 'name' => 'Urbano', 'text' => 'Casacas, poleras y ropa urbana', 'cta' => 'Ver tienda Bravos', 'url' => $bravosBrand ? route('web.brands.show', $bravosBrand) : $search(['q' => 'bravos'])],
    ];
@endphp

<div @class(['os-hh', 'os-hh--girls' => $girls])>
    {{-- Los más vendidos --}}
    @if ($bestSellers->isNotEmpty())
        <section class="os-hh-wrap py-16 lg:py-[88px]" aria-labelledby="hh-top" data-hh-carousel>
            <header class="os-hh-head">
                <div>
                    <p class="os-hh-eyebrow">Top de la semana</p>
                    <h2 id="hh-top" class="os-hh-title">Los más vendidos</h2>
                    <p class="os-hh-sub">Los {{ $bestSellers->count() }} favoritos de nuestros clientes</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ $search(['orden' => 'populares']) }}" class="os-hh-link os-focus">Ver todos →</a>
                    <div class="hidden sm:flex">
                        <button type="button" class="os-hh-arrow os-focus" data-hh-prev aria-label="Anteriores"><img src="{{ $shared('flecha-anterior.svg') }}" alt="" width="18" height="18"></button>
                        <button type="button" class="os-hh-arrow os-focus" data-hh-next aria-label="Siguientes"><img src="{{ $shared('flecha-siguiente.svg') }}" alt="" width="18" height="18"></button>
                    </div>
                </div>
            </header>

            <ul class="os-hh-track mt-8" data-hh-track>
                @foreach ($bestSellers as $product)
                    <li class="os-hh-card">
                        @include('web.partials.hh-product-card', ['rank' => $loop->iteration, 'photoClass' => 'h-[314px]'])
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Banner · Promo Wafflera --}}
    <section @class(['os-hh-wrap pb-16 lg:pb-[88px]', 'pt-16 lg:pt-[88px]' => $bestSellers->isEmpty()]) aria-labelledby="hh-promo">
        @include('web.partials.promo-wafflera')
    </section>

    {{-- Categorías --}}
    <section class="os-hh-wrap pb-16 lg:pb-[88px]" aria-labelledby="hh-cat">
        <header class="os-hh-head">
            <div>
                <h2 id="hh-cat" class="os-hh-title">Categorías</h2>
                <p class="os-hh-sub">Básicos para todos los días</p>
            </div>
            <a href="{{ $search(['orden' => 'populares']) }}" class="os-hh-link os-focus">Ver todas →</a>
        </header>
        <ul class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($categories as $category)
                <li>
                    <a href="{{ $category['url'] }}" class="os-focus group relative grid h-[260px] place-items-center overflow-hidden sm:h-[400px]">
                        <img src="{{ $img($category['image']) }}" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                        <span @class(['absolute inset-0 transition group-hover:bg-[var(--hh-shade-hover)]', 'bg-[var(--hh-shade)]' => $category['shade'] ?? false]) aria-hidden="true"></span>
                        <span class="os-hh-display relative text-[32px] tracking-[1px] text-white sm:text-[44px]">{{ $category['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Por qué elegir Overshark: calidad de la prenda y las tres telas --}}
    <section class="os-hh-wrap flex flex-col gap-4 pb-16 lg:pb-[88px]" aria-labelledby="hh-quality">
        <div class="flex flex-col gap-10 bg-[var(--hh-dark)] px-6 py-12 sm:p-12 xl:flex-row xl:items-center xl:gap-14 xl:p-16">
            <div class="flex min-w-0 flex-1 flex-col items-start gap-[18px]">
                <p class="os-hh-eyebrow !text-[12px] !tracking-[3px] !text-[var(--hh-bright)]">Por qué elegir Overshark</p>
                <h2 id="hh-quality" class="os-hh-display text-[40px] leading-[40px] text-white sm:text-[56px] sm:leading-[54px]">
                    Calidad que se nota,<br><span class="text-[var(--hh-bright)]">comodidad que se siente</span>
                </h2>
                <span class="h-[2px] w-12 bg-[var(--hh-bright)]" aria-hidden="true"></span>
                <p class="max-w-[440px] text-[16px] font-medium leading-[26px] text-white/80">{{ $girls ? 'Básicos' : 'Polos' }} diseñados para el uso diario con materiales que garantizan durabilidad, frescura y confort.</p>
            </div>
            <ul class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:shrink-0">
                @foreach ($qualities as $quality)
                    <li class="os-hh-quality">
                        <span class="grid h-[60px] w-[60px] shrink-0 place-items-center rounded-[18px] border border-[var(--hh-icon-line)] bg-[var(--hh-icon-bg)]" aria-hidden="true">
                            <img src="{{ $img($quality['icon']) }}" alt="" width="26" height="26">
                        </span>
                        <div class="flex flex-col gap-1.5 sm:items-center sm:gap-3.5">
                            <h3 class="text-[15px] font-extrabold text-white">{{ $quality['title'] }}</h3>
                            <p class="text-[13px] font-medium leading-[19px] text-[var(--hh-muted)] sm:max-w-[138px]">{{ $quality['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        <ul class="grid gap-4 lg:grid-cols-3">
            @foreach ($fabrics as $fabric)
                <li class="flex h-[200px] overflow-hidden border border-[#d9d9d9] bg-white">
                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5 pl-6 pr-4">
                        <p class="text-[11px] font-extrabold uppercase tracking-[2.5px] text-[var(--hh-accent)]">Tela</p>
                        <h3 class="os-hh-display text-[40px] leading-none">{{ $fabric['name'] }}</h3>
                        <p class="max-w-[150px] text-[13px] font-medium leading-[19px] text-[var(--hh-fabric-text)]">{{ $fabric['text'] }}</p>
                    </div>
                    <img src="{{ $img($fabric['image']) }}" alt="Textura de tela {{ mb_strtolower($fabric['name']) }}" width="190" height="198" class="h-full w-[150px] shrink-0 object-cover sm:w-[260px] lg:w-[140px] xl:w-[190px]" loading="lazy">
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Teaser · Arma tu outfit --}}
    <section class="bg-[var(--hh-soft)] py-16 lg:py-[88px]" aria-labelledby="hh-outfit">
        <div class="os-hh-wrap grid items-center gap-10 lg:grid-cols-[560px_minmax(0,1fr)] lg:gap-14">
            <img src="{{ $img('outfit-principal.webp') }}" alt="{{ $girls ? 'Modelo con conjunto de buzo rosado' : 'Modelo con camisa waffle azul y jean' }}" class="aspect-square w-full object-cover" loading="lazy">
            <div class="flex flex-col items-start gap-5">
                <p class="os-hh-eyebrow !text-[13px] !tracking-[3px]">Nuevo · Estilista online</p>
                <h2 id="hh-outfit" class="os-hh-display text-[52px] leading-[0.95] sm:text-[80px] sm:leading-[76px]">¡Arma tu outfit!</h2>
                <p class="text-[17px] font-medium leading-[27px] text-[#545966]">Combina un top, un bottom y un básico extra. Te mostramos cómo queda el look completo y lo agregas al carrito en un clic.</p>
                <ol class="grid w-full grid-cols-3 gap-3">
                    @foreach (['Elige tu top', 'Súmale un bottom', 'Agrégalo al carrito'] as $step)
                        <li class="flex flex-col gap-1.5 bg-white p-4">
                            <span class="os-hh-display text-[32px] leading-none text-[var(--hh-accent)]">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="text-[14px] font-bold">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
                <div class="flex gap-3" aria-hidden="true">
                    @foreach (['outfit-look-1.webp', 'outfit-look-2.webp', 'outfit-look-3.webp'] as $look)
                        <img src="{{ $img($look) }}" alt="" class="h-[128px] w-[96px] object-cover" loading="lazy">
                    @endforeach
                </div>
                <a href="{{ route('web.outfit') }}" class="os-hh-btn os-focus bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Empezar mi look →</a>
            </div>
        </div>
    </section>

    {{-- Compra con confianza --}}
    <section class="bg-[var(--hh-dark)] py-16 text-white lg:py-[88px]" aria-labelledby="hh-trust">
        <div class="os-hh-wrap text-center">
            <h2 id="hh-trust" class="os-hh-display text-[40px] leading-none sm:text-[56px] sm:leading-[56px]">Compra con confianza</h2>
            <p class="mt-3 text-[16px] font-medium text-[var(--hh-muted)]">Todo lo que necesitas para disfrutar tu experiencia Overshark</p>
            <ul class="mt-10 grid gap-4 md:grid-cols-3">
                @foreach ($benefits as $benefit)
                    <li class="flex flex-col items-center gap-3.5 border border-white/10 bg-[var(--hh-card)] pb-8">
                        <img src="{{ $shared($benefit['image']) }}" alt="" class="h-[260px] w-full object-cover" loading="lazy">
                        <span class="grid h-12 w-12 place-items-center rounded-full border border-[var(--hh-icon-line)]" aria-hidden="true">
                            <img src="{{ $img($benefit['icon']) }}" alt="" width="22" height="22">
                        </span>
                        <h3 class="text-[20px] font-extrabold">{{ $benefit['title'] }}</h3>
                        <p class="max-w-[300px] px-4 text-[14px] font-medium leading-[21px] text-[var(--hh-muted)]">{{ $benefit['text'] }}</p>
                        <a href="{{ $benefit['url'] }}" class="os-focus text-[14px] font-extrabold text-[var(--hh-bright)] hover:underline">{{ $benefit['cta'] }} →</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Familia Overshark --}}
    <section class="os-hh-wrap py-16 lg:py-[88px]" aria-labelledby="hh-family">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <div>
                <p class="os-hh-eyebrow !text-[13px] !tracking-[3px]">Familia Overshark</p>
                <h2 id="hh-family" class="os-hh-display mt-2.5 text-[52px] leading-[0.95] sm:text-[72px] sm:leading-[68px] lg:text-[96px] lg:leading-[88px]">Otras tiendas<br>de nuestra familia</h2>
            </div>
            <p class="max-w-[340px] text-[16px] font-medium leading-[25px] text-[#545966]">Overshark reúne tiendas para hombre, para mujer y la línea urbana Bravos, que comparten la misma visión: básicos bien hechos, a precio justo y con envío a todo el Perú.</p>
        </div>
        <ul class="mt-9 grid md:grid-cols-3">
            @foreach ($stores as $store)
                <li class="-mb-px md:-mr-px md:mb-0">
                    <a href="{{ $store['url'] }}" class="os-focus flex h-[240px] flex-col items-center justify-center gap-3.5 border border-[#d9d9d9] bg-white transition hover:bg-[var(--hh-soft)]">
                        <img src="{{ $shared($store['logo']) }}" alt="{{ $store['name'] === 'Urbano' ? 'Bravos' : ($store['name'] === 'Mujer' ? 'Overshark Girls' : 'Overshark') }}" class="{{ $store['logoClass'] }} w-auto max-w-[210px] object-contain">
                        <span class="os-hh-display text-[36px] tracking-[2px]">{{ $store['name'] }}</span>
                        <span class="text-[14px] font-medium text-[#545966]">{{ $store['text'] }}</span>
                        <span class="os-hh-link">{{ $store['cta'] }} →</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

</div>
