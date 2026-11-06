@extends('web.account.layout')

@section('title', 'Mi perfil | Overshark')
@section('heading', 'Mi perfil')

@php($card = 'border border-[#dae0e5] bg-white p-5 sm:p-6')

@section('account')
    <div class="grid grid-cols-[minmax(0,1fr)] gap-6 xl:grid-cols-2 xl:items-start">
        <section class="{{ $card }}" aria-labelledby="pf-data">
            <h2 id="pf-data" class="text-[18px] font-extrabold">Datos personales</h2>
            <p class="mt-1 text-[13px] text-[#65717c]">Cambiar esto no modifica tus pedidos anteriores.</p>
            <form method="POST" action="{{ route('customer.profile.update') }}" class="mt-4 flex flex-col gap-4" novalidate>
                @csrf @method('PUT')
                @include('web.account.partials.field', ['name' => 'name', 'label' => 'Nombres', 'value' => $account->name, 'autocomplete' => 'given-name'])
                @include('web.account.partials.field', ['name' => 'apellidos', 'label' => 'Apellidos', 'value' => $account->apellidos, 'autocomplete' => 'family-name'])
                @include('web.account.partials.field', ['name' => 'phone', 'label' => 'Celular', 'type' => 'tel', 'value' => $account->phone, 'required' => false, 'autocomplete' => 'tel-national', 'inputmode' => 'numeric', 'maxlength' => 9, 'placeholder' => '999 999 999'])
                <button type="submit" class="os-checkout-btn os-focus w-full sm:w-[220px]">GUARDAR CAMBIOS</button>
            </form>
        </section>

        <div class="flex flex-col gap-6" id="seguridad">
            <section class="{{ $card }}" aria-labelledby="pf-email">
                <h2 id="pf-email" class="text-[18px] font-extrabold">Correo electrónico</h2>
                <p class="mt-1 text-[14px]">Actual: <strong>{{ $account->email }}</strong> @if ($account->hasVerifiedEmail()) <span class="ml-1 rounded-full bg-[#e3f4ec] px-2 py-0.5 text-[11px] font-extrabold text-[#147d5b]">Verificado</span>@endif</p>
                @if ($account->pending_email)
                    <p class="mt-2 border border-[#f0d9a8] bg-[#fff6e0] px-3 py-2 text-[13px] text-[#8a5a00]">Esperando que confirmes <strong>{{ $account->pending_email }}</strong> desde el enlace que te enviamos.</p>
                @endif
                <form method="POST" action="{{ route('customer.email.request') }}" class="mt-4 flex flex-col gap-4" novalidate>
                    @csrf
                    @include('web.account.partials.field', ['name' => 'new_email', 'label' => 'Correo nuevo', 'type' => 'email', 'autocomplete' => 'email'])
                    @include('web.account.partials.field', ['name' => 'email_password', 'idSuffix' => 'em-', 'label' => 'Tu contraseña', 'type' => 'password', 'autocomplete' => 'current-password', 'hint' => 'La pedimos para confirmar que eres tú.'])
                    <button type="submit" class="os-checkout-btn os-checkout-btn--ghost os-focus w-full sm:w-[220px]">ENVIAR CONFIRMACIÓN</button>
                </form>
            </section>

            <section class="{{ $card }}" aria-labelledby="pf-pass">
                <h2 id="pf-pass" class="text-[18px] font-extrabold">Contraseña</h2>
                <form method="POST" action="{{ route('customer.password.update') }}" class="mt-4 flex flex-col gap-4" novalidate>
                    @csrf @method('PUT')
                    @include('web.account.partials.field', ['name' => 'current_password', 'idSuffix' => 'pw-', 'label' => 'Contraseña actual', 'type' => 'password', 'autocomplete' => 'current-password'])
                    @include('web.account.partials.field', ['name' => 'password', 'label' => 'Contraseña nueva', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Mínimo 8 caracteres, con letras y números.'])
                    @include('web.account.partials.field', ['name' => 'password_confirmation', 'label' => 'Repite la contraseña nueva', 'type' => 'password', 'autocomplete' => 'new-password'])
                    <button type="submit" class="os-checkout-btn os-focus w-full sm:w-[220px]">CAMBIAR CONTRASEÑA</button>
                </form>
            </section>
        </div>
    </div>
@endsection
