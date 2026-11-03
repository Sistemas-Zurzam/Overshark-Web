{{--
    Banner "Promo Wafflera · 9 prendas × S/ 99" (diseños Figma Home y Tienda). Va dentro de un .os-hh
    (toma su paleta) y la sección que lo incluye pone el espaciado. $girls: foto y combos de Overshark Girls.
--}}
<div class="relative h-[340px] overflow-hidden bg-[var(--hh-dark)]">
    <img src="{{ asset('images/'.($girls ? 'home-mujer' : 'home-hombre').'/promo.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover object-right" loading="lazy">
    <div class="absolute inset-0 [background:var(--hh-veil)]" aria-hidden="true"></div>
    <div class="relative flex h-full flex-col items-start justify-center gap-3.5 px-6 sm:px-14">
        <p class="os-hh-eyebrow !text-[13px] !tracking-[3px] !text-[var(--hh-bright)]">Promo Wafflera</p>
        <h2 id="hh-promo" class="os-hh-display text-[52px] leading-[0.95] text-white sm:text-[84px] sm:leading-[80px]">9 prendas × S/ 99</h2>
        <p class="max-w-[420px] text-[16px] font-medium leading-6 text-[var(--hh-light-text)]">Elige color y talla de cada prenda. Llegan juntas en un solo envío.</p>
        <a href="{{ route('web.combos.index', $girls ? ['marca' => 'Girls'] : []) }}" class="os-hh-btn os-focus bg-[var(--hh-bright)] text-[var(--hh-on-bright)] hover:bg-[var(--hh-bright-hover)]">Arma tu combo →</a>
    </div>
</div>
