@extends('layouts.admin')

@section('title', 'Arma tu outfit')

{{-- Combos de la página pública «Arma tu outfit» (modalidad Outfit). Separados de los combos del menú. --}}

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Contenido comercial</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Arma tu outfit</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Combos que el cliente arma prenda por prenda en <a href="{{ route('web.outfit') }}" target="_blank" class="font-bold text-cyan-700 underline">la página Arma tu outfit</a>. No aparecen en el menú de Combos de la tienda.</p>
        </div>
        <button type="button" data-combo-form-toggle class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Nuevo combo de outfit</button>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">{{ session('status') }}</div>
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

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <h2 class="text-lg font-black text-slate-950">Flyer de la página</h2>
        <p class="mt-1 text-sm text-slate-500">Es la imagen grande que aparece arriba en «Arma tu outfit». Recomendado: horizontal, unos 1600 × 900 px.</p>
        <div class="mt-4 grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:items-center">
            <img src="{{ \App\Support\OutfitFlyer::url() }}" alt="Flyer actual" class="w-full rounded-xl border border-slate-200 object-cover">
            <div class="space-y-3">
                <form action="{{ route('admin.outfits.flyer') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="flyer" accept=".png,.jpg,.jpeg,.webp" required class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
                    <p class="text-xs text-slate-400">PNG, JPG o WEBP; máximo 8 MB.</p>
                    <button type="submit" class="w-full rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Cambiar flyer</button>
                </form>
                @if (\App\Support\OutfitFlyer::isCustom())
                    <form action="{{ route('admin.outfits.flyer') }}" method="POST">
                        @csrf
                        <input type="hidden" name="reset" value="1">
                        <button type="submit" class="w-full rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-red-300 hover:text-red-600">Volver al flyer original</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section data-combo-form @class(['mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6', 'hidden' => ! $errors->any()])>
        <h2 class="text-xl font-black text-slate-950">Nuevo combo de outfit</h2>
        <form action="{{ route('admin.combos.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-6" novalidate>
            @csrf
            @include('admin.combos._fields', ['combo' => null, 'productRows' => [], 'forceOutfit' => true])
            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" data-combo-form-toggle class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-slate-400">Cancelar</button>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar combo</button>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-lg font-black text-slate-950">Combos de outfit</h2>
            <p class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-500">{{ $combos->count() }} {{ $combos->count() === 1 ? 'registro' : 'registros' }}</p>
        </div>

        @if ($combos->isEmpty())
            <div class="mt-6 grid min-h-48 place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-center">
                <p class="font-bold text-slate-700">Aún no hay combos de outfit. Crea el primero con «Nuevo combo de outfit».</p>
            </div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($combos as $combo)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-black text-slate-950">{{ $combo->name }}</h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $combo->notes }}</p>
                            </div>
                            <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $combo->status, 'bg-slate-100 text-slate-500' => ! $combo->status])>{{ $combo->status ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        @if ($combo->price !== null)
                            <p class="mt-4 text-2xl font-black text-slate-950">S/ {{ number_format((float) $combo->price, 2) }}</p>
                        @endif
                        <ul class="mt-4 space-y-1.5 text-sm text-slate-600">
                            @forelse ($combo->displayItems()->groupBy(fn ($i) => $i['grupo'] ?? $i['zazu']) as $garment => $items)
                                <li><b class="text-slate-950">{{ $items->first()['grupo'] ?? \Illuminate\Support\Str::title(mb_strtolower($garment)) }}:</b> {{ $items->pluck('zazu')->implode(' / ') }}</li>
                            @empty
                                <li class="text-slate-400">Sin prendas</li>
                            @endforelse
                        </ul>
                        <div class="mt-5 flex flex-wrap gap-2">
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
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <dialog data-combo-delete-dialog class="w-[min(92vw,28rem)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/50">
        <div class="p-6">
            <h2 class="text-lg font-black text-slate-950">Eliminar combo</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Se eliminará <strong data-combo-delete-name>este combo</strong>. Esta acción no se puede deshacer.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-combo-delete-cancel class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancelar</button>
                <button type="button" data-combo-delete-confirm class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700">Eliminar</button>
            </div>
        </div>
    </dialog>
@endsection
