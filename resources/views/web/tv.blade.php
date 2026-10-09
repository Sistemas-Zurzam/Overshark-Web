@extends('layouts.web')

@section('title', 'Overshark TV · En vivo')

{{--
    MAQUETA de Overshark TV: todo el contenido es de ejemplo. No hay streaming, chat ni precios reales;
    los botones de compra y el chat solo muestran cómo se vería. Ver el roadmap (Fase 0–4) antes de implementarlo.
--}}
@php
    $cartCount = collect(session('cart.items', []))->sum('qty');
    $polo = 'M38 22 50 15q10 9 20 0l12 7 20 14-8 17-10-6v58H36V46l-10 6-8-17z';
    $camisa = 'M40 19 52 13l8 11 8-11 12 6 20 10 8 67-12 2-8-50v60H34V48l-8 50-12-2 8-67z';
    $pantalon = 'M36 12h48l7 96H67l-7-62-7 62H29z';
    $liveProducts = [
        ['name' => 'Polo Waffle · Verde', 'path' => $polo, 'color' => '#5f7f5c', 'pinned' => true],
        ['name' => 'Camisero Pike · Azul', 'path' => $polo, 'color' => '#1f3f8f', 'pinned' => false],
        ['name' => 'Camisa Waffle · Celeste', 'path' => $camisa, 'color' => '#8fb6dd', 'pinned' => false],
        ['name' => 'Pantalón Bratz · Beige', 'path' => $pantalon, 'color' => '#cdb894', 'pinned' => false],
    ];
    $upcoming = [
        ['day' => 'JUE', 'title' => 'Básicos para el día a día', 'brand' => 'Overshark'],
        ['day' => 'SÁB', 'title' => 'Drop exclusivo Bravos', 'brand' => 'Bravos'],
        ['day' => 'DOM', 'title' => 'Liquidación de temporada', 'brand' => 'Overshark'],
    ];
    $chat = [
        ['host' => true, 'name' => 'Overshark', 'color' => '#8fbcff', 'text' => '¡Bienvenidos! Hoy precios especiales en Waffle.'],
        ['name' => 'carla_lima', 'color' => '#ffc44d', 'text' => '¿El verde viene en talla S?'],
        ['host' => true, 'name' => 'Overshark', 'color' => '#8fbcff', 'text' => '@carla_lima sí, de S a XL.'],
        ['system' => true, 'text' => 'Jorge compró Polo Waffle · Verde'],
        ['name' => 'miguel.ar', 'color' => '#7ee6d4', 'text' => 'Muestren el camisero azul porfa'],
        ['name' => 'dani_23', 'color' => '#ff8a8e', 'text' => '¿Envían a Arequipa?'],
        ['system' => true, 'text' => 'Lucía compró Pantalón Bratz · Beige'],
        ['name' => 'renzo', 'color' => '#c4a8ff', 'text' => 'Ya pedí el mío'],
    ];
@endphp

@section('header-bar')
    <div class="mx-auto flex h-14 max-w-[1440px] items-center gap-5 px-4 sm:px-6 xl:h-[52px] xl:px-16">
        <a href="{{ route('web.tv') }}" class="os-focus flex items-center gap-2 rounded" aria-label="Overshark TV">
            <span class="font-heading text-[21px] font-extrabold leading-none tracking-tight" aria-hidden="true">OVER<span class="text-electric">SHARK</span></span>
            <span class="rounded bg-electric px-1.5 py-0.5 font-heading text-[11px] font-extrabold tracking-[0.12em]" aria-hidden="true">TV</span>
        </a>
        <nav class="hidden items-center gap-6 md:flex" aria-label="Overshark TV">
            <a href="{{ route('web.tv') }}" class="os-nav-link os-focus" aria-current="page">En vivo</a>
            <a href="#programacion" class="os-nav-link os-focus">Programación</a>
            <a href="#programacion" class="os-nav-link os-focus">Repeticiones</a>
        </nav>
        <a href="{{ route('web.home') }}" class="os-focus ml-auto hidden rounded text-[11px] font-semibold uppercase tracking-wide text-mist hover:text-white sm:inline">Ir a la tienda</a>
        <button type="button" data-cart-open class="os-focus relative grid h-10 w-10 place-items-center rounded-full text-white transition hover:bg-white/10 max-sm:ml-auto" aria-label="Carrito de compras, {{ $cartCount }} {{ $cartCount === 1 ? 'producto' : 'productos' }}">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 4h2.2l2.3 11.2a1.5 1.5 0 0 0 1.5 1.2h8.3a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.1"/><circle cx="9.5" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/></svg>
            @if ($cartCount > 0)
                <span class="absolute -right-0.5 -top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-electric px-1 text-[11px] font-black leading-none text-white" aria-hidden="true">{{ $cartCount }}</span>
            @endif
        </button>
    </div>
