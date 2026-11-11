@extends('web.claims.layout')

@section('title', 'Libro de Reclamaciones | Overshark')
@section('meta_description', 'Registra tu reclamo o queja en el Libro de Reclamaciones virtual de Overshark. No necesitas una cuenta.')
@section('claim_title', 'Libro de Reclamaciones')
@section('claim_subtitle', 'Registra tu reclamo o queja. No necesitas una cuenta.')

@php
    $box = 'w-full border bg-[#f5f6f8] p-[14px] text-[14px] leading-[1.55] text-[#252932] outline-none focus:border-[#071226]';
    $border = fn (string $name) => $errors->has($name) ? 'border-[#b42318]' : 'border-[#d7dae0]';
@endphp

@section('claim_body')
    @include('web.partials.legal-provider')

    <p class="text-[14px] leading-[1.55] text-[#252932]">Respondemos en un plazo máximo de 15 días hábiles. Al enviar, recibirás una copia de tu hoja por el canal indicado.</p>

    <form method="POST" action="{{ route('web.claims.submit') }}" enctype="multipart/form-data" novalidate class="flex flex-col gap-7">
        @csrf

        <div class="hidden" aria-hidden="true">
            <label>No llenes este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <h2 class="text-[21px] font-extrabold leading-[1.55]">1. Datos del consumidor</h2>

        <x-claims.field name="consumer_name" label="Nombres y apellidos *" placeholder="Escribe tu nombre completo" autocomplete="name" />

        <div class="flex flex-col gap-[6px]">
            <label for="claim-document_number" class="text-[13px] font-extrabold leading-[1.55]">Tipo y número de documento *</label>
            <div class="grid grid-cols-[110px_minmax(0,1fr)] gap-2">
                <select name="document_type" aria-label="Tipo de documento" class="{{ $box }} {{ $border('document_number') }}">
                    @foreach (['DNI', 'CE', 'RUC', 'Pasaporte'] as $type)
                        <option value="{{ $type }}" @selected(old('document_type', 'DNI') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <input id="claim-document_number" name="document_number" value="{{ old('document_number') }}" placeholder="Número de documento" data-doc-number="document_type" class="{{ $box }} {{ $border('document_number') }}" @error('document_number') aria-invalid="true" @enderror>
            </div>
            @error('document_number')
                <p class="text-[12px] leading-[1.55] text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <x-claims.field name="address" label="Domicilio *" placeholder="Dirección, distrito, provincia y departamento" autocomplete="street-address" />
        <x-claims.field name="email" type="email" label="Correo electrónico *" placeholder="Para enviarte la respuesta y una copia" autocomplete="email" />
        <x-claims.field name="phone" label="Teléfono (opcional)" placeholder="Número de contacto" autocomplete="tel" />
        <x-claims.field name="order_number" label="Pedido (opcional)" placeholder="Número de tu compra" />

        <p class="text-[13px] leading-[1.55] text-[#252932]">Si el consumidor es menor de edad, completa los datos de su representante.</p>

        <x-claims.field name="guardian_name" label="Representante: nombres y apellidos" placeholder="Solo si corresponde" />

        <div class="flex flex-col gap-[6px]">
            <label for="claim-guardian_document_number" class="text-[13px] font-extrabold leading-[1.55]">Representante: documento</label>
            <div class="grid grid-cols-[110px_minmax(0,1fr)] gap-2">
                <select name="guardian_document_type" aria-label="Tipo de documento del representante" class="{{ $box }} border-[#d7dae0]">
                    @foreach (['DNI', 'CE', 'RUC', 'Pasaporte'] as $type)
                        <option value="{{ $type }}" @selected(old('guardian_document_type', 'DNI') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <input id="claim-guardian_document_number" name="guardian_document_number" value="{{ old('guardian_document_number') }}" placeholder="Solo si corresponde" data-doc-number="guardian_document_type" class="{{ $box }} {{ $border('guardian_document_number') }}" @error('guardian_document_number') aria-invalid="true" @enderror>
            </div>
            @error('guardian_document_number')
                <p class="text-[12px] leading-[1.55] text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <x-claims.field name="guardian_contact" label="Representante: correo o teléfono" placeholder="Solo si corresponde" />

        <h2 class="text-[21px] font-extrabold leading-[1.55]">2. Detalle de la solicitud</h2>

        <fieldset class="flex flex-col gap-[6px]">
            <legend class="mb-[6px] text-[13px] font-extrabold leading-[1.55]">Tipo de solicitud *</legend>
            <div class="flex flex-col gap-1 border {{ $border('claim_type') }} bg-[#f5f6f8] p-[14px] text-[14px] leading-[1.55] text-[#252932]">
                <label class="flex cursor-pointer items-center gap-2"><input type="radio" name="claim_type" value="reclamo" class="h-4 w-4 accent-[#071226]" @checked(old('claim_type', 'reclamo') === 'reclamo')> Reclamo — producto o servicio</label>
                <label class="flex cursor-pointer items-center gap-2"><input type="radio" name="claim_type" value="queja" class="h-4 w-4 accent-[#071226]" @checked(old('claim_type') === 'queja')> Queja — atención recibida</label>
            </div>
            @error('claim_type')
                <p class="text-[12px] leading-[1.55] text-[#b42318]">{{ $message }}</p>
            @enderror
        </fieldset>

        <x-claims.field name="order_product" label="Bien contratado *" placeholder="Producto / Servicio · describe la prenda o compra" />
        <x-claims.field name="claimed_amount" type="number" label="Monto reclamado (si corresponde)" placeholder="S/ 0.00" :attrs="['step' => '0.01', 'min' => '0', 'inputmode' => 'decimal']" />
        <x-claims.field name="claim_description" label="¿Qué ocurrió? *" placeholder="Describe los hechos y la fecha del incidente." rows="3" />
        <x-claims.field name="expected_solution" label="¿Qué solución solicitas? *" placeholder="Indica tu pedido concreto al proveedor." rows="3" />

        <div class="flex flex-col gap-[6px]">
            <label for="claim-supporting_document" class="text-[13px] font-extrabold leading-[1.55]">Documentos de sustento (opcional)</label>
            <input id="claim-supporting_document" name="supporting_document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="{{ $box }} {{ $border('supporting_document') }} file:mr-3 file:border-0 file:bg-transparent file:p-0 file:text-[14px] file:font-bold file:text-[#071226]">
            <p class="text-[12px] leading-[1.55] text-[#687080]">+ Adjuntar comprobante o evidencia (PDF o imagen, hasta 5 MB).</p>
            @error('supporting_document')
                <p class="text-[12px] leading-[1.55] text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <p class="text-[13px] leading-[1.55] text-[#252932]">
            Usaremos estos datos para atender tu solicitud y cumplir las obligaciones aplicables. Consulta la
            <a href="{{ route('web.privacy') }}" data-legal-open="privacy" class="os-focus font-bold underline underline-offset-4">Política de privacidad</a>.
            No se requiere aceptar publicidad.
        </p>
        <p class="text-[12px] leading-[1.55] text-[#252932]">* Campos obligatorios. Presentar una reclamación no impide acudir a otras vías de solución ni es requisito previo para denunciar ante Indecopi.</p>

        <button type="submit" class="os-focus flex h-12 w-full items-center justify-center bg-[#071226] px-4 text-[14px] font-extrabold uppercase leading-[1.35] text-white transition hover:bg-[#0078d7]">Revisar solicitud</button>
    </form>
@endsection
