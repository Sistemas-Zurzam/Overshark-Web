@extends('layouts.web')

{{--
    Inicio de la línea mujer (diseño Figma "01 · Home Mujer · Overshark"): hero "Nacida del arrecife"
    bajo el menú, con la foto entera (proporción 1440 × 482), y las mismas secciones que el inicio
    hombre con la paleta Girls (web/partials/home-sections.blade.php con line = mujer).
--}}

@section('title', 'Overshark Girls | Colección mujer')

@section('content')
    <section class="relative isolate flex min-h-[520px] flex-col justify-end overflow-hidden bg-[#1a0611] text-white sm:min-h-0 sm:aspect-[1440/482] sm:justify-center" aria-labelledby="home-hero-title">
        <img src="{{ asset('images/home-mujer/hero.webp') }}" alt="" width="1920" height="640" class="absolute inset-0 -z-20 h-full w-full object-cover object-[70%_center]" fetchpriority="high">
        {{-- Solo en celular: la foto se recorta en vertical y el texto va abajo, sobre un velo para leerse. --}}
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgb(26_6_17/92%)_0%,rgb(26_6_17/55%)_40%,transparent_65%)] sm:hidden" aria-hidden="true"></div>

        <div class="flex flex-col items-start px-4 pb-10 sm:px-0 sm:pb-0 sm:pl-[17%]">
            <p class="text-[10px] font-medium uppercase tracking-[3.3px] text-white/90 lg:text-[11px]">Colección mujer</p>
            <h1 id="home-hero-title" class="pt-2 font-heading text-[30px] font-black uppercase leading-[1.02] tracking-[-0.025em] sm:text-[min(2.6vw,48px)]">
                <span class="block text-white">Nacida del arrecife</span>
                <span class="block text-[#f07ab0]">Hecha para brillar</span>
            </h1>
            <p class="pt-2 text-[15px] font-medium leading-[21px] text-white/90 lg:text-[min(1.1vw,20px)] lg:leading-[1.4]">Básicos suaves, luminosos y con actitud para cada día.</p>
            <a href="{{ route('web.products.search', ['orden' => 'populares']) }}" class="os-focus mt-[26px] inline-flex h-[43px] items-center justify-center gap-2.5 rounded-[6px] border border-[#f07fa6] bg-[rgb(2_7_19/55%)] px-7 text-[11px] font-bold uppercase tracking-[0.4px] transition hover:bg-[#f07fa6] hover:text-[#3a0f27]">
                Ver colección
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
    </section>

    @include('web.partials.home-sections', ['line' => 'mujer'])
@endsection
