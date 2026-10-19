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
            <p class="mt-2 max-w-2xl text-slate-500">Edita los textos y las fotos de <a href="{{ route('web.nosotros') }}" target="_blank" class="font-bold text-cyan-700 underline">la página Nosotros</a>. En cada título, el «texto destacado» se muestra en azul al final. Las fotos se muestran en blanco y negro.</p>
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
                <div class="grid gap-4 md:grid-cols-2">
                    @include('admin.about._input', ['name' => 'hero.tag', 'label' => 'Etiqueta', 'value' => $c['hero']['tag']])
                    @include('admin.about._input', ['name' => 'hero.button', 'label' => 'Botón', 'value' => $c['hero']['button']])
                    @include('admin.about._input', ['name' => 'hero.title', 'label' => 'Título', 'value' => $c['hero']['title']])
                    @include('admin.about._input', ['name' => 'hero.highlight', 'label' => 'Texto destacado', 'hint' => '(azul)', 'value' => $c['hero']['highlight']])
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'hero.text', 'label' => 'Descripción', 'value' => $c['hero']['text'], 'area' => true])</div>
                    @foreach ($c['hero']['attributes'] as $i => $attribute)
                        @include('admin.about._input', ['name' => "hero.attributes.{$i}", 'label' => 'Atributo '.($i + 1), 'value' => $attribute])
                    @endforeach
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'hero.image_alt', 'label' => 'Descripción de la foto', 'hint' => '(para accesibilidad)', 'value' => $c['hero']['image_alt']])</div>
                </div>
                @include('admin.about._image', ['key' => 'portada', 'image' => $images['portada']])
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">2. Nuestra esencia</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div class="grid gap-4 md:grid-cols-2">
                    @include('admin.about._input', ['name' => 'esencia.tag', 'label' => 'Etiqueta', 'value' => $c['esencia']['tag']])
                    @include('admin.about._input', ['name' => 'esencia.image_alt', 'label' => 'Descripción de la foto', 'value' => $c['esencia']['image_alt']])
                    @include('admin.about._input', ['name' => 'esencia.title', 'label' => 'Título', 'value' => $c['esencia']['title']])
                    @include('admin.about._input', ['name' => 'esencia.highlight', 'label' => 'Texto destacado', 'hint' => '(azul)', 'value' => $c['esencia']['highlight']])
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'esencia.text', 'label' => 'Texto', 'value' => $c['esencia']['text'], 'area' => true])</div>
                </div>
                @include('admin.about._image', ['key' => 'esencia', 'image' => $images['esencia']])
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">3. Proceso de fabricación</h2>
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
            <h2 class="{{ $h2 }}">4. Control de calidad</h2>
            <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div class="grid gap-4 md:grid-cols-2">
                    @include('admin.about._input', ['name' => 'calidad.tag', 'label' => 'Etiqueta', 'value' => $c['calidad']['tag']])
                    @include('admin.about._input', ['name' => 'calidad.image_alt', 'label' => 'Descripción de la foto', 'value' => $c['calidad']['image_alt']])
                    @include('admin.about._input', ['name' => 'calidad.title', 'label' => 'Título', 'value' => $c['calidad']['title']])
                    @include('admin.about._input', ['name' => 'calidad.highlight', 'label' => 'Texto destacado', 'hint' => '(azul)', 'value' => $c['calidad']['highlight']])
                    <div class="md:col-span-2">@include('admin.about._input', ['name' => 'calidad.text', 'label' => 'Texto', 'value' => $c['calidad']['text'], 'area' => true])</div>
                    @foreach ($c['calidad']['points'] as $i => $point)
                        @include('admin.about._input', ['name' => "calidad.points.{$i}", 'label' => 'Punto '.($i + 1), 'value' => $point])
                    @endforeach
                </div>
                @include('admin.about._image', ['key' => 'calidad', 'image' => $images['calidad']])
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">5. Experiencia conectada</h2>
            <p class="{{ $help }}">Los botones llevan a la Tienda y a Arma tu outfit.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @include('admin.about._input', ['name' => 'conectada.tag', 'label' => 'Etiqueta', 'value' => $c['conectada']['tag']])
                <span></span>
                @include('admin.about._input', ['name' => 'conectada.title', 'label' => 'Título', 'value' => $c['conectada']['title']])
                @include('admin.about._input', ['name' => 'conectada.highlight', 'label' => 'Texto destacado', 'hint' => '(azul)', 'value' => $c['conectada']['highlight']])
                @include('admin.about._input', ['name' => 'conectada.text', 'label' => 'Texto', 'value' => $c['conectada']['text'], 'area' => true])
                @include('admin.about._input', ['name' => 'conectada.note', 'label' => 'Nota', 'hint' => '(opcional)', 'value' => $c['conectada']['note'], 'area' => true])
                @include('admin.about._input', ['name' => 'conectada.button_collection', 'label' => 'Botón de la colección', 'value' => $c['conectada']['button_collection']])
                @include('admin.about._input', ['name' => 'conectada.button_outfit', 'label' => 'Botón de Arma tu outfit', 'value' => $c['conectada']['button_outfit']])
                <label class="flex items-center gap-3 text-sm font-bold text-slate-700 md:col-span-2">
                    <input type="hidden" name="conectada[show_products]" value="0">
                    <input type="checkbox" name="conectada[show_products]" value="1" @checked(old('conectada.show_products', $c['conectada']['show_products'])) class="h-5 w-5 rounded border-slate-300 text-cyan-600">
                    Mostrar 3 prendas de la colección (las de más stock)
                </label>
            </div>
        </section>

        <section class="{{ $card }}">
            <h2 class="{{ $h2 }}">6. Cierre</h2>
            <p class="{{ $help }}">El botón principal lleva a la Tienda; el secundario, al contacto del pie de página.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @include('admin.about._input', ['name' => 'cierre.title', 'label' => 'Título', 'value' => $c['cierre']['title']])
                @include('admin.about._input', ['name' => 'cierre.highlight', 'label' => 'Texto destacado', 'hint' => '(azul)', 'value' => $c['cierre']['highlight']])
                <div class="md:col-span-2">@include('admin.about._input', ['name' => 'cierre.text', 'label' => 'Texto', 'value' => $c['cierre']['text'], 'area' => true])</div>
                @include('admin.about._input', ['name' => 'cierre.button_primary', 'label' => 'Botón principal', 'value' => $c['cierre']['button_primary']])
                @include('admin.about._input', ['name' => 'cierre.button_secondary', 'label' => 'Botón secundario', 'value' => $c['cierre']['button_secondary']])
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
