@extends('web.claims.layout')

@section('title', 'Constancia de reclamación | Overshark')
@section('claim_title', 'Constancia de reclamación')
@section('claim_subtitle', 'Libro de Reclamaciones · Overshark Hombre')

@section('claim_body')
    <p class="text-[25px] font-extrabold leading-[1.55]">✓ Hoja del Libro de Reclamaciones</p>

    @include('web.claims.sheet')

    <p class="text-[12px] leading-[1.55] text-[#687080]">Copia para guardar o imprimir. Usa la opción de imprimir de tu navegador para guardarla en PDF.</p>

    <div class="flex flex-col gap-7 print:hidden">
        <button type="button" onclick="window.print()" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Imprimir o guardar</button>
        <a href="{{ route('web.home') }}" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Volver a la tienda</a>
    </div>
@endsection
