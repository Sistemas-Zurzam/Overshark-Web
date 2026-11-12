@extends('layouts.admin')

@section('title', 'Contenido de la tienda')

@php
    $field = 'w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none focus:border-admin-text';
    $label = 'mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary';
    $card = 'rounded-sm border border-admin-border bg-admin-surface';
    $name = fn (string $path) => 'c['.str_replace('.', '][', $path).']';
    $current = fn (string $path) => old('c.'.$path, $values($path));
@endphp

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Contenido / Tienda</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Contenido de la tienda</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">
                    Textos, precios de envío y datos que se ven en toda la tienda. Un campo vacío vuelve al valor original.
                    Los textos aceptan <code class="font-bold">{lima}</code>, <code class="font-bold">{agencia}</code>, <code class="font-bold">{desde}</code>,
                    <code class="font-bold">{separo}</code>, <code class="font-bold">{separo_lima}</code> y <code class="font-bold">{combo}</code>: se reemplazan por el precio vigente.
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <a href="{{ route('web.home') }}" target="_blank" class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Ver tienda</a>
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

        <form action="{{ route('admin.contenido.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            @foreach ($schema as $id => $group)
                <details class="{{ $card }}" @if ($loop->first) open @endif>
                    <summary class="cursor-pointer px-6 py-4 font-admin-display text-xl font-black uppercase leading-none text-admin-text">{{ $group['title'] }}</summary>
                    <div class="flex flex-col gap-4 border-t border-admin-border p-6">
                        <p class="text-sm text-admin-text-secondary">{{ $group['help'] }}</p>
                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach ($group['fields'] as [$path, $text, $type, $max, $help])
                                @if ($type === 'toggle')
                                    <label class="flex items-start gap-2 text-sm lg:col-span-2">
                                        <input type="checkbox" name="{{ $name($path) }}" value="1" @checked(old('c.'.$path, $current($path))) class="mt-0.5 size-4">
                                        <span><span class="font-bold">{{ $text }}</span>@if ($help)<span class="mt-1 block text-xs text-slate-500">{{ $help }}</span>@endif</span>
                                    </label>
                                    @continue
                                @endif
                                <label @class(['block', 'lg:col-span-2' => $type === 'textarea'])>
                                    <span class="{{ $label }}">{{ $text }}</span>
                                    @if ($type === 'textarea')
                                        <textarea name="{{ $name($path) }}" rows="2" maxlength="{{ $max }}" class="{{ $field }} py-2">{{ $current($path) }}</textarea>
                                    @else
                                        <input type="{{ $type === 'phone' ? 'tel' : $type }}" name="{{ $name($path) }}" value="{{ $current($path) }}" @if ($type === 'number') step="0.01" min="0" @else maxlength="{{ $max }}" @endif class="{{ $field }} h-10">
                                    @endif
                                    @if ($help) <span class="mt-1 block text-xs text-admin-text-muted">{{ $help }}</span> @endif
                                </label>
                            @endforeach
                        </div>

                    </div>
                </details>
            @endforeach

            <div class="flex flex-col gap-1 rounded-sm border border-admin-border bg-admin-surface px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-admin-text-secondary"><strong class="text-admin-text">Fotos de la tienda:</strong> portadas, promos, categorías y telas se reemplazan desde Banners, donde las ves todas juntas.</p>
                <a href="{{ route('admin.banners.index') }}#imagenes-fijas" class="shrink-0 text-xs font-extrabold uppercase tracking-[1px] text-admin-accent hover:underline">Ir a Banners</a>
            </div>

            <details class="{{ $card }}">
                <summary class="cursor-pointer px-6 py-4 font-admin-display text-xl font-black uppercase leading-none text-admin-text">Categorías de la tienda</summary>
                <div class="flex flex-col gap-6 border-t border-admin-border p-6">
                    <p class="text-sm text-admin-text-secondary">
                        Zazu envía todo como «General», así que cada categoría agrupa los productos cuyo nombre contiene alguno de sus términos
                        (separados por comas). Puedes cambiar el nombre que ven los clientes y los términos; con un campo vacío vuelve el original.
                        Un producto cae en la primera categoría que coincide, en el orden en que aparecen aquí.
                    </p>
                    @foreach ($categories as $line => $config)
                        <div class="flex flex-col gap-3 border-t border-admin-border pt-4 first:border-t-0 first:pt-0">
                            <h3 class="text-sm font-extrabold uppercase tracking-[1px] text-admin-text">{{ $line === 'mujer' ? 'Mujer (Overshark Girls)' : 'Hombre' }}</h3>
                            @foreach ($config['categories'] as $slug => $category)
                                <div class="grid gap-3 border-b border-admin-border pb-3 last:border-b-0 lg:grid-cols-[220px_1fr]">
                                    <label class="block">
                                        <span class="{{ $label }}">Nombre</span>
                                        <input type="text" name="c[categorias][{{ $line }}][{{ $slug }}][label]" value="{{ old("c.categorias.$line.$slug.label", $category['label']) }}" maxlength="40" class="{{ $field }} h-10">
                                    </label>
                                    <label class="block">
                                        <span class="{{ $label }}">Términos del nombre del producto</span>
                                        <textarea name="c[categorias][{{ $line }}][{{ $slug }}][terms]" rows="2" maxlength="500" class="{{ $field }} py-2">{{ old("c.categorias.$line.$slug.terms", implode(', ', $category['terms'])) }}</textarea>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </details>

            <details class="{{ $card }}">
                <summary class="cursor-pointer px-6 py-4 font-admin-display text-xl font-black uppercase leading-none text-admin-text">Preguntas frecuentes</summary>
                <div class="flex flex-col gap-4 border-t border-admin-border p-6">
                    <p class="text-sm text-admin-text-secondary">Hasta {{ \App\Support\SiteContent::FAQ_SLOTS }} preguntas en la página Contacto. Deja una fila vacía para quitarla. Si las dejas todas vacías vuelven las originales.</p>
                    @foreach ($faqs as $i => $faq)
                        <div class="grid gap-3 border-b border-admin-border pb-4 last:border-b-0 last:pb-0">
                            <label class="block">
                                <span class="{{ $label }}">Pregunta {{ $i + 1 }}</span>
                                <input type="text" name="c[contacto][faq][{{ $i }}][q]" value="{{ old("c.contacto.faq.$i.q", $faq[0]) }}" maxlength="200" class="{{ $field }} h-10">
                            </label>
                            <label class="block">
                                <span class="{{ $label }}">Respuesta {{ $i + 1 }}</span>
                                <textarea name="c[contacto][faq][{{ $i }}][a]" rows="2" maxlength="800" class="{{ $field }} py-2">{{ old("c.contacto.faq.$i.a", $faq[1]) }}</textarea>
                            </label>
                        </div>
                    @endforeach
                </div>
            </details>

            @foreach ($legal as $key => $doc)
                <details class="{{ $card }}">
                    <summary class="cursor-pointer px-6 py-4 font-admin-display text-xl font-black uppercase leading-none text-admin-text">{{ $doc['title'] }} (documento legal)</summary>
                    <div class="flex flex-col gap-4 border-t border-admin-border p-6">
                        <p class="text-sm text-admin-text-secondary">Texto de la página y del modal. Los textos actuales son un borrador del diseño: valídalos con tu asesor legal antes de publicar.</p>
                        @foreach ($doc['sections'] as $i => [$heading, $text])
                            <div class="grid gap-3 border-b border-admin-border pb-4 last:border-b-0 last:pb-0">
                                <label class="block">
                                    <span class="{{ $label }}">Sección {{ $i + 1 }} · título</span>
                                    <input type="text" name="c[legal][{{ $key }}][{{ $i }}][h]" value="{{ old("c.legal.$key.$i.h", $heading) }}" maxlength="160" class="{{ $field }} h-10">
                                </label>
                                <label class="block">
                                    <span class="{{ $label }}">Sección {{ $i + 1 }} · texto</span>
                                    <textarea name="c[legal][{{ $key }}][{{ $i }}][t]" rows="4" maxlength="2500" class="{{ $field }} py-2">{{ old("c.legal.$key.$i.t", $text) }}</textarea>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach

            <details class="{{ $card }}">
                <summary class="cursor-pointer px-6 py-4 font-admin-display text-xl font-black uppercase leading-none text-admin-text">Nota al pie de los documentos legales</summary>
                <div class="flex flex-col gap-4 border-t border-admin-border p-6">
                    <label class="block">
                        <span class="{{ $label }}">Nota</span>
                        <input type="text" name="c[legal][note]" value="{{ old('c.legal.note', $values('legal.note')) }}" maxlength="300" class="{{ $field }} h-10">
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="c[legal][hide_note]" value="1" @checked(old('c.legal.hide_note', $values('legal.hide_note'))) class="size-4">
                        Ocultar la nota (cuando el texto ya esté validado)
                    </label>
                </div>
            </details>

            <div class="sticky bottom-0 -mx-1 flex flex-wrap items-center gap-3 border-t border-admin-border bg-admin-canvas px-1 py-3">
                <button type="submit" class="bg-admin-navy px-6 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar cambios</button>
                <button type="submit" form="reset-content" onclick="return confirm('¿Restaurar todos los textos y precios al valor original? Las fotos subidas se conservan.')" class="border border-admin-border-strong px-6 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Restaurar originales</button>
            </div>
        </form>

        <form id="reset-content" action="{{ route('admin.contenido.reset') }}" method="POST" class="hidden">@csrf</form>
    </div>
@endsection
