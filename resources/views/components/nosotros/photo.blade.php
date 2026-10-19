{{--
    Foto de la página Nosotros. $src viene de App\Support\AboutPage::imageUrl() (subida en el admin o archivo
    en public/images/nosotros); si es null se muestra un espacio reservado indicando la foto pendiente.
    Las fotos se muestran en blanco y negro (estilo industrial) y se cargan en diferido salvo la portada.
--}}
@props(['src' => null, 'file' => '', 'alt', 'ratio' => '4 / 3', 'eager' => false, 'gray' => true])

<div {{ $attributes->merge(['class' => 'os-nos-photo']) }} style="aspect-ratio: {{ $ratio }}">
    @if ($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            @class(['h-full w-full object-cover', 'os-nos-photo__img--gray' => $gray])
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            @if ($eager) fetchpriority="high" @endif
            decoding="async"
        >
    @else
        <div class="os-nos-photo__empty" role="img" aria-label="{{ $alt }}">
            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 17-5-5-8 7"/></svg>
            <span>Foto pendiente · súbela en Admin → Página Nosotros</span>
            @if ($file)<code>images/nosotros/{{ $file }}.webp</code>@endif
        </div>
    @endif
</div>
