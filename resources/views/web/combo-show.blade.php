@extends('layouts.web')

@section('title', $combo->name.' · Overshark')

@section('content')
    <section class="bg-[#f1f2f4] px-5 py-12 text-slate-950 sm:py-16 lg:px-8">
        <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,.95fr)] lg:items-center">
            <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-slate-950 shadow-2xl shadow-slate-300/50">
                <img src="{{ $combo->imageUrl() }}" alt="{{ $combo->name }}" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6 flex items-end justify-between gap-4 text-white">
                    <span class="rounded-full bg-white/95 px-3 py-1.5 text-xs font-black uppercase tracking-[0.16em] text-slate-950">{{ $combo->brand ?: 'Overshark' }}</span>
                    @if ($combo->modality)
                        <span class="text-sm font-bold text-white/85">{{ $combo->modality }}</span>
                    @endif
                </div>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-cyan-600">Promoción Overshark</p>
                <h1 class="mt-3 text-4xl font-black uppercase leading-[.95] sm:text-6xl">{{ $combo->name }}</h1>
                @if ($combo->price !== null)
                    <p class="mt-6 text-4xl font-black text-slate-950">S/ {{ number_format((float) $combo->price, 2) }}</p>
                @endif
                <p class="mt-2 text-sm font-semibold text-slate-500">{{ $combo->quantityLabel() }}</p>

                <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Incluye</h2>
                    <ul class="mt-4 divide-y divide-slate-100">
                        @forelse ($combo->displayItems() as $item)
                            <li class="flex items-center justify-between gap-4 py-3 text-sm">
                                @php
                                    $searchUrl = route('web.products.search', ['q' => $item['zazu']]);
                                @endphp
                                <a href="{{ $searchUrl }}" class="font-bold text-slate-800 transition hover:text-cyan-700">{{ $item['zazu'] }}</a>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">×{{ $item['cantidad'] }}</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm font-semibold text-slate-500">La composición se confirmará antes de publicar la promoción.</li>
                        @endforelse
                    </ul>
                    @if ($combo->notes)
                        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold leading-6 text-amber-900">{{ $combo->notes }}</p>
                    @endif
                </div>

                <a href="{{ route('web.home') }}#productos" class="btn-primary mt-8 px-7 py-3.5">Ver productos disponibles</a>
            </div>
        </div>
    </section>
@endsection
