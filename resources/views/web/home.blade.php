@extends('layouts.web')

@section('title', 'Overshark | Tienda')

{{--
    Inicio de Overshark Hombre: landing "Nacido del océano" (prototipo overshark-landing), escritorio y celular.
    La línea mujer usa web/home-mujer.blade.php. Detalle de la vista: web/partials/landing-hombre.blade.php.
--}}

@section('content')
    @include('web.partials.landing-hombre')
@endsection
