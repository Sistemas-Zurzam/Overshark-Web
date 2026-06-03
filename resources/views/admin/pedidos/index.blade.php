@extends('layouts.admin')
@section('title', 'Pedidos')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Ventas', 'title' => 'Pedidos', 'singular' => 'pedido', 'description' => 'Consulta y administra los pedidos de la tienda.', 'columns' => ['Numero', 'Cliente', 'Estado', 'Medio de pago', 'Total', 'Fecha']])
@endsection
