@extends('web.account.auth.layout')

@section('title', 'Crear cuenta | Overshark')
@section('heading', 'Crear cuenta')
@section('lead', 'Guarda tus datos y sigue tus pedidos. Solo necesitamos lo básico; el resto lo pedimos al comprar.')

@section('form')
    <form method="POST" action="{{ route('customer.register.store') }}" class="flex flex-col gap-4" novalidate>
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            @include('web.account.partials.field', ['name' => 'name', 'label' => 'Nombres', 'autocomplete' => 'given-name'])
            @include('web.account.partials.field', ['name' => 'apellidos', 'label' => 'Apellidos', 'autocomplete' => 'family-name'])
        </div>
        @include('web.account.partials.field', ['name' => 'email', 'label' => 'Correo electrónico', 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => 'nombre@correo.com'])
        @include('web.account.partials.field', ['name' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Mínimo 8 caracteres, con letras y números.'])
        @include('web.account.partials.field', ['name' => 'password_confirmation', 'label' => 'Repite la contraseña', 'type' => 'password', 'autocomplete' => 'new-password'])
        <button type="submit" class="os-checkout-btn os-focus w-full">CREAR CUENTA</button>
    </form>
@endsection

@section('footer-links')
    ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-bold text-[#2467cf] hover:underline">Inicia sesión</a>
@endsection
