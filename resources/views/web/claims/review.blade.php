@extends('web.claims.layout')

@section('title', 'Revisa tu solicitud | Overshark')
@section('claim_title', 'Revisa tu solicitud')
@section('claim_subtitle', 'Libro de Reclamaciones · Overshark Hombre')

@php
    $kind = $draft['claim_type'] === 'queja' ? 'Queja · Atención' : 'Reclamo · Producto';
    $amount = filled($draft['claimed_amount'] ?? null) ? 'S/ '.number_format((float) $draft['claimed_amount'], 2) : null;
    $box = 'border border-[#d7dae0] bg-[#f5f6f8] p-[14px] text-[14px] leading-[1.55] text-[#252932]';
@endphp

@section('claim_body')
    <p class="text-[16px] leading-[1.55] text-[#252932]">Confirma que tu información y el pedido al proveedor son correctos.</p>

    <div class="flex flex-col gap-[6px]">
        <h2 class="text-[13px] font-extrabold leading-[1.55]">Consumidor</h2>
        <div class="{{ $box }}">
            <p>{{ $draft['consumer_name'] }} · {{ $draft['document_type'] }} {{ $draft['document_number'] }}</p>
            <p>{{ $draft['address'] }}</p>
            <p>{{ $draft['email'] }}@if (filled($draft['phone'] ?? null)) · {{ $draft['phone'] }}@endif</p>
            @if (filled($draft['order_number'] ?? null))
                <p>Pedido: {{ $draft['order_number'] }}</p>
            @endif
            @if (filled($draft['guardian_name'] ?? null))
                <p class="mt-2">Representante: {{ $draft['guardian_name'] }} · {{ $draft['guardian_document_type'] ?? 'DNI' }} {{ $draft['guardian_document_number'] }} · {{ $draft['guardian_contact'] }}</p>
            @endif
        </div>
    </div>

    <div class="flex flex-col gap-[6px]">
        <h2 class="text-[13px] font-extrabold leading-[1.55]">Solicitud</h2>
        <div class="{{ $box }} whitespace-pre-line pb-12">
            <p>{{ $kind }} · {{ $draft['order_product'] }}@if ($amount) · {{ $amount }}@endif</p>
            <p>{{ $draft['claim_description'] }}</p>
            <p>{{ $draft['expected_solution'] }}</p>
            @if (filled($draft['supporting_document_name'] ?? null))
                <p class="mt-2">Adjunto: {{ $draft['supporting_document_name'] }}</p>
            @endif
        </div>
    </div>

    <p class="text-[14px] leading-[1.55] text-[#252932]">La copia y la respuesta se enviarán al correo indicado. Puedes corregir los datos antes de confirmar.</p>

    <form method="POST" action="{{ route('web.claims.store') }}">
        @csrf
        <button type="submit" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Confirmar envío</button>
    </form>

    <a href="{{ route('web.claims.edit') }}" class="os-focus w-fit text-[14px] font-extrabold leading-[1.55] underline-offset-4 hover:underline">← Editar mi solicitud</a>
@endsection
