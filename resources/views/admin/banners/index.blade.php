@extends('layouts.admin')

@section('title', 'Banners')

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Contenido / Banners</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Banners</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">Imágenes del carrusel de portada de cada tienda.</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('web.home') }}" target="_blank" class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Ver tienda</a>
                <button type="button" data-banner-form-toggle class="flex items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                    <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                    Nuevo banner
                </button>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3 text-sm font-semibold text-admin-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section data-banner-form @class(['flex flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6', 'hidden' => ! $errors->any()])>
            <div>
                <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Agregar nuevo banner</h2>
                <p class="mt-1 text-sm text-admin-text-secondary">Recomendado: imagen horizontal de al menos 1600 × 700 px.</p>
            </div>

            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2">
                @csrf
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Marca</span>
                    <select name="brand_id" required class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $selectedBrand?->id) == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id') <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre</span>
                    <input type="text" name="name" value="{{ old('name') }}" required class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none" placeholder="Ej. Promoción de verano">
                    @error('name') <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Imagen del banner</span>
                    <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" required class="block w-full rounded-sm border border-admin-border-strong bg-admin-surface p-2 text-sm text-admin-text file:mr-3 file:border-0 file:bg-admin-navy file:px-3 file:py-2 file:text-xs file:font-extrabold file:uppercase file:text-white">
                    @error('image') <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Duración en segundos</span>
                    <input type="number" name="time" value="{{ old('time', 5) }}" min="1" max="60" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                    @error('time') <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Ajuste de imagen</span>
                    <select name="modo" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                        <option value="cover" @selected(old('modo', 'cover') === 'cover')>Cubrir todo el espacio</option>
                        <option value="contain" @selected(old('modo') === 'contain')>Mostrar imagen completa</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Posición de botones</span>
                    <select name="buttons_position" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                        <option value="center-left" @selected(old('buttons_position', 'center-left') === 'center-left')>Centro izquierda</option>
                        <option value="center" @selected(old('buttons_position') === 'center')>Centro</option>
                        <option value="bottom-left" @selected(old('buttons_position') === 'bottom-left')>Abajo izquierda</option>
                        <option value="bottom-center" @selected(old('buttons_position') === 'bottom-center')>Abajo centro</option>
                        <option value="bottom-right" @selected(old('buttons_position') === 'bottom-right')>Abajo derecha</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Vigente desde <span class="font-medium normal-case text-admin-text-muted">(opcional)</span></span>
                    <input type="date" name="starts_at" value="{{ old('starts_at') }}" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Vigente hasta <span class="font-medium normal-case text-admin-text-muted">(opcional)</span></span>
                    <input type="date" name="ends_at" value="{{ old('ends_at') }}" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                    @error('ends_at') <span class="mt-1 block text-xs font-semibold text-admin-danger">{{ $message }}</span> @enderror
                </label>

                <div class="lg:col-span-2 grid gap-4 lg:grid-cols-2">
                    @for ($index = 0; $index < 2; $index++)
                        <fieldset class="rounded-sm border border-admin-border bg-admin-subtle p-4">
                            <label class="flex items-center gap-2.5 text-sm font-bold text-admin-text">
                                <input type="checkbox" name="buttons[{{ $index }}][enabled]" value="1" @checked(old("buttons.$index.enabled", $index === 0)) class="size-4 rounded border-admin-border-strong text-admin-accent">
                                Botón {{ $index + 1 }}
                            </label>
                            <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                <input type="text" name="buttons[{{ $index }}][text]" value="{{ old("buttons.$index.text", $index === 0 ? 'Comprar ahora' : 'Ver catálogo') }}" class="rounded-sm border border-admin-border-strong bg-admin-surface px-3 py-2 text-sm text-admin-text outline-none" placeholder="Texto">
                                <input type="text" name="buttons[{{ $index }}][url]" value="{{ old("buttons.$index.url", $index === 0 ? '#productos' : '#combos') }}" class="rounded-sm border border-admin-border-strong bg-admin-surface px-3 py-2 text-sm text-admin-text outline-none" placeholder="URL o #seccion">
                                <input type="color" name="buttons[{{ $index }}][bg_color]" value="{{ old("buttons.$index.bg_color", $index === 0 ? '#111111' : '#ffffff') }}" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface p-1">
                                <input type="color" name="buttons[{{ $index }}][text_color]" value="{{ old("buttons.$index.text_color", $index === 0 ? '#ffffff' : '#111111') }}" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface p-1">
                                <select name="buttons[{{ $index }}][shape]" class="rounded-sm border border-admin-border-strong bg-admin-surface px-3 py-2 text-sm text-admin-text outline-none sm:col-span-2">
                                    <option value="square" @selected(old("buttons.$index.shape") === 'square')>Cuadrado</option>
                                    <option value="rounded" @selected(old("buttons.$index.shape", 'rounded') === 'rounded')>Borde redondeado</option>
                                    <option value="pill" @selected(old("buttons.$index.shape") === 'pill')>Pastilla</option>
                                </select>
                                <select name="buttons[{{ $index }}][size]" class="rounded-sm border border-admin-border-strong bg-admin-surface px-3 py-2 text-sm text-admin-text outline-none sm:col-span-2">
                                    <option value="sm" @selected(old("buttons.$index.size") === 'sm')>Pequeño</option>
                                    <option value="md" @selected(old("buttons.$index.size", 'md') === 'md')>Mediano</option>
                                    <option value="lg" @selected(old("buttons.$index.size") === 'lg')>Grande</option>
                                    <option value="xl" @selected(old("buttons.$index.size") === 'xl')>Extra grande</option>
                                </select>
                            </div>
                        </fieldset>
                    @endfor
                </div>

                <label class="flex items-center gap-2.5 text-sm font-bold text-admin-text">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="size-4 rounded border-admin-border-strong text-admin-accent">
                    Mostrar banner en la portada
                </label>

                <div class="flex items-center gap-3 lg:col-span-2 lg:justify-end">
                    <button type="button" data-banner-form-toggle class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                    <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar banner</button>
                </div>
            </form>
        </section>

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <div class="flex items-center gap-6 overflow-x-auto border-b border-admin-border px-5">
                @foreach ($brands as $brand)
                    <a href="{{ route('admin.banners.index', ['marca' => $brand->id]) }}"
                       class="flex shrink-0 items-center gap-2 border-b-2 py-3.5 {{ $selectedBrand?->id === $brand->id ? 'border-admin-accent' : 'border-transparent' }}">
                        <span class="text-sm {{ $selectedBrand?->id === $brand->id ? 'font-bold text-admin-text' : 'font-medium text-admin-text-secondary' }}">{{ $brand->name }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $counts[$brand->id] === 0 ? 'bg-admin-warning-soft text-admin-warning' : 'bg-admin-hover text-admin-text-secondary' }}">{{ $counts[$brand->id] }}</span>
                    </a>
                @endforeach
            </div>

            <p class="px-5 pt-4 text-[13px] text-admin-text-secondary">
                Tamaño recomendado: 1920 × 640 px (escritorio) y 800 × 1000 px (móvil). Máximo {{ \App\Models\Admin\BannerPortada::MAX_ACTIVE_PER_BRAND }} banners activos por marca.
            </p>

            @if ($drivesHome)
                @if (! $banners->contains(fn ($banner) => $banner->scheduleState() === 'activo'))
                    <div class="mx-5 mt-4 flex flex-col gap-4 rounded-sm border border-admin-warning bg-admin-warning-soft p-4 sm:flex-row sm:items-center">
                        <img src="{{ \App\Support\SiteContent::imageUrl('hero_hombre') }}" alt="" class="h-24 w-auto rounded-sm bg-[#071226] object-contain">
                        <div>
                            <p class="text-sm font-bold text-admin-text">Ahora la portada de Hombre muestra la imagen predeterminada</p>
                            <p class="mt-1 text-[13px] text-admin-text-secondary">No hay banners activos, así que la tienda usa el modelo de siempre sobre el fondo del mar. Agrega un banner para reemplazarla; o cambia ese modelo en «Imágenes fijas», más abajo.</p>
                        </div>
                    </div>
                @endif
            @else
                <div class="mx-5 mt-4 rounded-sm border border-admin-border bg-admin-subtle p-4 text-[13px] text-admin-text-secondary">
                    <p class="font-bold text-admin-text">Estos banners todavía no se muestran en la tienda de {{ $selectedBrand?->name }}</p>
                    <p class="mt-1">El carrusel de portada solo funciona en el inicio de Overshark Hombre. La portada de {{ $selectedBrand?->name }} es una imagen fija: puedes reemplazarla en «Imágenes fijas», más abajo.</p>
                </div>
            @endif

            <div data-banner-grid data-banner-reorder-url="{{ route('admin.banners.reorder') }}" class="flex flex-wrap items-start gap-4 p-5">
                @foreach ($banners as $banner)
                    @php
                        $state = $banner->scheduleState();
                        $tone = match ($state) {
                            'activo' => 'success',
                            'programado' => 'info',
                            default => 'neutral',
                        };
                        $stateLabel = match ($state) {
                            'activo' => 'Activo',
                            'programado' => 'Programado',
                            'finalizado' => 'Finalizado',
                            default => 'Inactivo',
                        };
                    @endphp
                    <article draggable="true" data-banner-card data-banner-id="{{ $banner->id }}" class="flex w-85.5 cursor-move flex-col rounded-sm border border-admin-border bg-admin-surface">
                        <div class="relative h-28.5 w-full {{ $state === 'finalizado' ? 'opacity-50' : '' }} bg-admin-subtle" data-banner-button-preview>
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->name }}" class="absolute inset-0 size-full object-{{ $banner->modo === 'contain' ? 'contain' : 'cover' }} pointer-events-none">
                            @foreach ($banner->buttons ?? [] as $index => $button)
                                @php
                                    $radiusClass = ['square' => 'rounded-none', 'rounded' => 'rounded-lg', 'pill' => 'rounded-full'][$button['shape'] ?? 'rounded'] ?? 'rounded-lg';
                                    $sizeClass = ['sm' => 'px-3 py-1.5 text-[10px]', 'md' => 'px-5 py-2 text-xs', 'lg' => 'px-7 py-3 text-sm', 'xl' => 'px-10 py-4 text-base'][$button['size'] ?? 'md'] ?? 'px-5 py-2 text-xs';
                                @endphp
                                <button type="button" data-banner-button-drag="{{ $index }}" class="absolute z-10 -translate-x-1/2 -translate-y-1/2 cursor-move {{ $radiusClass }} {{ $sizeClass }} font-black uppercase shadow-lg" style="left: {{ $button['x'] ?? 20 }}%; top: {{ $button['y'] ?? 50 }}%; background-color: {{ $button['bg_color'] ?? '#111111' }}; color: {{ $button['text_color'] ?? '#ffffff' }};">
                                    {{ $button['text'] ?? 'Boton' }}
                                </button>
                            @endforeach
                        </div>
                        <div class="flex flex-col gap-2 p-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="grid size-5.5 shrink-0 place-items-center rounded-sm bg-admin-hover text-[11px] font-extrabold text-admin-text-secondary">{{ $loop->iteration }}</span>
                                    <p class="truncate text-sm font-bold text-admin-text">{{ $banner->name }}</p>
                                </div>
                                <x-admin.badge :tone="$tone" :label="$stateLabel" />
                            </div>
                            <p class="text-xs text-admin-text-secondary">Enlace: {{ $banner->primaryLink() ?? '—' }}</p>
                            <div class="flex items-center gap-1.5">
                                <img src="{{ asset('images/admin/icon-clock-muted.svg') }}" class="size-3.5" alt="">
                                <p class="text-xs text-admin-text-muted">{{ $banner->scheduleLabel() }}</p>
                            </div>
                            <div class="mt-1 flex gap-2">
                                <button type="button" data-banner-edit-toggle="banner-edit-{{ $banner->id }}" class="flex-1 border border-admin-border-strong bg-admin-surface px-3 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Editar</button>
                                <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="border border-admin-border-strong bg-admin-surface px-3 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">{{ $banner->status ? 'Ocultar' : 'Activar' }}</button>
                                </form>
                                <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('¿Eliminar este banner?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="border border-admin-danger px-3 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>

                        <form id="banner-edit-{{ $banner->id }}" action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data" class="hidden flex-col gap-3 border-t border-admin-border bg-admin-subtle p-4">
                            @csrf
                            @method('PATCH')
                            <label class="block">
                                <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Marca</span>
                                <select name="brand_id" required class="h-9 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}" @selected($banner->brand_id === $brand->id)>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre</span>
                                <input type="text" name="name" value="{{ $banner->name }}" required class="h-9 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Cambiar imagen</span>
                                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" class="block w-full rounded-sm border border-admin-border-strong bg-admin-surface p-1.5 text-xs text-admin-text">
                            </label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Duración (s)</span>
                                    <input type="number" name="time" value="{{ $banner->time ?? 5 }}" min="1" max="60" class="h-9 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Ajuste</span>
                                    <select name="modo" class="h-9 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                                        <option value="cover" @selected($banner->modo === 'cover')>Cubrir</option>
                                        <option value="contain" @selected($banner->modo === 'contain')>Completa</option>
                                    </select>
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Desde</span>
                                    <input type="date" name="starts_at" value="{{ $banner->starts_at?->format('Y-m-d') }}" class="h-9 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Hasta</span>
                                    <input type="date" name="ends_at" value="{{ $banner->ends_at?->format('Y-m-d') }}" class="h-9 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                                </label>
                            </div>
                            <input type="hidden" name="buttons_position" value="{{ $banner->buttons_position }}">
                            @for ($index = 0; $index < 2; $index++)
                                @php $button = $banner->buttons[$index] ?? []; @endphp
                                <input type="hidden" name="buttons[{{ $index }}][enabled]" value="{{ ! empty($button) ? '1' : '' }}">
                                <input type="hidden" name="buttons[{{ $index }}][text]" value="{{ $button['text'] ?? '' }}">
                                <input type="hidden" name="buttons[{{ $index }}][url]" value="{{ $button['url'] ?? '' }}">
                                <input type="hidden" name="buttons[{{ $index }}][bg_color]" value="{{ $button['bg_color'] ?? '#111111' }}">
                                <input type="hidden" name="buttons[{{ $index }}][text_color]" value="{{ $button['text_color'] ?? '#ffffff' }}">
                                <input type="hidden" name="buttons[{{ $index }}][shape]" value="{{ $button['shape'] ?? 'rounded' }}">
                                <input type="hidden" name="buttons[{{ $index }}][size]" value="{{ $button['size'] ?? 'md' }}">
                                <input type="hidden" name="buttons[{{ $index }}][x]" value="{{ $button['x'] ?? 20 }}" data-banner-button-x="{{ $index }}">
                                <input type="hidden" name="buttons[{{ $index }}][y]" value="{{ $button['y'] ?? 50 }}" data-banner-button-y="{{ $index }}">
                            @endfor
                            <label class="flex items-center gap-2 text-sm font-bold text-admin-text">
                                <input type="hidden" name="status" value="0">
                                <input type="checkbox" name="status" value="1" @checked($banner->status) class="size-4 rounded border-admin-border-strong text-admin-accent">
                                Mostrar en la portada
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" data-banner-edit-toggle="banner-edit-{{ $banner->id }}" class="flex-1 border border-admin-border-strong bg-admin-surface px-3 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                                <button type="submit" class="flex-1 bg-admin-navy px-3 py-2 text-xs font-extrabold uppercase tracking-[1px] text-white">Actualizar</button>
                            </div>
                        </form>
                    </article>
                @endforeach

                <button type="button" data-banner-form-toggle class="flex h-60 w-85.5 flex-col items-center justify-center gap-2 rounded-sm border border-dashed border-admin-border-strong bg-admin-subtle">
                    <img src="{{ asset('images/admin/icon-upload.svg') }}" class="size-5.5" alt="">
                    <span class="text-sm font-bold text-admin-text">Agregar banner</span>
                    <span class="text-xs text-admin-text-muted">Arrastra una imagen aquí</span>
                </button>
            </div>
        </div>

        @include('admin.banners._inventory', ['inventory' => $inventory])
    </div>
@endsection
