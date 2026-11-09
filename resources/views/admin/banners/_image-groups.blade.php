{{-- Grupos de imágenes fijas con su vista previa y las acciones Reemplazar / Restaurar. $groups = grupos de BannerController::inventory(). --}}
@foreach ($groups as $group)
    <div class="flex flex-col gap-3">
        <div>
            <h3 class="text-sm font-extrabold uppercase tracking-[1px] text-admin-text">{{ $group['title'] }}</h3>
            @if ($group['where']) <p class="text-xs text-admin-text-secondary">{{ $group['where'] }}</p> @endif
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
            @foreach ($group['items'] as $image)
                <div class="flex flex-col overflow-hidden rounded-sm border border-admin-border">
                    {{-- La vista previa siempre se ve entera dentro de su recuadro (sin salirse de la tarjeta). --}}
                    <div class="relative h-36 overflow-hidden bg-admin-subtle">
                        @if ($image['url'])
                            <img src="{{ $image['url'] }}" alt="" loading="lazy" class="absolute inset-0 size-full object-contain p-1">
                        @else
                            <span class="absolute inset-0 grid place-items-center text-xs text-admin-text-muted">Sin imagen</span>
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
                                    <input type="file" name="image" accept="{{ in_array($image['key'], ['site_icon', 'site_logo'], true) ? '.png,.jpg,.jpeg,.webp,.svg,.ico' : '.png,.jpg,.jpeg,.webp' }}" class="sr-only" onchange="this.form.submit()">
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
