@extends('layouts.admin')

@section('title', 'Combos')

@section('content')

    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Contenido comercial</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Combos</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Administra promociones, precios, cantidades y productos que aparecen en el menú público.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form action="{{ route('admin.combos.import') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-cyan-500 hover:text-cyan-700">
                    Cargar catálogo base
                </button>
            </form>
            <button type="button" data-combo-form-toggle class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
                Nuevo combo
            </button>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('import_missing'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900" role="status">
            <p class="font-black">Productos aún no vinculados</p>
            <p class="mt-1">Los combos ya se guardaron con su composición ZAZU. Sincroniza el inventario para crear las relaciones de productos.</p>
            <p class="mt-2 font-semibold">{{ collect(session('import_missing'))->unique()->implode(', ') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" role="alert">
            <p class="font-black">Revisa los datos del combo</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section data-combo-form @class([
        'mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6',
        'hidden' => ! $errors->any(),
    ])>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-cyan-600">Alta manual</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Agregar nuevo combo</h2>
                <p class="mt-1 text-sm text-slate-500">Si no subes una imagen, se usará la foto estándar del catálogo.</p>
            </div>
        </div>

        <form action="{{ route('admin.combos.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-6" novalidate>
            @csrf

            @include('admin.combos._fields', ['combo' => null, 'productRows' => []])

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" data-combo-form-toggle class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-slate-400">Cancelar</button>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar combo</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-950">Catálogo de combos</h2>
                <p class="mt-1 text-sm text-slate-500">Cada tarjeta conserva el precio y la composición definida en tu matriz.</p>
            </div>
            <p class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">{{ $combos->count() }} {{ $combos->count() === 1 ? 'registro' : 'registros' }}</p>
        </div>

        <form action="{{ route('admin.combos.index') }}" method="GET" class="mt-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-end" role="search">
            <label class="min-w-0 flex-1">
                <span class="mb-2 block text-sm font-bold text-slate-700">Buscar combos</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nombre, marca, modalidad o producto incluido" autocomplete="off" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" aria-label="Buscar combos">
            </label>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600 focus:outline-none focus:ring-4 focus:ring-cyan-100">Buscar</button>
                @if ($search !== '')
                    <a href="{{ route('admin.combos.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-slate-500 hover:text-slate-950 focus:outline-none focus:ring-4 focus:ring-cyan-100">Limpiar</a>
                @endif
            </div>
        </form>

        @if ($combos->isEmpty())
            <div class="mt-6 grid min-h-64 place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-center">
                <div>
                    @if ($search !== '')
                        <p class="font-bold text-slate-700">No encontramos combos para “{{ $search }}”.</p>
                        <p class="mt-1 text-sm text-slate-400">Prueba con otro nombre, marca, modalidad o producto incluido.</p>
                        <a href="{{ route('admin.combos.index') }}" class="mt-4 inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-cyan-500 hover:text-cyan-700">Ver todos los combos</a>
                    @else
                    <p class="font-bold text-slate-700">No hay combos registrados.</p>
                    <p class="mt-1 text-sm text-slate-400">Usa “Cargar catálogo base” para crear las promociones de la matriz.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($combos as $combo)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                            <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="h-full w-full object-cover">
                            <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-slate-700">{{ $combo->brand ?: 'Overshark' }}</span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-black text-slate-950">{{ $combo->name }}</h2>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-cyan-700">{{ $combo->modality === 'Outfit' ? 'Arma tu outfit' : ($combo->modality ?: 'Promoción') }}</p>
                                </div>
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $combo->status, 'bg-slate-100 text-slate-500' => ! $combo->status])>{{ $combo->status ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                            @if ($combo->price !== null)
                                <p class="mt-4 text-2xl font-black text-slate-950">S/ {{ number_format((float) $combo->price, 2) }}</p>
                            @endif
                            <p class="mt-1 text-xs font-semibold text-slate-400">{{ $combo->quantityLabel() }}</p>
                            <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                                @forelse ($combo->displayItems() as $item)
                                    <li class="flex items-start justify-between gap-3"><span>@if (! empty($item['grupo']))<b class="text-slate-950">{{ $item['grupo'] }}:</b> @endif{{ $item['zazu'] }}</span><strong class="text-slate-950">×{{ $item['cantidad'] }}</strong></li>
                                @empty
                                    <li class="text-slate-400">Composición pendiente</li>
                                @endforelse
                            </ul>
                            @if ($combo->notes)
                                <p class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-900">{{ $combo->notes }}</p>
                            @endif
                            <div class="mt-5 flex flex-wrap gap-2">
                                <a href="{{ $combo->url ?: route('web.combos.show', $combo) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-300 hover:text-cyan-700">Ver página</a>
                                <a href="{{ route('admin.combos.edit', $combo) }}" class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white transition hover:bg-cyan-600">Editar</a>
                                <form action="{{ route('admin.combos.toggle', $combo) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ $combo->status ? 'Ocultar' : 'Activar' }}</button>
                                </form>
                                <form action="{{ route('admin.combos.destroy', $combo) }}" method="POST" data-combo-delete-form data-combo-name="{{ $combo->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <dialog data-combo-delete-dialog class="w-[min(92vw,28rem)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/50">
        <div class="p-6">
            <h2 class="text-lg font-black text-slate-950">Eliminar combo</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Se eliminará <strong data-combo-delete-name>este combo</strong> y su composición. Esta acción no se puede deshacer.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-combo-delete-cancel class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancelar</button>
                <button type="button" data-combo-delete-confirm class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700">Eliminar</button>
            </div>
        </div>
    </dialog>
@endsection
