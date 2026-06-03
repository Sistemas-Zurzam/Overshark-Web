@extends('layouts.admin')
@section('title', 'Usuarios')
@section('content')
    @include('admin.partials.module-index', ['eyebrow' => 'Acceso', 'title' => 'Usuarios', 'singular' => 'usuario', 'description' => 'Administra usuarios, roles y acceso al panel.', 'columns' => ['Nombre', 'Email', 'Rol', 'Estado', 'Fecha']])
@endsection