@endsection

@section('content')
    <div class="os-ocean pb-16" data-tv-mock>
        <p class="border-b border-amber-400/30 bg-amber-400/10 px-4 py-2 text-center text-xs font-semibold text-amber-200" role="note">
            Maqueta de Overshark TV: la transmisión, el chat, los precios y las cifras son de ejemplo.
        </p>

        <div class="mx-auto flex max-w-[1400px] flex-wrap items-start gap-6 px-4 pt-6 sm:px-6 xl:px-10">
            {{-- Transmisión --}}
            <section aria-label="Transmisión" class="flex min-w-0 flex-[999_1_640px] flex-col gap-5">
                <div class="relative aspect-[4/5] overflow-hidden rounded-2xl border border-electric-soft/35 sm:aspect-video bg-[#06183a] shadow-[0_0_40px_rgb(38_117_255/25%)]">
                    <img src="{{ asset('images/outfit/fondo-probador-1000.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-85" fetchpriority="high">
                    <img src="{{ asset('images/home/hero-model-hombre.webp') }}" alt="Presentador mostrando el polo verde en vivo" class="absolute bottom-0 left-1/2 h-[92%] w-auto -translate-x-1/2">

                    <div class="absolute left-3 top-3 flex gap-2 sm:left-4 sm:top-4">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-[#d93a3f] px-2.5 py-1.5 text-[11px] font-extrabold tracking-[0.08em]">
                            <span class="neon-live-dot h-2 w-2 rounded-full bg-white" aria-hidden="true"></span>EN VIVO
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-abyss/75 px-2.5 py-1.5 text-[11px] font-bold">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            [N] viendo
                        </span>
                    </div>
                    <span class="absolute right-4 top-4 hidden font-heading text-sm font-black opacity-85 sm:block" aria-hidden="true">OVERSHARK TV</span>

                    {{-- Producto en pantalla --}}
                    <div class="absolute bottom-14 left-3 flex w-[min(340px,calc(100%-1.5rem))] items-center gap-3 rounded-xl border border-electric bg-abyss/90 p-3 shadow-[0_0_24px_rgb(38_117_255/40%)] sm:left-4">
                        <div class="grid h-14 w-14 flex-none place-items-center rounded-lg bg-[#0a1a3a]">
                            <svg class="h-11 w-11" viewBox="0 0 120 120" aria-hidden="true"><path d="{{ $polo }}" fill="#5f7f5c"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="hidden text-[10px] font-extrabold uppercase tracking-[0.1em] text-electric-soft sm:block">Mostrando ahora</p>
                            <p class="truncate text-sm font-bold">Polo Waffle · Verde</p>
                            <p class="text-sm font-extrabold">S/ [PRECIO LIVE]</p>
                            <p class="hidden text-xs font-medium text-haze line-through sm:block">Antes S/ [PRECIO]</p>
                        </div>
                        <button type="button" class="os-btn-solid os-focus h-11 flex-none px-3 text-xs">Agregar</button>
                    </div>

                    {{-- Oferta flash --}}
                    <div class="absolute bottom-14 right-3 hidden rounded-lg border border-amber-300/60 bg-abyss/90 px-3.5 py-2 text-center sm:right-4 sm:block">
                        <p class="text-[10px] font-extrabold tracking-[0.1em] text-amber-300">OFERTA FLASH</p>
                        <p class="font-heading text-2xl font-black" data-tv-countdown>05:00</p>
                        <p class="text-[11px] text-mist">quedan [N] unidades</p>
                    </div>

                    <div class="absolute inset-x-0 bottom-0 flex h-12 items-center gap-2 bg-gradient-to-t from-abyss/90 to-transparent px-3">
                        <button type="button" class="os-focus grid h-9 w-9 place-items-center rounded" aria-label="Pausar"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg></button>
                        <button type="button" class="os-focus grid h-9 w-9 place-items-center rounded" aria-label="Volumen"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10v4h4l5 4V6L8 10H4Z"/><path d="M16.5 8.5a5 5 0 0 1 0 7"/></svg></button>
                        <span class="text-xs font-bold text-[#ff8a8e]">EN DIRECTO</span>
                        <button type="button" class="os-focus ml-auto grid h-9 w-9 place-items-center rounded" aria-label="Pantalla completa"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/></svg></button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <div class="grid h-12 w-12 flex-none place-items-center rounded-full border-2 border-electric bg-panel-2 font-heading text-sm font-black" aria-hidden="true">OS</div>
                        <div class="min-w-0">
                            <h1 class="font-heading text-xl font-black leading-tight sm:text-2xl">Drop de temporada: Waffle y Camisero Pike</h1>
                            <p class="mt-1 text-sm text-mist">Overshark Perú · Colección hombre · Envíos a todo el Perú</p>
                        </div>
                    </div>
                    <div class="flex gap-2.5">
                        <button type="button" class="os-btn-ghost os-focus h-11 px-4 text-xs">Compartir</button>
                        <button type="button" class="os-btn-solid os-focus h-11 px-4 text-xs">Seguir canal</button>
                    </div>
                </div>

                <section aria-labelledby="tv-products" class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="tv-products" class="font-heading text-lg font-black uppercase">Productos de este live</h2>
                        <span class="text-xs text-haze">Precios especiales solo durante la transmisión</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        @foreach ($liveProducts as $product)
                            <article @class([
                                'overflow-hidden rounded-xl border bg-[#071431]',
                                'border-electric shadow-[0_0_18px_rgb(38_117_255/35%)]' => $product['pinned'],
                                'border-electric-soft/25' => ! $product['pinned'],
                            ])>
                                <div class="os-product-media relative grid h-28 place-items-center">
                                    <svg class="h-20 w-20" viewBox="0 0 120 120" aria-hidden="true"><path d="{{ $product['path'] }}" fill="{{ $product['color'] }}"/></svg>
                                    @if ($product['pinned'])
                                        <span class="absolute left-2 top-2 rounded bg-[#d93a3f] px-2 py-0.5 text-[10px] font-extrabold tracking-[0.08em]">EN PANTALLA</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-1.5 p-3">
                                    <p class="text-sm font-bold leading-tight">{{ $product['name'] }}</p>
                                    <p class="text-sm font-extrabold">S/ [PRECIO LIVE]</p>
                                    <button type="button" class="os-btn-ghost os-focus h-10 text-[11px]">Agregar al carrito</button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="programacion" aria-labelledby="tv-upcoming" class="flex scroll-mt-24 flex-col gap-3">
                    <h2 id="tv-upcoming" class="font-heading text-lg font-black uppercase">Próximos lives</h2>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($upcoming as $live)
                            <article class="flex items-center gap-3.5 rounded-xl border border-electric-soft/25 bg-[#071431] p-3.5">
                                <div class="w-14 flex-none rounded-lg bg-panel-2 py-1.5 text-center">
                                    <p class="text-[11px] font-extrabold text-electric-soft">{{ $live['day'] }}</p>
                                    <p class="font-heading text-lg font-black">[DD]</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold">{{ $live['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-haze">[HORA] · {{ $live['brand'] }}</p>
                                </div>
                                <button type="button" class="os-focus grid h-11 w-11 flex-none place-items-center rounded-full border border-electric-soft/45 hover:bg-white/10" aria-label="Recordarme: {{ $live['title'] }}">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15Z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                                </button>
                            </article>
                        @endforeach
                    </div>
                </section>
            </section>

            {{-- Chat --}}
            <aside aria-label="Chat en vivo" class="os-panel flex h-[560px] min-w-0 flex-[1_1_340px] flex-col lg:max-w-[400px] xl:h-[calc(100svh-130px)] xl:max-h-[760px] xl:min-h-[520px]">
                <div class="flex items-center justify-between border-b border-edge px-4 py-3.5">
                    <h2 class="font-heading text-[15px] font-black uppercase">Chat en vivo</h2>
                    <span class="text-xs text-haze">Modo lento · 5 s</span>
                </div>

                <div class="mx-4 mt-3 rounded-lg border border-electric-soft/35 bg-panel-2 p-3">
                    <p class="text-[11px] font-extrabold tracking-[0.1em] text-electric-soft">ENCUESTA</p>
                    <p class="mb-2.5 mt-1 text-sm font-bold">¿Qué color sacamos en el próximo drop?</p>
                    <div class="flex flex-col gap-1.5 text-[13px] font-bold">
                        <button type="button" class="os-focus flex h-9 items-center justify-between rounded-md border border-electric-soft/40 bg-[linear-gradient(90deg,rgb(38_117_255/45%)_62%,transparent_62%)] px-2.5">Azul marino <span>62%</span></button>
                        <button type="button" class="os-focus flex h-9 items-center justify-between rounded-md border border-electric-soft/40 bg-[linear-gradient(90deg,rgb(38_117_255/25%)_38%,transparent_38%)] px-2.5">Verde botella <span>38%</span></button>
                    </div>
                </div>

                <ol class="flex flex-1 flex-col gap-2.5 overflow-y-auto px-4 py-3 text-sm leading-snug [scrollbar-color:#2a4a8c_transparent]" aria-label="Mensajes" aria-live="polite" data-tv-chat>
                    @foreach ($chat as $message)
                        <li>
                            @if ($message['system'] ?? false)
                                <p class="rounded-lg bg-aqua/12 px-2.5 py-2 text-[13px] font-semibold text-[#7ee6d4]">{{ $message['text'] }}</p>
                            @else
                                <p>
                                    @if ($message['host'] ?? false)
                                        <span class="mr-1.5 rounded bg-electric px-1.5 py-px align-[2px] text-[10px] font-extrabold">HOST</span>
                                    @endif
                                    <span class="font-extrabold" style="color: {{ $message['color'] }}">{{ $message['name'] }}</span>
                                    <span class="text-[#dbe6f5]">{{ $message['text'] }}</span>
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <div class="flex gap-2 px-4 pb-2">
                    <button type="button" class="os-focus grid h-10 w-10 place-items-center rounded-full border border-electric-soft/35 text-[#ff8a3d] hover:bg-white/5" aria-label="Reaccionar con fuego"><svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 1.8c.7 4.4-2.2 5.8-2.2 8.7 0 1.2.7 2.1 1.7 2.1 1.6 0 2.5-1.5 2.1-3.5 2.6 2 4 4.4 4 7.1a7.1 7.1 0 0 1-14.2 0c0-3.8 2-7.3 5.8-10.5-.2 2.6.5 4.1 1.5 4.1 1.4 0 2.3-2.7 1.3-8Z"/></svg></button>
                    <button type="button" class="os-focus grid h-10 w-10 place-items-center rounded-full border border-electric-soft/35 text-[#ff6b8a] hover:bg-white/5" aria-label="Reaccionar con corazón"><svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 20s-7-4.4-9.2-9A5 5 0 0 1 12 6a5 5 0 0 1 9.2 5c-2.2 4.6-9.2 9-9.2 9Z"/></svg></button>
                </div>

                <form class="flex gap-2 border-t border-edge px-4 pb-4 pt-3" data-tv-chat-form>
                    <label for="tv-chat-input" class="sr-only">Escribe un mensaje</label>
                    <input id="tv-chat-input" type="text" maxlength="200" autocomplete="off" placeholder="Escribe en el chat…" class="h-11 min-w-0 flex-1 rounded-lg border border-electric-soft/35 bg-abyss px-3 text-sm text-white outline-none placeholder:text-haze focus:border-electric-soft">
                    <button type="submit" class="os-btn-solid os-focus h-11 px-4 text-xs">Enviar</button>
                </form>
            </aside>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Maqueta: cuenta regresiva de la oferta y chat local (los mensajes no se envían a nadie).
        (() => {
            const countdown = document.querySelector('[data-tv-countdown]');
            let seconds = 300;
            if (countdown) {
                setInterval(() => {
                    seconds = seconds > 0 ? seconds - 1 : 300;
                    countdown.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
                }, 1000);
            }

            const form = document.querySelector('[data-tv-chat-form]');
            const list = document.querySelector('[data-tv-chat]');
            form?.addEventListener('submit', (event) => {
                event.preventDefault();
                const input = form.querySelector('input');
                const text = input.value.trim();
                if (!text) {
                    return;
                }
                const item = document.createElement('li');
                const p = document.createElement('p');
                const name = document.createElement('span');
                name.className = 'font-extrabold text-electric-soft';
                name.textContent = 'tú ';
                const body = document.createElement('span');
                body.className = 'text-[#dbe6f5]';
                body.textContent = text;
                p.append(name, body);
                item.append(p);
                list.append(item);
                list.scrollTop = list.scrollHeight;
                input.value = '';
            });
        })();
    </script>
@endsection
