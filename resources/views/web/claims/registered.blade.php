@extends('web.claims.layout')

@section('title', 'Solicitud registrada | Overshark')
@section('claim_title', 'Solicitud registrada')
@section('claim_subtitle', 'Libro de Reclamaciones · Overshark Hombre')

@section('claim_body')
    <p role="status" class="text-[25px] font-extrabold leading-[1.55]">✓ Recibimos tu solicitud</p>

    @include('web.claims.sheet')

    <div class="flex flex-col gap-7">
        <a href="{{ route('web.claims.receipt') }}" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Ver constancia</a>
        <a href="{{ route('web.home') }}" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Volver a la tienda</a>
    </div>
@endsection
