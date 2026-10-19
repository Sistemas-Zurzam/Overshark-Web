@extends('layouts.admin')

@section('title', 'Editar combo')

@section('content')
    <div class="mb-8">
        @php $isOutfitCombo = $combo->modality === 'Outfit'; @endphp
        <a href="{{ route($isOutfitCombo ? 'admin.outfits.index' : 'admin.combos.index') }}" class="text-sm font-bold text-cyan-700 hover:text-cyan-900">← Volver a {{ $isOutfitCombo ? 'Arma tu outfit' : 'combos' }}</a>
        <h1 class="mt-3 text-3xl font-black text-slate-950">Editar «{{ $combo->name }}»</h1>
        <p class="mt-2 max-w-2xl text-slate-500">Los cambios se ven al instante en la tienda y en «Arma tu outfit».</p>
    </div>

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

    <section data-combo-form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="mb-5 flex items-center gap-4">
            <img src="{{ $combo->imageUrl() }}" alt="" class="h-20 w-20 rounded-xl object-cover">
            <p class="text-sm text-slate-500">Imagen actual del combo.</p>
        </div>
        <form action="{{ route('admin.combos.update', $combo) }}" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
            @csrf
            @method('PUT')
            @include('admin.combos._fields', ['combo' => $combo, 'productRows' => $productRows, 'forceOutfit' => $isOutfitCombo])
            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route($isOutfitCombo ? 'admin.outfits.index' : 'admin.combos.index') }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-slate-400">Cancelar</a>
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar cambios</button>
            </div>
        </form>
    </section>
@endsection
