@extends('layouts.admin')

@section('title', 'Combos')

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Catálogo / Combos</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Combos</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">Promociones con precio cerrado que aparecen en la sección Combos de la tienda. Distintos de «Arma tu outfit», donde el cliente elige cada prenda.</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <form action="{{ route('admin.combos.import') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">
                        <img src="{{ asset('images/admin/icon-download.svg') }}" class="size-4" alt="">
                        Cargar catálogo base
                    </button>
                </form>
                <button type="button" data-combo-form-toggle class="flex items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                    <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                    Nuevo combo
                </button>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif
        @if (session('import_missing'))
            <div class="rounded-sm border border-admin-warning bg-admin-warning-soft px-4 py-3 text-sm text-admin-text">
                <p class="font-bold">Productos aún no vinculados</p>
                <p class="mt-1">Los combos ya se guardaron con su composición ZAZU. Sincroniza el inventario para crear las relaciones de productos.</p>
                <p class="mt-2 font-semibold">{{ collect(session('import_missing'))->unique()->implode(', ') }}</p>
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3 text-sm font-semibold text-admin-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section data-combo-form @class(['flex flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6', 'hidden' => ! $errors->any()])>
            <div>
                <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Agregar nuevo combo</h2>
                <p class="mt-1 text-sm text-admin-text-secondary">Si no subes una imagen, se usará la foto estándar del catálogo.</p>
            </div>

            <form action="{{ route('admin.combos.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5" novalidate>
                @csrf
                @include('admin.combos._fields', ['combo' => null, 'productRows' => []])
                <div class="flex items-center justify-end gap-3 border-t border-admin-border pt-4">
                    <button type="button" data-combo-form-toggle class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                    <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar combo</button>
                </div>
            </form>
        </section>

        <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
            <div class="flex items-center gap-6 overflow-x-auto border-b border-admin-border px-5">
                @foreach ([
                    ['key' => '', 'label' => 'Todos', 'count' => $counts['todos']],
                    ['key' => 'activos', 'label' => 'Activos', 'count' => $counts['activos']],
                    ['key' => 'borradores', 'label' => 'Borradores', 'count' => $counts['borradores']],
                ] as $t)
                    <a href="{{ route('admin.combos.index', array_filter(['estado' => $t['key'] ?: null, 'marca' => $selectedBrandId, 'search' => $search ?: null])) }}"
                       class="flex shrink-0 items-center gap-2 border-b-2 py-3.5 {{ $estado === $t['key'] ? 'border-admin-accent' : 'border-transparent' }}">
                        <span class="text-sm {{ $estado === $t['key'] ? 'font-bold text-admin-text' : 'font-medium text-admin-text-secondary' }}">{{ $t['label'] }}</span>
                        <span class="rounded-full bg-admin-hover px-2 py-0.5 text-xs font-bold text-admin-text-secondary">{{ $t['count'] }}</span>
                    </a>
                @endforeach
            </div>

            <form action="{{ route('admin.combos.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                @if ($estado !== '')
                    <input type="hidden" name="estado" value="{{ $estado }}">
                @endif
                <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                    <button type="submit" class="shrink-0" aria-label="Buscar">
                        <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                    </button>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Buscar por nombre, marca o producto incluido" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                </div>
                <select name="marca" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                    <option value="">Marca: todas</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) $selectedBrandId === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </form>

            @if ($combos->isEmpty())
                <div class="flex flex-col items-center gap-1 px-4 py-14 text-center">
                    <p class="text-[15px] font-bold text-admin-text">{{ $search !== '' ? 'No encontramos combos para "'.$search.'"' : 'No hay combos registrados' }}</p>
                    <p class="text-[13px] text-admin-text-secondary">Usa "Nuevo combo" o "Cargar catálogo base" para crear promociones.</p>
                </div>
            @else
                <div class="flex flex-wrap gap-4 px-5 pb-5">
                    @foreach ($combos as $combo)
                        @php($compareAt = $combo->compareAtPriceToShow())
                        <article class="flex w-128.5 gap-3.5 rounded-sm border border-admin-border bg-admin-surface p-4">
                            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="size-22 shrink-0 rounded-sm object-cover">
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-[15px] font-bold text-admin-text">{{ $combo->name }}</p>
                                    @if ($combo->is_draft)
                                        <x-admin.badge tone="neutral" label="Borrador" />
                                    @else
                                        <x-admin.badge :tone="$combo->status ? 'success' : 'neutral'" :label="$combo->status ? 'Activo' : 'Inactivo'" />
                                    @endif
                                </div>
                                <p class="truncate text-[13px] text-admin-text-secondary">
                                    {{ $combo->brand ?: 'Overshark' }} ·
                                    @forelse ($combo->displayItems() as $item)
                                        {{ $loop->first ? '' : ', ' }}{{ $item['cantidad'] }} × {{ $item['zazu'] }}{{ ! empty($item['grupo']) ? ' ('.$item['grupo'].' a elegir)' : '' }}
                                    @empty
                                        Composición pendiente
                                    @endforelse
                                </p>
                                <div class="flex items-center gap-2">
                                    @if ($combo->price !== null)
                                        <p class="text-base font-extrabold text-admin-text">S/ {{ number_format((float) $combo->price, 2) }}</p>
                                    @endif
                                    @if ($compareAt)
                                        <p class="text-[13px] text-admin-text-muted line-through">S/ {{ number_format($compareAt, 2) }}</p>
                                    @endif
                                </div>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="border border-admin-border-strong bg-admin-surface px-3 py-1.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Ver página</a>
                                    <a href="{{ route('admin.combos.edit', $combo) }}" class="border border-admin-border-strong bg-admin-surface px-3 py-1.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Editar</a>
                                    @if ($combo->is_draft)
                                        <form action="{{ route('admin.combos.publish', $combo) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="bg-admin-navy px-3 py-1.5 text-xs font-extrabold uppercase tracking-[1px] text-white">Publicar</button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.combos.toggle', $combo) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="border border-admin-border-strong bg-admin-surface px-3 py-1.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">{{ $combo->status ? 'Ocultar' : 'Activar' }}</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.combos.destroy', $combo) }}" method="POST" data-combo-delete-form data-combo-name="{{ $combo->name }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="border border-admin-danger px-3 py-1.5 text-xs font-extrabold uppercase tracking-[1px] text-admin-danger">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <dialog data-combo-delete-dialog class="w-[min(92vw,28rem)] rounded-sm border border-admin-border p-0 backdrop:bg-black/50">
        <div class="p-6">
            <h2 class="font-admin-display text-lg font-black uppercase text-admin-text">Eliminar combo</h2>
            <p class="mt-2 text-sm leading-6 text-admin-text-secondary">Se eliminará <strong data-combo-delete-name>este combo</strong> y su composición. Esta acción no se puede deshacer.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-combo-delete-cancel class="border border-admin-border-strong px-4 py-2.5 text-sm font-bold text-admin-text">Cancelar</button>
                <button type="button" data-combo-delete-confirm class="bg-admin-danger px-4 py-2.5 text-sm font-bold text-white">Eliminar</button>
            </div>
        </div>
    </dialog>
@endsection
