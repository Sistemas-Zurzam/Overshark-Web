@extends('layouts.admin')
@section('title', 'Banners')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Contenido', 'title' => 'Banners', 'singular' => 'banner', 'description' => 'Administra los banners que aparecen en la tienda.', 'columns' => ['Nombre', 'Modo', 'Duracion', 'Estado', 'Fecha']])
@endsection
