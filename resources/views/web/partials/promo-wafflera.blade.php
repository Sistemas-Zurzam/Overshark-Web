{{--
    Banner "Promo Wafflera · 9 prendas × S/ 99" (diseños Figma Home y Tienda). Va dentro de un .os-hh
    (toma su paleta) y la sección que lo incluye pone el espaciado. $girls: foto y combos de Overshark Girls.
--}}
@php($imageOnly = (bool) \App\Support\SiteContent::get('promos.wafflera_image_only'))
{{-- Todo el banner lleva a Combos. "Solo imagen" (panel): la imagen subida ya trae su texto y botón, así que no se encima nada. --}}
@php($combosUrl = route('web.combos.index', $girls ? ['marca' => 'Girls'] : []))
<div class="relative h-[340px] overflow-hidden bg-[var(--hh-dark)]">
    <img src="{{ \App\Support\SiteContent::asset('images/'.($girls ? 'home-mujer' : 'home-hombre').'/promo.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover {{ $imageOnly ? 'object-center' : 'object-right' }}" loading="lazy">
    @if ($imageOnly)
        <h2 id="hh-promo" class="sr-only">{{ \App\Support\SiteContent::text('promos.wafflera_title') }}</h2>
        <a href="{{ $combosUrl }}" class="os-focus absolute inset-0" aria-labelledby="hh-promo"></a>
    @else
        <div class="absolute inset-0 [background:var(--hh-veil)]" aria-hidden="true"></div>
        <div class="relative flex h-full flex-col items-start justify-center gap-3.5 px-6 sm:px-14">
            <p class="os-hh-eyebrow !text-[13px] !tracking-[3px] !text-[var(--hh-bright)]">Promo Wafflera</p>
            <h2 id="hh-promo" class="os-hh-display text-[44px] leading-[0.95] text-white min-[400px]:text-[52px] md:text-[68px] lg:text-[84px] lg:leading-[80px]">{{ \App\Support\SiteContent::text('promos.wafflera_title') }}</h2>
            <p class="max-w-[420px] text-[16px] font-medium leading-6 text-[var(--hh-light-text)]">{{ \App\Support\SiteContent::text('promos.wafflera_text') }}</p>
            {{-- after:absolute after:inset-0 extiende el enlace a todo el banner (el botón y el resto llevan a Combos). --}}
            <a href="{{ $combosUrl }}" class="os-hh-btn os-focus bg-[var(--hh-bright)] text-[var(--hh-on-bright)] hover:bg-[var(--hh-bright-hover)] after:absolute after:inset-0">Arma tu combo →</a>
        </div>
    @endif
</div>
