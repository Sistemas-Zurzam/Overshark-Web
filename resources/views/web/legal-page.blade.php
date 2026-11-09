@extends('layouts.web')

{{--
    Página de un documento legal (Figma "10 · Legal / Términos · Privacidad · Reclamaciones"): $document = clave de
    App\Support\LegalDocuments. El mismo texto se abre en un modal desde el checkout y los footers
    (web.partials.legal-modal); esta página es el respaldo sin JavaScript y la dirección que se puede compartir.
--}}

@php($doc = \App\Support\LegalDocuments::find($document))

@section('title', $doc['title'].' | Overshark')
@section('meta_description', $doc['description'])

@section('content')
    <div class="bg-white font-['Manrope',sans-serif] text-[#071226]">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-7 px-5 py-10 sm:px-8 lg:px-[160px] lg:py-16">
            <p class="text-[11px] font-extrabold uppercase leading-[1.55]">Información legal · Overshark Hombre</p>
            <h1 class="text-[34px] font-extrabold leading-[1.15] sm:text-[48px] sm:leading-[1.55]">{{ $doc['title'] }}</h1>
            <p class="text-[16px] leading-[1.55] text-[#252932]">Conoce las condiciones de la tienda y cómo se utiliza tu información.</p>

            <nav class="flex flex-col gap-2 bg-[#f3f4f6] p-4 text-[13px] font-extrabold leading-[1.55]" aria-label="Información legal">
                @foreach (\App\Support\LegalDocuments::all() as $key => $other)
                    @if ($key === $document)
                        <span aria-current="page">{{ $other['title'] }}</span>
                    @else
                        <a href="{{ route($other['route']) }}" class="os-focus w-fit underline-offset-4 hover:underline">{{ $other['title'] }}</a>
                    @endif
                @endforeach
                <a href="{{ route('web.claims.create') }}" class="os-focus w-fit underline-offset-4 hover:underline">Libro de Reclamaciones</a>
            </nav>

            @include('web.partials.legal-content', ['document' => $document])
        </div>
    </div>
@endsection
