{{-- Inventario visual de la tienda (Admin → Banners). Sigue a la pestaña de marca elegida:
     $inventory = ['fixed' => imágenes de esa marca, 'shared' => imágenes de toda la tienda, 'elsewhere' => fuentes de otros módulos]. --}}
<section class="flex flex-col rounded-sm border border-admin-border bg-admin-surface" id="imagenes-fijas">
    <div class="border-b border-admin-border px-5 py-4">
        <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Imágenes fijas · {{ $selectedBrand?->name ?? 'Marca' }}</h2>
        <p class="mt-1.5 max-w-3xl text-sm text-admin-text-secondary">
            Las fotos que la tienda trae de fábrica para esta marca: portada, promos, categorías, telas y más. Reemplaza cualquiera y se cambia en todas
            las páginas donde aparece; con «Restaurar» vuelve la original. PNG, JPG o WEBP de hasta 8 MB (se optimizan solas).
        </p>
    </div>

    <div class="flex flex-col gap-6 p-5">
        @forelse ($inventory['fixed'] as $group)
            @include('admin.banners._image-groups', ['groups' => [$group]])
        @empty
            <p class="text-sm text-admin-text-secondary">Esta marca no tiene imágenes fijas propias. Las de toda la tienda están más abajo.</p>
        @endforelse
    </div>
</section>

@if ($inventory['shared']->isNotEmpty())
    <section class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
        <div class="border-b border-admin-border px-5 py-4">
            <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Compartido por toda la tienda</h2>
            <p class="mt-1.5 max-w-3xl text-sm text-admin-text-secondary">Estas imágenes no pertenecen a una sola marca: se ven igual en Overshark, Overshark Girls y Bravos.</p>
        </div>
        <div class="flex flex-col gap-6 p-5">
            @include('admin.banners._image-groups', ['groups' => $inventory['shared']])
        </div>
    </section>
@endif

<section class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
    <div class="border-b border-admin-border px-5 py-4">
        <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Lo que se cambia en otras pantallas</h2>
        <p class="mt-1.5 max-w-3xl text-sm text-admin-text-secondary">El resto de las imágenes de la tienda ya tiene su propio módulo. Aquí ves cuántas hay y a dónde ir a cambiarlas.</p>
    </div>
    <div class="grid gap-4 p-5 md:grid-cols-2 2xl:grid-cols-3">
        @foreach ($inventory['elsewhere'] as $source)
            <article class="flex flex-col gap-3 rounded-sm border border-admin-border p-4">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-[1px] text-admin-text">{{ $source['title'] }}</h3>
                    <p class="text-xs text-admin-text-secondary">{{ $source['where'] }}</p>
                </div>
                <div class="flex h-16 gap-2">
                    @forelse ($source['thumbs'] as $thumb)
                        <img src="{{ $thumb }}" alt="" loading="lazy" class="h-16 w-16 rounded-sm border border-admin-border bg-admin-subtle object-cover">
                    @empty
                        <p class="self-center text-xs text-admin-text-muted">Aún no hay imágenes cargadas.</p>
                    @endforelse
                </div>
                <p class="text-xs font-bold text-admin-text">{{ $source['count'] }}</p>
                <div class="mt-auto flex flex-wrap items-center gap-3">
                    <a href="{{ $source['url'] }}" class="bg-admin-navy px-3 py-2 text-xs font-extrabold uppercase tracking-[1px] text-white">{{ $source['cta'] }}</a>
                    @isset($source['extra'])
                        <a href="{{ $source['extra'][1] }}" class="text-xs font-extrabold uppercase tracking-[1px] text-admin-accent hover:underline">{{ $source['extra'][0] }}</a>
                    @endisset
                </div>
            </article>
        @endforeach
    </div>
</section>
