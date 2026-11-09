{{-- Hoja registrada (número, consumidor, detalle y plazos): la comparten "Solicitud registrada" y "Constancia". --}}
@php
    $amount = $claim->claimed_amount !== null ? 'S/ '.number_format((float) $claim->claimed_amount, 2) : 'no indicado';
@endphp

@include('web.partials.legal-provider')

<div class="text-[17px] font-extrabold leading-[1.55]">
    <p>HOJA N.º {{ $claim->sheet_number }}</p>
    <p>Fecha: {{ $claim->created_at->format('d/m/Y') }}</p>
    <p>Marca: {{ $claim->brand }}</p>
    <p>Tipo: {{ $claim->typeLabel() }}</p>
</div>

<div class="text-[14px] leading-[1.55] text-[#252932]">
    <p>Consumidor: {{ $claim->consumer_name }} · {{ $claim->document_type }} {{ $claim->document_number }}</p>
    <p>Domicilio: {{ $claim->address }}</p>
    <p>Correo: {{ $claim->email }}</p>
    @if (filled($claim->phone))
        <p>Teléfono: {{ $claim->phone }}</p>
    @endif
    @if (filled($claim->order_number))
        <p>Pedido: {{ $claim->order_number }}</p>
    @endif
    @if (filled($claim->guardian_name))
        <p>Representante: {{ $claim->guardian_name }} · {{ $claim->guardian_document_type }} {{ $claim->guardian_document_number }} · {{ $claim->guardian_contact }}</p>
    @endif
    <p>Bien contratado: {{ $claim->order_product }}</p>
    <p>Monto reclamado: {{ $amount }}</p>
</div>

<div class="text-[15px] leading-[1.55] text-[#252932]">
    <p>Detalle: {{ $claim->claim_description }}</p>
    <p>Pedido: {{ $claim->expected_solution }}</p>
</div>

<div class="text-[15px] leading-[1.55] text-[#252932]">
    <p>Respuesta: máximo 15 días hábiles.</p>
    <p>Canal elegido: correo electrónico.</p>
    <p>Acciones del proveedor: {{ $claim->isAnswered() ? 'atendido' : 'pendiente de atención' }}.</p>
</div>

<p class="text-[13px] leading-[1.55] text-[#252932]">Conserva una copia de esta hoja. El registro no limita tu derecho a acudir a otras vías de solución.</p>
