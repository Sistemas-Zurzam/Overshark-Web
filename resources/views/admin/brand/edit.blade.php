@extends('layouts.admin')

@section('title', 'Identidad visual')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Configuración</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Logo e ícono</h1>
        <p class="mt-2 text-slate-500">Carga los recursos que identifican a Overshark en la tienda y el navegador.</p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('admin.brand.update') }}" method="POST" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-2">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Logo principal</h2>
                    <p class="mt-1 text-sm text-slate-500">Se muestra en el menú de la tienda y del panel.</p>
                </div>
                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">PNG, JPG, WEBP o SVG</span>
            </div>

            <div class="mt-6 grid min-h-44 place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
                @if ($brand->logoUrl())
                    <img src="{{ $brand->logoUrl() }}" alt="Logo actual de Overshark" class="max-h-24 max-w-full object-contain">
                @else
                    <span class="text-2xl font-black tracking-[0.2em] text-slate-800">OVERSHARK</span>
                @endif
            </div>

            <label class="mt-5 block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Seleccionar logo</span>
                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
            </label>
            @error('logo')
                <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Ícono del sitio</h2>
                    <p class="mt-1 text-sm text-slate-500">Se muestra como favicon en la pestaña del navegador.</p>
                </div>
                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">PNG, ICO o SVG</span>
            </div>

            <div class="mt-6 grid min-h-44 place-items-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
                @if ($brand->iconUrl())
                    <img src="{{ $brand->iconUrl() }}" alt="Ícono actual de Overshark" class="h-24 w-24 object-contain">
                @else
                    <span class="grid h-24 w-24 place-items-center rounded-3xl bg-cyan-500 text-2xl font-black text-slate-950">OS</span>
                @endif
            </div>

            <label class="mt-5 block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Seleccionar ícono</span>
                <input type="file" name="icon" accept=".png,.ico,.svg" class="block w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:font-bold file:text-white hover:file:bg-cyan-600">
            </label>
            @error('icon')
                <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <div class="xl:col-span-2">
            <button type="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">
                Guardar identidad visual
            </button>
        </div>
    </form>

    {{-- Botón flotante de WhatsApp de la tienda (layouts/web.blade.php). --}}
    <form action="{{ route('admin.brand.whatsapp') }}" method="POST" class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-950">Botón de WhatsApp</h2>
                <p class="mt-1 text-sm text-slate-500">Botón flotante a la derecha en toda la tienda. Si dejas el número vacío, el botón no se muestra.</p>
            </div>
            <span @class([
                'rounded-full px-3 py-1 text-xs font-bold',
                'bg-emerald-50 text-emerald-700' => $brand->whatsappUrl(),
                'bg-slate-100 text-slate-500' => ! $brand->whatsappUrl(),
            ])>{{ $brand->whatsappUrl() ? 'Visible en la tienda' : 'Oculto' }}</span>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Número de WhatsApp</span>
                <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number', $brand->whatsapp_number) }}" placeholder="999 888 777 o +51 999 888 777" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                <span class="mt-1.5 block text-xs text-slate-500">Un celular peruano de 9 dígitos se completa con +51.</span>
                @error('whatsapp_number')
                    <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span>
                @enderror
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-bold text-slate-700">Mensaje inicial</span>
                <input type="text" name="whatsapp_message" value="{{ old('whatsapp_message', $brand->whatsapp_message) }}" placeholder="{{ \App\Models\BrandSetting::WHATSAPP_DEFAULT_MESSAGE }}" maxlength="300" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                <span class="mt-1.5 block text-xs text-slate-500">El texto que aparece escrito al abrir el chat. Vacío = mensaje por defecto.</span>
                @error('whatsapp_message')
                    <span class="mt-2 block text-sm font-semibold text-red-600">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <button type="submit" class="mt-6 rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar WhatsApp</button>
    </form>
@endsection
