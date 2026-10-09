@extends('layouts.admin')

@section('title', 'Overshark TV')

{{-- MAQUETA del estudio de Overshark TV: sin streaming, chat ni ofertas reales. --}}
@section('content')
    @php
        $polo = 'M38 22 50 15q10 9 20 0l12 7 20 14-8 17-10-6v58H36V46l-10 6-8-17z';
        $camisa = 'M40 19 52 13l8 11 8-11 12 6 20 10 8 67-12 2-8-50v60H34V48l-8 50-12-2 8-67z';
        $pantalon = 'M36 12h48l7 96H67l-7-62-7 62H29z';
        $products = [
            ['name' => 'Polo Waffle · Verde', 'path' => $polo, 'color' => '#5f7f5c', 'pinned' => true],
            ['name' => 'Camisero Pike · Azul', 'path' => $polo, 'color' => '#1f3f8f', 'pinned' => false],
            ['name' => 'Camisa Waffle · Celeste', 'path' => $camisa, 'color' => '#8fb6dd', 'pinned' => false],
            ['name' => 'Pantalón Bratz · Beige', 'path' => $pantalon, 'color' => '#cdb894', 'pinned' => false],
        ];
        $chat = [
            ['name' => 'carla_lima', 'text' => '¿El verde viene en talla S?', 'flagged' => false],
            ['name' => 'miguel.ar', 'text' => 'Muestren el camisero azul porfa', 'flagged' => false],
            ['name' => 'spam_bot_77', 'text' => '[mensaje marcado por el filtro]', 'flagged' => true],
            ['name' => 'dani_23', 'text' => '¿Envían a Arequipa?', 'flagged' => false],
            ['name' => 'renzo', 'text' => 'Ya pedí el mío', 'flagged' => false],
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-600">Overshark TV</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Estudio</h1>
            <p class="mt-2 text-sm text-slate-500">Controla la transmisión, los productos en pantalla y el chat.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-xs font-black tracking-widest text-white">
                <span class="h-2 w-2 rounded-full bg-white" aria-hidden="true"></span>EN VIVO · 00:42:15
            </span>
            <a href="{{ route('web.tv') }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:border-cyan-300 hover:text-cyan-700">Ver canal</a>
            <button type="button" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50">Finalizar transmisión</button>
        </div>
    </div>

    <p class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800" role="note">
        Maqueta: los controles todavía no están conectados a una transmisión real.
    </p>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="tv-signal">
            <h2 id="tv-signal" class="text-lg font-black text-slate-950">Señal</h2>
            <div class="relative mt-4 aspect-video overflow-hidden rounded-xl bg-slate-900">
                <img src="{{ asset('images/outfit/fondo-probador-1000.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                <img src="{{ asset('images/home/hero-model-hombre.webp') }}" alt="Vista previa de la cámara" class="absolute bottom-0 left-1/2 h-[92%] w-auto -translate-x-1/2">
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-slate-500">Espectadores</dt><dd class="mt-1 text-xl font-black">[N]</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-slate-500">Ventas del live</dt><dd class="mt-1 text-xl font-black">S/ [TOTAL]</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-slate-500">Calidad</dt><dd class="mt-1 font-bold text-emerald-700">Estable · 1080p</dd></div>
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-slate-500">Retraso</dt><dd class="mt-1 font-bold">~[N] s</dd></div>
            </dl>
            <div class="mt-4">
                <label for="tv-key" class="mb-1.5 block text-xs font-bold text-slate-500">Clave de transmisión para OBS</label>
                <div class="flex gap-2">
                    <input id="tv-key" type="password" value="maqueta-sin-clave" readonly class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 hover:border-cyan-300">Copiar</button>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="tv-products">
            <h2 id="tv-products" class="text-lg font-black text-slate-950">Productos del live</h2>
            <p class="mt-1 text-sm text-slate-500">Fija el que estás mostrando: aparecerá en la pantalla de todos.</p>
            <ul class="mt-4 space-y-2">
                @foreach ($products as $product)
                    <li @class([
                        'flex items-center gap-3 rounded-xl border p-2.5',
                        'border-cyan-500 bg-cyan-50' => $product['pinned'],
                        'border-slate-200' => ! $product['pinned'],
                    ])>
                        <div class="grid h-12 w-12 flex-none place-items-center rounded-lg bg-slate-100">
                            <svg class="h-9 w-9" viewBox="0 0 120 120" aria-hidden="true"><path d="{{ $product['path'] }}" fill="{{ $product['color'] }}"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900">{{ $product['name'] }}</p>
                            <p class="text-xs text-slate-500">Stock: [N] · Precio live S/ [PRECIO]</p>
                        </div>
                        @if ($product['pinned'])
                            <span class="rounded-lg bg-cyan-600 px-2.5 py-1.5 text-[11px] font-black text-white">EN PANTALLA</span>
                        @else
                            <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:border-cyan-300">Fijar</button>
                        @endif
                    </li>
                @endforeach
            </ul>
            <fieldset class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4">
                <legend class="px-1.5 text-xs font-black text-amber-800">Oferta flash</legend>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="tv-price" class="mb-1 block text-xs font-bold text-slate-500">Precio (S/)</label>
                        <input id="tv-price" type="text" placeholder="[PRECIO]" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="tv-time" class="mb-1 block text-xs font-bold text-slate-500">Duración</label>
                        <input id="tv-time" type="text" value="5 min" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <button type="button" class="mt-3 w-full rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-600">Lanzar oferta</button>
            </fieldset>
        </section>

        <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="tv-chat">
            <h2 id="tv-chat" class="text-lg font-black text-slate-950">Moderación del chat</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" aria-pressed="true" class="rounded-full border border-cyan-500 bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-700">Modo lento</button>
                <button type="button" aria-pressed="false" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700">Solo seguidores</button>
                <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700">Nueva encuesta</button>
            </div>
            <ul class="mt-4 space-y-1.5 text-sm">
                @foreach ($chat as $message)
                    <li @class(['flex items-center gap-2 rounded-lg px-3 py-2', 'bg-red-50' => $message['flagged'], 'bg-slate-50' => ! $message['flagged']])>
                        <p class="min-w-0 flex-1"><b>{{ $message['name'] }}</b> {{ $message['text'] }}</p>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-md text-slate-500 hover:bg-white" aria-label="Borrar mensaje de {{ $message['name'] }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M6.5 7 8 21h8l1.5-14"/></svg></button>
                        <button type="button" class="grid h-8 w-8 place-items-center rounded-md text-red-600 hover:bg-white" aria-label="Bloquear a {{ $message['name'] }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="m6.5 6.5 11 11"/></svg></button>
                    </li>
                @endforeach
            </ul>
            <form class="mt-auto flex gap-2 pt-4" onsubmit="return false">
                <label for="tv-host" class="sr-only">Mensaje como host</label>
                <input id="tv-host" type="text" placeholder="Escribe como Overshark…" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                <button type="submit" class="rounded-xl bg-cyan-600 px-4 text-sm font-bold text-white">Enviar</button>
            </form>
        </section>
    </div>
@endsection
