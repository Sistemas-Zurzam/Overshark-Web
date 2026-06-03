@extends('layouts.web')

@section('title', 'Overshark | Tienda')

@section('content')
    <section class="relative overflow-hidden border-b border-white/10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_75%_30%,rgba(6,182,212,0.22),transparent_38%)]"></div>
        <div class="relative mx-auto grid min-h-[620px] max-w-7xl items-center gap-12 px-5 py-20 lg:grid-cols-2 lg:px-8">
            <div>
                <p class="mb-5 text-sm font-bold uppercase tracking-[0.35em] text-cyan-400">Nueva colección</p>
                <h1 class="max-w-2xl text-5xl font-black leading-[0.95] tracking-tight sm:text-7xl">
                    Diseñado para destacar.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                    Descubre productos seleccionados, variantes exclusivas y combos hechos para tu estilo.
                </p>
                <div class="mt-9 flex flex-wrap gap-4">
                    <a href="#productos" class="rounded-full bg-cyan-400 px-6 py-3 font-bold text-slate-950 transition hover:bg-cyan-300">Ver productos</a>
                    <a href="#categorias" class="rounded-full border border-white/20 px-6 py-3 font-bold transition hover:border-cyan-400 hover:text-cyan-300">Explorar categorías</a>
                </div>
            </div>
            <div class="relative mx-auto aspect-square w-full max-w-lg">
                <div class="absolute inset-8 rounded-full bg-cyan-400/20 blur-3xl"></div>
                <div class="relative grid h-full place-items-center rounded-[3rem] border border-white/10 bg-gradient-to-br from-slate-800 to-slate-950 shadow-2xl">
                    <span class="text-center text-7xl font-black tracking-[0.15em] text-cyan-400 sm:text-8xl">OS</span>
                </div>
            </div>
        </div>
    </section>

    <section id="categorias" class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="mb-10 flex items-end justify-between">
            <div><p class="text-sm font-bold uppercase tracking-widest text-cyan-400">Explora</p><h2 class="mt-2 text-3xl font-black">Categorías destacadas</h2></div>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            @foreach (['Novedades', 'Más vendidos', 'Combos'] as $category)
                <article class="group min-h-56 rounded-3xl border border-white/10 bg-slate-900 p-7 transition hover:-translate-y-1 hover:border-cyan-400/60">
                    <span class="text-sm font-bold text-cyan-400">0{{ $loop->iteration }}</span>
                    <h3 class="mt-24 text-2xl font-black">{{ $category }}</h3>
                </article>
            @endforeach
        </div>
    </section>

    <section id="productos" class="border-y border-white/10 bg-slate-900/50">
        <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-widest text-cyan-400">Selección Overshark</p>
            <h2 class="mt-2 text-3xl font-black">Productos destacados</h2>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (range(1, 4) as $item)
                    <article class="overflow-hidden rounded-3xl border border-white/10 bg-slate-950">
                        <div class="grid aspect-square place-items-center bg-gradient-to-br from-slate-800 to-slate-900 text-4xl font-black text-slate-700">OS</div>
                        <div class="p-5">
                            <p class="text-xs font-bold uppercase tracking-widest text-cyan-400">Categoría</p>
                            <h3 class="mt-2 font-bold">Producto destacado {{ $item }}</h3>
                            <p class="mt-3 text-lg font-black">S/ 0.00</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
