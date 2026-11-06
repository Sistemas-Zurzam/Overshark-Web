@extends('web.account.layout')

@section('title', 'Mis direcciones | Overshark')
@section('heading', 'Mis direcciones')

@php
    $card = 'border border-[#dae0e5] bg-white p-5 sm:p-6';
    $editing = $address !== null;
    $district = $editing ? $address->distrito_id : old('distrito_id');
    $recipientDefault = auth('customer')->user()->fullName();
@endphp

@section('account')
    <p class="max-w-[640px] text-[14px] leading-[1.5] text-[#65717c]">Guarda tus direcciones de delivery y elígelas con un clic al comprar. Los pedidos ya hechos conservan su propia dirección.</p>

    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        @forelse ($addresses as $saved)
            <article @class(['flex flex-col gap-3 border p-5', 'border-[#0b1015]' => $saved->is_default, 'border-[#dae0e5]' => ! $saved->is_default])>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-[16px] font-extrabold">{{ $saved->alias }}</p>
                    @if ($saved->is_default)<span class="rounded-full bg-[#0b1015] px-2.5 py-0.5 text-[11px] font-extrabold text-white">Principal</span>@endif
                </div>
                <div class="text-[14px] leading-[1.5]">
                    <p>{{ $saved->address }}</p>
                    <p class="text-[#65717c]">{{ $saved->placeLabel() }}</p>
                    @if ($saved->reference)<p class="text-[#65717c]">Ref.: {{ $saved->reference }}</p>@endif
                    <p class="mt-1 text-[#65717c]">Recibe {{ $saved->recipient }} · {{ $saved->phone }}</p>
                </div>
                <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-[#eef1f4] pt-3 text-[13px] font-bold">
                    <a href="{{ route('customer.addresses.edit', $saved->id) }}" class="os-focus text-[#2467cf] hover:underline">Editar</a>
                    @unless ($saved->is_default)
                        <form method="POST" action="{{ route('customer.addresses.default', $saved->id) }}">@csrf @method('PATCH')<button type="submit" class="os-focus text-[#0b1015] hover:underline">Hacer principal</button></form>
                    @endunless
                    <form method="POST" action="{{ route('customer.addresses.destroy', $saved->id) }}" onsubmit="return confirm('¿Eliminar esta dirección?')">@csrf @method('DELETE')<button type="submit" class="os-focus text-[#a3261c] hover:underline">Eliminar</button></form>
                </div>
            </article>
        @empty
            <p class="border border-dashed border-[#dae0e5] p-6 text-center text-[14px] text-[#65717c] sm:col-span-2">Aún no guardaste direcciones.</p>
        @endforelse
    </div>

    <section class="{{ $card }} mt-8" aria-labelledby="ad-form">
        <h2 id="ad-form" class="text-[18px] font-extrabold">{{ $editing ? 'Editar dirección' : 'Nueva dirección' }}</h2>
        <form method="POST" action="{{ $editing ? route('customer.addresses.update', $address->id) : route('customer.addresses.store') }}" class="mt-4 flex flex-col gap-4" novalidate
              data-address-form data-ubigeo='@json($ubigeo)' data-distrito="{{ $district }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="os-ck-field">
                <label for="f-alias" class="font-bold">Nombre de la dirección</label>
                <select id="f-alias" name="alias" class="os-ck-input !h-11">
                    @foreach (\App\Http\Controllers\Customer\AddressController::ALIASES as $alias)
                        <option value="{{ $alias }}" @selected(old('alias', $address->alias ?? 'Casa') === $alias)>{{ $alias }}</option>
                    @endforeach
                </select>
                @error('alias')<p class="text-[12px] font-semibold text-[#a3261c]" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @include('web.account.partials.field', ['name' => 'recipient', 'label' => 'Quién recibe', 'value' => $address->recipient ?? $recipientDefault, 'autocomplete' => 'name'])
                @include('web.account.partials.field', ['name' => 'phone', 'label' => 'Celular de contacto', 'type' => 'tel', 'value' => $address->phone ?? auth('customer')->user()->phone, 'autocomplete' => 'tel-national', 'inputmode' => 'numeric', 'maxlength' => 9, 'placeholder' => '999 999 999'])
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <label class="os-ck-field"><span class="font-bold">Departamento</span>
                    <select class="os-ck-input !h-11" data-ad-departamento required><option value="">Elige</option></select>
                </label>
                <label class="os-ck-field"><span class="font-bold">Provincia</span>
                    <select class="os-ck-input !h-11" data-ad-provincia required disabled><option value="">Elige</option></select>
                </label>
                <div class="os-ck-field">
                    <label for="f-distrito_id" class="font-bold">Distrito</label>
                    <select id="f-distrito_id" name="distrito_id" class="os-ck-input !h-11" data-ad-distrito required disabled><option value="">Elige</option></select>
                    @error('distrito_id')<p class="text-[12px] font-semibold text-[#a3261c]" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            @include('web.account.partials.field', ['name' => 'address', 'label' => 'Dirección', 'value' => $address->address ?? '', 'autocomplete' => 'street-address', 'placeholder' => 'Calle, número, interior o departamento'])
            @include('web.account.partials.field', ['name' => 'reference', 'label' => 'Referencia (opcional)', 'value' => $address->reference ?? '', 'required' => false, 'placeholder' => 'Ayúdanos a encontrar tu dirección'])

            <label class="flex cursor-pointer items-center gap-2 text-[14px]">
                <input type="checkbox" name="is_default" value="1" class="os-ck-radio" @checked(old('is_default', $address->is_default ?? false))>
                Usar como mi dirección principal
            </label>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button type="submit" class="os-checkout-btn os-focus w-full sm:w-[240px]">{{ $editing ? 'GUARDAR CAMBIOS' : 'GUARDAR DIRECCIÓN' }}</button>
                @if ($editing)<a href="{{ route('customer.addresses') }}" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full sm:w-[160px]">CANCELAR</a>@endif
            </div>
            <p class="text-[12px] text-[#65717c]">El delivery a domicilio solo cubre Lima. Al comprar confirmamos la ubicación en el mapa.</p>
        </form>
    </section>
@endsection
