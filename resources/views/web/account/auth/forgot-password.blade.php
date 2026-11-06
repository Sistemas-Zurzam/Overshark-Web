@extends('web.account.auth.layout')

@section('title', 'Recuperar contraseña | Overshark')
@section('heading', 'Recupera tu contraseña')
@section('lead', 'Escribe el correo de tu cuenta y te enviaremos un enlace para crear una contraseña nueva.')

@section('form')
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4" novalidate>
        @csrf
        @include('web.account.partials.field', ['name' => 'email', 'label' => 'Correo electrónico', 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => 'nombre@correo.com'])
        <button type="submit" class="os-checkout-btn os-focus w-full">ENVIAR ENLACE</button>
    </form>
@endsection

@section('footer-links')
    <a href="{{ route('login') }}" class="font-bold text-[#2467cf] hover:underline">Volver a iniciar sesión</a>
@endsection
