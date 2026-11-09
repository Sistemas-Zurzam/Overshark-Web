@extends('layouts.web')

{{--
    Marco de las pantallas del Libro de Reclamaciones (Figma "Libro de Reclamaciones"): el hijo define
    claim_title, claim_subtitle y claim_body. La navegación legal es la de las demás páginas legales.
--}}

@section('content')
    <div class="bg-white font-['Manrope',sans-serif] text-[#071226]">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-7 px-5 py-10 sm:px-8 lg:px-[160px] lg:py-16">
            <p class="text-[11px] font-extrabold uppercase leading-[1.55]">Información legal · Overshark Hombre</p>
            <h1 class="text-[34px] font-extrabold leading-[1.15] sm:text-[48px] sm:leading-[1.55]">@yield('claim_title')</h1>
            <p class="text-[16px] leading-[1.55] text-[#252932]">@yield('claim_subtitle')</p>

            <nav class="flex flex-col gap-2 bg-[#f3f4f6] p-4 text-[13px] font-extrabold leading-[1.55]" aria-label="Información legal">
                @foreach (\App\Support\LegalDocuments::all() as $key => $other)
                    <a href="{{ route($other['route']) }}" data-legal-open="{{ $key }}" class="os-focus w-fit underline-offset-4 hover:underline">{{ $other['title'] }}</a>
                @endforeach
                <span aria-current="page">Libro de Reclamaciones</span>
            </nav>

            @yield('claim_body')
        </div>
    </div>
@endsection
