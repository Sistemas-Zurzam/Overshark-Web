@extends('web.claims.layout')

@section('title', 'No se pudo enviar | Overshark')
@section('claim_title', 'No se pudo enviar')
@section('claim_subtitle', 'Libro de Reclamaciones · Overshark Hombre')

@section('claim_body')
    <p role="alert" class="text-[23px] font-extrabold leading-[1.55] text-[#b42318]">Tu solicitud aún no fue registrada.</p>
    <p class="text-[16px] leading-[1.55] text-[#252932]">Conservamos los datos en esta pantalla para que puedas intentar nuevamente. No se ha generado un número de hoja.</p>

    <form method="POST" action="{{ route('web.claims.store') }}">
        @csrf
        <button type="submit" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Volver a intentar</button>
    </form>

    <a href="{{ route('web.claims.review') }}" class="os-focus w-fit text-[14px] font-extrabold leading-[1.55] underline-offset-4 hover:underline">← Revisar mis datos</a>
@endsection
