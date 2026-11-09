{{-- Inventario visual de la tienda (Admin → Banners): las imágenes fijas, que se reemplazan aquí, y lo que se administra en otros módulos.
     $inventory = ['fixed' => grupos de App\Support\SiteContent::IMAGES, 'elsewhere' => fuentes de otros módulos]. --}}
<section class="flex flex-col rounded-sm border border-admin-border bg-admin-surface" id="imagenes-fijas">
    <div class="border-b border-admin-border px-5 py-4">
        <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Imágenes fijas de la tienda</h2>
        <p class="mt-1.5 max-w-3xl text-sm text-admin-text-secondary">
            Las fotos que la tienda trae de fábrica: portadas, promos, categorías, telas y más. Reemplaza cualquiera y se cambia en todas
            las páginas donde aparece; con «Restaurar» vuelve la original. PNG, JPG o WEBP de hasta 8 MB (se optimizan solas).
        </p>
    </div>

    <div class="flex flex-col gap-6 p-5">
        @foreach ($inventory['fixed'] as $group)
            <div class="flex flex-col gap-3">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-[1px] text-admin-text">{{ $group['title'] }}</h3>
                    @if ($group['where']) <p class="text-xs text-admin-text-secondary">{{ $group['where'] }}</p> @endif
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($group['items'] as $image)
                        <div class="flex flex-col overflow-hidden rounded-sm border border-admin-border">
                            <div class="grid h-36 place-items-center bg-admin-subtle">
                                @if ($image['url'])
                                    <img src="{{ $image['url'] }}" alt="" loading="lazy" class="size-full object-cover">
                                @else
                                    <span class="text-xs text-admin-text-muted">Sin imagen</span>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col gap-2 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[13px] font-bold leading-4 text-admin-text">{{ $image['label'] }}</p>
                                    <x-admin.badge :tone="$image['custom'] ? 'info' : 'neutral'" :label="$image['custom'] ? 'Reemplazada' : 'Original'" />
                                </div>
                                <div class="mt-auto flex items-center gap-2">
                                    <form action="{{ route('admin.banners.images', $image['key']) }}" method="POST" enctype="multipart/form-data" class="flex-1">
                                        @csrf
                                        <label class="block cursor-pointer border border-admin-border-strong bg-admin-surface px-3 py-2 text-center text-xs font-extrabold uppercase tracking-[1px] text-admin-text hover:bg-admin-hover">
                                            Reemplazar
                                            <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="sr-only" onchange="this.form.submit()">
                                        </label>
                                    </form>
                                    @if ($image['custom'])
                                        <form action="{{ route('admin.banners.images', $image['key']) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="restore" value="1">
                                            <button type="submit" class="px-2 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-danger hover:underline">Restaurar</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

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
