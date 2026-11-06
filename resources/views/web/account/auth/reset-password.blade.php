@extends('web.account.auth.layout')

@section('title', 'Nueva contraseña | Overshark')
@section('heading', 'Crea tu nueva contraseña')
@section('lead', 'Elige una contraseña que no uses en otros sitios.')

@section('form')
    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @include('web.account.partials.field', ['name' => 'email', 'label' => 'Correo electrónico', 'type' => 'email', 'autocomplete' => 'email', 'value' => $email])
        @include('web.account.partials.field', ['name' => 'password', 'label' => 'Contraseña nueva', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Mínimo 8 caracteres, con letras y números.'])
        @include('web.account.partials.field', ['name' => 'password_confirmation', 'label' => 'Repite la contraseña', 'type' => 'password', 'autocomplete' => 'new-password'])
        <button type="submit" class="os-checkout-btn os-focus w-full">GUARDAR CONTRASEÑA</button>
    </form>
@endsection

@section('footer-links')
    <a href="{{ route('password.request') }}" class="font-bold text-[#2467cf] hover:underline">Pedir otro enlace</a>
@endsection
