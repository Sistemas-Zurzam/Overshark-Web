{{-- Portada de una marca con sus banners activos (Admin → Banners): reemplaza la imagen fija de la portada mientras haya al menos uno.
     $banners = BannerPortada activos, $label = nombre de la portada (título oculto para lectores de pantalla), $catalogUrl opcional. --}}
@php
    $catalogUrl = $catalogUrl ?? route('web.products.search', ['orden' => 'populares']);
@endphp
<section data-carousel="slide" class="relative isolate overflow-hidden bg-[#050505]" aria-roledescription="carrusel" aria-label="{{ $label }}">
    <h1 class="sr-only">{{ $label }}</h1>
    <div class="relative aspect-[1920/640] min-h-[240px] w-full">
        @foreach ($banners as $banner)
            <div @class(['absolute inset-0', 'block' => $loop->first, 'hidden' => ! $loop->first]) data-carousel-item>
                <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->name }}" class="size-full {{ $banner->modo === 'contain' ? 'object-contain' : 'object-cover' }}" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                @foreach ($banner->buttons ?? [] as $button)
                    @php
                        $radiusClass = ['square' => 'rounded-none', 'rounded' => 'rounded-lg', 'pill' => 'rounded-full'][$button['shape'] ?? 'rounded'] ?? 'rounded-lg';
                        $sizeClass = ['sm' => 'px-3 py-1.5 text-[10px] sm:px-4 sm:py-2 sm:text-xs', 'md' => 'px-4 py-2 text-xs sm:px-7 sm:py-3 sm:text-sm', 'lg' => 'px-5 py-2.5 text-sm sm:px-9 sm:py-4 sm:text-base', 'xl' => 'px-6 py-3 text-base sm:px-12 sm:py-5 sm:text-lg'][$button['size'] ?? 'md'] ?? 'px-4 py-2 text-xs sm:px-7 sm:py-3 sm:text-sm';
                    @endphp
                    <a href="{{ in_array($button['url'] ?? null, [null, '', '#productos'], true) ? $catalogUrl : $button['url'] }}" class="os-focus absolute z-20 -translate-x-1/2 -translate-y-1/2 {{ $radiusClass }} {{ $sizeClass }} font-black uppercase shadow-lg transition hover:-translate-y-[calc(50%+2px)]" style="left: {{ $button['x'] ?? 20 }}%; top: {{ $button['y'] ?? 50 }}%; background-color: {{ $button['bg_color'] ?? '#111111' }}; color: {{ $button['text_color'] ?? '#ffffff' }};">
                        {{ $button['text'] ?? 'Comprar ahora' }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>

    @if ($banners->count() > 1)
        <div class="absolute inset-x-0 bottom-3 z-30 flex justify-center gap-2">
            @foreach ($banners as $banner)
                <button type="button" @class(['slider-dot os-focus transition-all', 'is-active' => $loop->first]) aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="Mostrar banner {{ $loop->iteration }}" data-carousel-slide-to="{{ $loop->index }}"></button>
            @endforeach
        </div>
    @endif
</section>
