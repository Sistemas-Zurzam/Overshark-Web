@extends('layouts.admin')

@section('title', 'Página Nosotros')

@php
    $card = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6';
    $h2 = 'text-lg font-black text-slate-950';
    $help = 'mt-1 text-sm text-slate-500';
@endphp

@section('content')
    <div class="mb-8 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Contenido</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Página Nosotros</h1>
            <p class="mt-2 max-w-2xl text-slate-500">Edita los textos y las fotos de <a href="{{ route('web.nosotros') }}" target="_blank" class="font-bold text-cyan-700 underline">la página Nosotros</a>. Si no subes una foto, se usa la del diseño.</p>
        </div>
        <form action="{{ route('admin.about.reset') }}" method="POST" onsubmit="return confirm('¿Restaurar todos los textos originales? Las fotos subidas se conservan.')">
            @csrf
            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-red-300 hover:text-red-600">Restaurar textos originales</button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">Revisa los campos marcados en rojo.</div>
    @endif

    <form action="{{ route('admin.about.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
        @csrf
        @method('PUT')

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">1. Portada</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div class="grid content-start gap-4 md:grid-cols-2">
                    @include('admin.about._input', ['name' => 'hero.tag', 'label' => 'Etiqueta', 'value' => $c['hero']['tag']])
                    @include('admin.about._input', ['name' => 'hero.title', 'label' => 'Título', 'value' => $c['hero']['title']])
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'hero.text', 'label' => 'Descripción', 'value' => $c['hero']['text'], 'area' => true])</div>
                </div>
                @include('admin.about._image', ['key' => 'portada', 'image' => $images['portada']])
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">2. Nuestra historia</h2>
            <p class="{{ $help }}">El año y el párrafo de origen son opcionales: si los dejas vacíos no se muestran. Separa los párrafos del texto con una línea en blanco.</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div class="grid content-start gap-4 md:grid-cols-2">
                    @include('admin.about._input', ['name' => 'historia.tag', 'label' => 'Etiqueta', 'value' => $c['historia']['tag']])
                    @include('admin.about._input', ['name' => 'historia.title', 'label' => 'Título', 'value' => $c['historia']['title']])
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'historia.text', 'label' => 'Texto', 'value' => $c['historia']['text'], 'area' => true])</div>
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'historia.origen', 'label' => 'Origen de la marca', 'hint' => '(opcional)', 'value' => $c['historia']['origen'], 'area' => true])</div>
                    @include('admin.about._input', ['name' => 'historia.year', 'label' => 'Año de fundación', 'hint' => '(opcional, sello «Desde»)', 'value' => $c['historia']['year']])
                    @include('admin.about._input', ['name' => 'historia.button', 'label' => 'Botón (lleva a la Tienda)', 'value' => $c['historia']['button']])
                    @include('admin.about._input', ['name' => 'historia.image_alt_1', 'label' => 'Descripción foto grande', 'value' => $c['historia']['image_alt_1']])
                    @include('admin.about._input', ['name' => 'historia.image_alt_2', 'label' => 'Descripción foto pequeña', 'value' => $c['historia']['image_alt_2']])
                </div>
                <div class="space-y-4">
                    @include('admin.about._image', ['key' => 'historia_1', 'image' => $images['historia_1']])
                    @include('admin.about._image', ['key' => 'historia_2', 'image' => $images['historia_2']])
                </div>
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">3. Nuestro ADN</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @include('admin.about._input', ['name' => 'adn.title', 'label' => 'Título', 'value' => $c['adn']['title']])
                @include('admin.about._input', ['name' => 'adn.text', 'label' => 'Descripción', 'value' => $c['adn']['text']])
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                @foreach ($c['adn']['items'] as $i => $item)
                    <div class="space-y-3 rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-600">Valor {{ sprintf('%02d', $i + 1) }}</p>
                        @include('admin.about._input', ['name' => "adn.items.{$i}.title", 'label' => 'Palabra', 'value' => $item['title']])
                        @include('admin.about._input', ['name' => "adn.items.{$i}.text", 'label' => 'Texto', 'value' => $item['text'], 'area' => true])
                    </div>
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">4. Proceso de fabricación</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @include('admin.about._input', ['name' => 'proceso.tag', 'label' => 'Etiqueta', 'value' => $c['proceso']['tag']])
                @include('admin.about._input', ['name' => 'proceso.title', 'label' => 'Título', 'value' => $c['proceso']['title']])
                @include('admin.about._input', ['name' => 'proceso.text', 'label' => 'Descripción', 'value' => $c['proceso']['text']])
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($c['proceso']['steps'] as $i => $step)
                    <div class="space-y-3 rounded-2xl border border-slate-200 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-cyan-600">Paso {{ sprintf('%02d', $i + 1) }}</p>
                        @include('admin.about._image', ['key' => 'paso_'.($i + 1), 'image' => $images['paso_'.($i + 1)]])
                        @include('admin.about._input', ['name' => "proceso.steps.{$i}.title", 'label' => 'Título', 'value' => $step['title']])
                        @include('admin.about._input', ['name' => "proceso.steps.{$i}.text", 'label' => 'Texto', 'value' => $step['text'], 'area' => true])
                        @include('admin.about._input', ['name' => "proceso.steps.{$i}.image_alt", 'label' => 'Descripción de la foto', 'value' => $step['image_alt']])
                    </div>
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">5. Datos clave</h2>
            <p class="{{ $help }}">Cuatro datos en grande (por ejemplo «S/ 14») con su explicación.</p>
            <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($c['datos'] as $i => $dato)
                    <div class="space-y-3 rounded-2xl border border-slate-200 p-4">
                        @include('admin.about._input', ['name' => "datos.{$i}.value", 'label' => 'Dato '.($i + 1), 'value' => $dato['value']])
                        @include('admin.about._input', ['name' => "datos.{$i}.label", 'label' => 'Explicación', 'value' => $dato['label']])
                    </div>
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">6. Nuestras marcas</h2>
            <p class="{{ $help }}">Cada tarjeta lleva a la tienda de esa marca.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @include('admin.about._input', ['name' => 'marcas.tag', 'label' => 'Etiqueta', 'value' => $c['marcas']['tag']])
                @include('admin.about._input', ['name' => 'marcas.title', 'label' => 'Título', 'value' => $c['marcas']['title']])
                @include('admin.about._input', ['name' => 'marcas.text', 'label' => 'Descripción', 'value' => $c['marcas']['text']])
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                @foreach (['marca_overshark', 'marca_girls', 'marca_bravos'] as $key)
                    @include('admin.about._image', ['key' => $key, 'image' => $images[$key]])
                @endforeach
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">7. Comunidad</h2>
            <p class="{{ $help }}">La cuenta y los botones de redes solo se muestran cuando los completas.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @include('admin.about._input', ['name' => 'comunidad.title', 'label' => 'Título', 'value' => $c['comunidad']['title']])
                @include('admin.about._input', ['name' => 'comunidad.handle', 'label' => 'Cuenta para etiquetar', 'hint' => '(ej. @overshark.pe)', 'value' => $c['comunidad']['handle']])
                @include('admin.about._input', ['name' => 'comunidad.instagram', 'label' => 'Enlace de Instagram', 'hint' => '(https://…)', 'value' => $c['comunidad']['instagram']])
                @include('admin.about._input', ['name' => 'comunidad.tiktok', 'label' => 'Enlace de TikTok', 'hint' => '(https://…)', 'value' => $c['comunidad']['tiktok']])
                @include('admin.about._input', ['name' => 'comunidad.facebook', 'label' => 'Enlace de Facebook', 'hint' => '(https://…)', 'value' => $c['comunidad']['facebook']])
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">SEO</h2>
            <div class="mt-4">@include('admin.about._input', ['name' => 'seo_description', 'label' => 'Descripción para Google', 'hint' => '(máx. 300 caracteres)', 'value' => $c['seo_description'], 'area' => true])</div>
        </section>

        <div class="sticky bottom-0 -mx-1 flex justify-end gap-3 border-t border-slate-200 bg-slate-100/95 px-1 py-4 backdrop-blur">
            <a href="{{ route('web.nosotros') }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">Ver página</a>
            <button type="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-cyan-600">Guardar cambios</button>
        </div>
    </form>
@endsection
