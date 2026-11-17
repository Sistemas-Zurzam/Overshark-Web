@extends('layouts.admin')

@section('title', 'Pasarelas de pago')

@section('content')
    <div class="mb-8">
        <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Configuración</p>
        <h1 class="mt-2 font-admin-display text-4xl uppercase text-admin-text">Pasarelas de pago</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">Administra las credenciales y decide qué pasarelas se ofrecen al comprar en la tienda. Las claves privadas se guardan cifradas.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach ($gateways as $gateway)
            @php
                $fields = match ($gateway->provider) {
                    'culqi' => [['public_key', 'Llave pública'], ['secret_key', 'Llave secreta']],
                    'mercadopago' => [['public_key', 'Public Key'], ['access_token', 'Access Token'], ['client_id', 'Client ID'], ['client_secret', 'Client Secret'], ['webhook_secret', 'Secreto de webhook']],
                    'izipay' => [['shop_id', 'ID de tienda'], ['password', 'Contraseña API REST'], ['public_key', 'Llave pública'], ['hmac_key', 'Clave HMAC-SHA256']],
                };
                $configured = $gateway->isConfigured();
            @endphp
            <section class="overflow-hidden border border-admin-border bg-white shadow-sm">
                <div class="flex items-start justify-between gap-4 border-b border-admin-border p-5 sm:p-6">
                    <div>
                        <h2 class="text-xl font-extrabold text-admin-text">{{ $gateway->label() }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $gateway->provider === 'culqi' ? 'Guarda aquí las llaves para una integración futura del checkout.' : ($gateway->provider === 'mercadopago' ? 'Checkout Pro para enviar al cliente al entorno seguro de Mercado Pago.' : 'Credenciales del checkout web de Izipay.') }}</p>
                    </div>
                    <span @class(['shrink-0 px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide', 'bg-emerald-100 text-emerald-800' => $gateway->isAvailable(), 'bg-amber-100 text-amber-800' => ! $gateway->isAvailable()])>
                        {{ $gateway->isAvailable() ? 'Visible en tienda' : (in_array($gateway->provider, ['izipay', 'mercadopago'], true) && $gateway->enabled ? 'Faltan credenciales' : ($gateway->enabled ? 'Integración pendiente' : ($configured ? 'Desactivada' : 'Faltan credenciales'))) }}
                    </span>
                </div>
                <form action="{{ route('admin.pasarelas-pago.update', $gateway) }}" method="POST" class="grid gap-5 p-5 sm:p-6">
                    @csrf @method('PUT')
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-admin-text">Ambiente</span>
                        <select name="environment" class="w-full border border-admin-border bg-white px-3 py-2.5 text-sm focus:border-admin-accent focus:outline-none">
                            <option value="test" @selected(old('environment', $gateway->environment) === 'test')>Pruebas</option>
                            <option value="production" @selected(old('environment', $gateway->environment) === 'production')>Producción</option>
                        </select>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($fields as [$key, $label])
                            @php($secret = in_array($key, ['secret_key', 'access_token', 'client_secret', 'webhook_secret', 'password', 'hmac_key'], true))
                            <label class="block {{ in_array($key, ['webhook_secret'], true) ? 'sm:col-span-2' : '' }}">
                                <span class="mb-1.5 block text-sm font-bold text-admin-text">{{ $label }} @if ($gateway->credential($key)) <span class="font-medium text-emerald-700">· guardada</span> @endif</span>
                                <input type="{{ $secret ? 'password' : 'text' }}" name="credentials[{{ $key }}]" value="" autocomplete="new-password" placeholder="{{ $gateway->credential($key) ? 'Deja vacío para conservar la clave' : 'Pega aquí la credencial' }}" class="w-full border border-admin-border bg-admin-canvas px-3 py-2.5 text-sm outline-none focus:border-admin-accent">
                                @error("credentials.$key") <span class="mt-1 block text-xs font-semibold text-red-700">{{ $message }}</span> @enderror
                            </label>
                        @endforeach
                    </div>
                    @if ($gateway->provider === 'mercadopago')
                        <div class="border-l-2 border-admin-accent bg-admin-canvas px-4 py-3 text-sm text-slate-700">
                            <p class="font-extrabold text-admin-text">Webhook para registrar en Mercado Pago</p>
                            <code class="mt-1 block break-all text-xs">{{ route('web.payments.mercadopago.webhook') }}</code>
                            <p class="mt-1">En Tus integraciones, abre esta aplicación y entra a Webhooks &gt; Configurar notificaciones. En Modo productivo registra esta URL, activa el evento Pagos y guarda la configuración. Copia aquí la clave secreta que genera Mercado Pago.</p>
                        </div>
                    @endif
                    <label class="flex items-start gap-3 border border-admin-border bg-admin-canvas p-3 text-sm">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $gateway->enabled)) class="mt-0.5 size-4 accent-[#0078D7]">
                        <span><strong class="block text-admin-text">Activar esta pasarela</strong><span class="mt-0.5 block text-slate-600">Aparecerá al cliente cuando tenga credenciales válidas y procesamiento de pagos disponible.</span></span>
                    </label>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-admin-border pt-4">
                        <button type="submit" class="bg-admin-text px-5 py-2.5 text-sm font-extrabold text-white transition hover:bg-admin-accent">Guardar configuración</button>
                        @if ($configured)
                            <button type="submit" form="clear-gateway-{{ $gateway->id }}" class="px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50" onclick="return confirm('¿Eliminar credenciales guardadas de {{ $gateway->label() }}?')">Eliminar credenciales</button>
                        @endif
                    </div>
                </form>
                @if ($configured)
                    <form id="clear-gateway-{{ $gateway->id }}" action="{{ route('admin.pasarelas-pago.clear', $gateway) }}" method="POST" class="hidden">@csrf @method('DELETE')</form>
                @endif
            </section>
        @endforeach
    </div>
    <p class="mt-6 max-w-4xl text-xs leading-5 text-slate-500">Culqi queda preparado como proveedor configurable, pero no se publica en el checkout de esta versión. Izipay utiliza la integración ya implementada; Mercado Pago confirma pagos mediante webhook firmado. No copies llaves privadas a chats ni a JavaScript.</p>
@endsection
