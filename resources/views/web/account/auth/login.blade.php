@extends('web.account.auth.layout')

@section('title', 'Iniciar sesión | Overshark')
@section('heading', 'Iniciar sesión')
@section('lead', 'Entra para ver tus pedidos y comprar más rápido. Tu carrito se mantiene.')

@section('form')
    <form method="POST" action="{{ route('customer.login.store') }}" class="flex flex-col gap-4" novalidate>
        @csrf
        @include('web.account.partials.field', ['name' => 'email', 'label' => 'Correo electrónico', 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => 'nombre@correo.com'])
        @include('web.account.partials.field', ['name' => 'password', 'label' => 'Contraseña', 'type' => 'password', 'autocomplete' => 'current-password'])
        <div class="flex items-center justify-between gap-3 text-[13px]">
            <label class="flex cursor-pointer items-center gap-2"><input type="checkbox" name="remember" value="1" class="os-ck-radio" @checked(old('remember'))> Recordarme</label>
            <a href="{{ route('password.request') }}" class="font-bold text-[#2467cf] hover:underline">¿Olvidaste tu contraseña?</a>
        </div>
        <button type="submit" class="os-checkout-btn os-focus w-full">INICIAR SESIÓN</button>
    </form>
@endsection

@section('footer-links')
    ¿Aún no tienes cuenta? <a href="{{ route('customer.register') }}" class="font-bold text-[#2467cf] hover:underline">Crear cuenta</a>
@endsection
