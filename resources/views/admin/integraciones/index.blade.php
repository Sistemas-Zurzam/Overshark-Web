@extends('layouts.admin')

@section('title', 'Integraciones')

@php
    $card = 'overflow-hidden border border-admin-border bg-white shadow-sm';
    $input = 'w-full border border-admin-border bg-admin-canvas px-3 py-2.5 text-sm outline-none focus:border-admin-accent';
    $label = 'mb-1.5 block text-sm font-bold text-admin-text';
    $primary = 'bg-admin-navy px-5 py-2.5 text-xs font-extrabold uppercase tracking-[1px] text-white';
    $secondary = 'border border-admin-border px-4 py-2.5 text-sm font-bold text-admin-text hover:text-admin-accent';
    $danger = 'px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50';
    $code = 'block overflow-x-auto whitespace-pre bg-admin-canvas px-3 py-2 font-mono text-xs text-admin-text';
    $connectionInfo = [
        'crm' => ['title' => 'CRM Livex', 'text' => 'Cada pedido pagado se registra como venta en el CRM (nota de venta, Zazu y boleta).', 'toggle' => 'Enviar los pedidos pagados al CRM', 'env' => config('services.crm.send_sales')],
        'zazu' => ['title' => 'Zazu', 'text' => 'Inventario (stock y precios cada 5 minutos) y agencias Shalom (cada 6 horas).', 'toggle' => 'Sincronizar inventario y agencias automáticamente', 'env' => true],
        'pixels' => ['title' => 'Píxeles de anuncios', 'text' => 'Meta, Google Analytics 4 y TikTok en la tienda: visitas, producto visto, agregar al carrito, inicio de pago y compra.', 'toggle' => 'Cargar los píxeles en la tienda', 'env' => false],
    ];
    $envValue = fn (string $provider, string $key) => match ($provider) {
        'crm' => config('services.crm.'.$key),
        'zazu' => $key === 'companies' ? implode(',', (array) config('services.zazu.companies')) : config('services.zazu.'.$key),
        default => null,
    };
@endphp

@section('content')
    <div class="mb-8">
        <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Configuración</p>
        <h1 class="mt-2 font-admin-display text-4xl uppercase text-admin-text">Integraciones</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">Conecta la tienda con otros sistemas: lo que Overshark envía (CRM, Zazu, píxeles) y lo que otros sistemas pueden leer o actualizar con una clave API, más los avisos automáticos de pedidos. Las claves y secretos se guardan cifrados.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('new_api_key'))
        <div class="mb-5 border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            <p class="font-extrabold">Clave API nueva: cópiala y guárdala en el otro sistema. No se volverá a mostrar.</p>
            <input type="text" readonly value="{{ session('new_api_key') }}" onclick="this.select()" class="mt-2 w-full border border-amber-300 bg-white px-3 py-2 font-mono text-sm" aria-label="Clave API nueva">
        </div>
    @endif

    {{-- 1. Conexiones de salida --}}
    <h2 class="mb-3 text-xl font-extrabold text-admin-text">Conexiones de Overshark con otros sistemas</h2>
    <div class="grid gap-5 xl:grid-cols-3">
        @foreach ($connections as $provider => $connection)
            @php
                $info = $connectionInfo[$provider];
            @endphp
            <section class="{{ $card }}">
                <div class="border-b border-admin-border p-5">
                    <h3 class="text-lg font-extrabold text-admin-text">{{ $info['title'] }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $info['text'] }}</p>
                    @unless ($connection->exists)
                        <p class="mt-1 text-xs font-bold text-slate-500">Hoy usa la configuración del servidor (.env). Al guardar aquí, el panel manda.</p>
                    @endunless
                </div>
                <form method="POST" action="{{ route('admin.integraciones.connections.update', $provider) }}" class="grid gap-4 p-5">
                    @csrf @method('PUT')
                    @foreach ($fields[$provider] as [$key, $text, $secret])
                        @php
                            $saved = $connection->value($key);
                            $env = $envValue($provider, $key);
                        @endphp
                        <label class="block">
                            <span class="{{ $label }}">{{ $text }}
                                @if ($secret && $saved) <span class="font-medium text-emerald-700">· guardada</span>
                                @elseif ($secret && $env) <span class="font-medium text-slate-500">· en .env</span> @endif
                            </span>
                            <input type="{{ $secret ? 'password' : 'text' }}" name="settings[{{ $key }}]" value="{{ $secret ? '' : old("settings.$key", $saved) }}" autocomplete="off"
                                placeholder="{{ $secret ? ($saved ? 'Deja vacío para conservarla' : ($env ? 'Vacío = usa la del .env' : 'Pega aquí la credencial')) : ($env ? 'Vacío = '.$env : '') }}" class="{{ $input }}">
                        </label>
                    @endforeach
                    <label class="flex items-start gap-3 border border-admin-border bg-admin-canvas p-3 text-sm">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $connection->exists ? $connection->enabled : $info['env'])) class="mt-0.5 size-4">
                        <strong class="text-admin-text">{{ $info['toggle'] }}</strong>
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="{{ $primary }}">Guardar</button>
                        @if (in_array($provider, ['crm', 'zazu'], true))
                            <button type="submit" form="test-{{ $provider }}" class="{{ $secondary }}">Probar conexión</button>
                        @endif
                    </div>
                </form>
                @if (in_array($provider, ['crm', 'zazu'], true))
                    <form id="test-{{ $provider }}" method="POST" action="{{ route('admin.integraciones.connections.test', $provider) }}" class="hidden">@csrf</form>
                @endif
            </section>
        @endforeach
    </div>

    {{-- 2. API de Overshark --}}
    <h2 class="mb-3 mt-10 text-xl font-extrabold text-admin-text">API de Overshark: otros sistemas se conectan a tu tienda</h2>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <section class="{{ $card }}">
            <div class="border-b border-admin-border p-5">
                <h3 class="text-lg font-extrabold text-admin-text">Claves API</h3>
                <p class="mt-1 text-sm text-slate-600">Crea una clave por sistema (ERP, courier, marketplace) con solo los permisos que necesita. Si una clave se filtra, revócala y crea otra.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="border-b border-admin-border bg-admin-canvas text-left text-xs font-extrabold uppercase tracking-[1px] text-slate-500">
                        <tr><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Clave</th><th class="px-4 py-3">Permisos</th><th class="px-4 py-3">Último uso</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($apiKeys as $apiKey)
                            <tr class="border-b border-admin-border last:border-b-0">
                                <td class="px-4 py-3 font-bold text-admin-text">{{ $apiKey->name }}</td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $apiKey->token_prefix }}…</td>
                                <td class="px-4 py-3 text-xs">{{ collect($apiKey->abilities)->map(fn ($ability) => $abilities[$ability] ?? $ability)->implode(' · ') }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $apiKey->last_used_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($apiKey->revoked_at)
                                        <span class="text-xs font-bold text-slate-500">Revocada el {{ $apiKey->revoked_at->format('d/m/Y') }}</span>
                                    @else
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer text-sm font-bold text-red-700">Revocar</summary>
                                            <form method="POST" action="{{ route('admin.integraciones.keys.revoke', $apiKey) }}" class="mt-2">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="{{ $danger }} border border-red-200">Sí, revocar «{{ $apiKey->name }}»</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Aún no hay claves. Crea una para el primer sistema que se conectará.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form method="POST" action="{{ route('admin.integraciones.keys.store') }}" class="grid gap-4 border-t border-admin-border p-5">
                @csrf
                <label class="block">
                    <span class="{{ $label }}">Nueva clave: nombre del sistema</span>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="Ej.: ERP Zurzam, Courier, Catálogo de Meta" class="{{ $input }}">
                </label>
                <fieldset>
                    <legend class="{{ $label }}">Permisos</legend>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        @foreach ($abilities as $ability => $text)
                            <label class="flex items-center gap-2"><input type="checkbox" name="abilities[]" value="{{ $ability }}" @checked(in_array($ability, old('abilities', []), true)) class="size-4"> {{ $text }} <code class="text-xs text-slate-500">{{ $ability }}</code></label>
                        @endforeach
                    </div>
                </fieldset>
                <div><button type="submit" class="{{ $primary }}">Crear clave</button></div>
            </form>
        </section>

        <section class="{{ $card }} p-5 text-sm text-slate-700">
            <h3 class="text-lg font-extrabold text-admin-text">Cómo usar la API</h3>
            <p class="mt-2">URL base:</p>
            <code class="{{ $code }}">{{ $apiBase }}</code>
            <p class="mt-3">Envía la clave en cada solicitud:</p>
            <code class="{{ $code }}">Authorization: Bearer osk_…</code>
            <ul class="mt-3 space-y-1.5">
                <li><code class="font-mono text-xs">GET /ping</code> · prueba la clave</li>
                <li><code class="font-mono text-xs">GET /products</code> · catálogo con variantes y stock (<code class="font-mono text-xs">q</code>, <code class="font-mono text-xs">updated_since</code>, <code class="font-mono text-xs">in_stock=1</code>, <code class="font-mono text-xs">page</code>, <code class="font-mono text-xs">per_page</code>) · catalog.read</li>
                <li><code class="font-mono text-xs">GET /products/{id}</code> · un producto · catalog.read</li>
                <li><code class="font-mono text-xs">GET /orders</code> · pedidos confirmados (<code class="font-mono text-xs">status</code>, <code class="font-mono text-xs">from</code>, <code class="font-mono text-xs">to</code>, <code class="font-mono text-xs">updated_since</code>) · orders.read</li>
                <li><code class="font-mono text-xs">GET /orders/{id}</code> · un pedido · orders.read</li>
                <li><code class="font-mono text-xs">PATCH /orders/{id}/status</code> · <code class="font-mono text-xs">{"status": "Enviado"}</code>, Entregado o Cancelado · orders.write</li>
            </ul>
            <p class="mt-3">Ejemplo:</p>
            <code class="{{ $code }}">curl {{ $apiBase }}/orders \
  -H "Authorization: Bearer osk_…"</code>
            <p class="mt-3 text-xs text-slate-500">Respuestas en JSON. Límite: 120 solicitudes por minuto por clave. Errores: 401 clave inválida, 403 sin permiso, 404 no existe, 409 estado no permitido, 422 datos inválidos.</p>
        </section>
    </div>

    {{-- 3. Webhooks --}}
    <h2 class="mb-3 mt-10 text-xl font-extrabold text-admin-text">Webhooks: avisos automáticos de pedidos</h2>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="grid gap-5">
            @foreach ($webhooks as $webhook)
                <section class="{{ $card }}">
                    <form method="POST" action="{{ route('admin.integraciones.webhooks.update', $webhook) }}" class="grid gap-4 p-5">
                        @csrf @method('PUT')
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-extrabold text-admin-text">{{ $webhook->name }}</h3>
                                <p class="mt-1 text-xs text-slate-500">
                                    @if ($webhook->last_attempt_at)
                                        Último envío {{ $webhook->last_attempt_at->format('d/m/Y H:i') }}:
                                        <strong @class(['text-emerald-700' => ! $webhook->last_error, 'text-red-700' => $webhook->last_error])>{{ $webhook->last_error ?: 'HTTP '.$webhook->last_status }}</strong>
                                    @else
                                        Aún no se envió ningún aviso.
                                    @endif
                                </p>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="enabled" value="1" @checked($webhook->enabled) class="size-4"> Activo</label>
                        </div>
                        <input type="hidden" name="name" value="{{ $webhook->name }}">
                        <label class="block"><span class="{{ $label }}">URL de destino</span><input type="url" name="url" value="{{ $webhook->url }}" class="{{ $input }}"></label>
                        <fieldset>
                            <legend class="{{ $label }}">Eventos</legend>
                            <div class="grid gap-1.5 text-sm sm:grid-cols-2">
                                @foreach ($events as $event => $text)
                                    <label class="flex items-center gap-2"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, $webhook->events ?? [], true)) class="size-4"> {{ $text }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                        <details class="text-sm">
                            <summary class="cursor-pointer font-bold text-admin-accent">Ver secreto para validar la firma</summary>
                            <input type="text" readonly value="{{ $webhook->secret }}" onclick="this.select()" class="{{ $input }} mt-2 font-mono" aria-label="Secreto del webhook">
                        </details>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="{{ $primary }}">Guardar</button>
                            <button type="submit" form="test-webhook-{{ $webhook->id }}" class="{{ $secondary }}">Enviar prueba</button>
                            <details class="ml-auto">
                                <summary class="cursor-pointer text-sm font-bold text-red-700">Eliminar</summary>
                                <button type="submit" form="delete-webhook-{{ $webhook->id }}" class="{{ $danger }} mt-2 border border-red-200">Sí, eliminar</button>
                            </details>
                        </div>
                    </form>
                    <form id="test-webhook-{{ $webhook->id }}" method="POST" action="{{ route('admin.integraciones.webhooks.test', $webhook) }}" class="hidden">@csrf</form>
                    <form id="delete-webhook-{{ $webhook->id }}" method="POST" action="{{ route('admin.integraciones.webhooks.destroy', $webhook) }}" class="hidden">@csrf @method('DELETE')</form>
                </section>
            @endforeach

            <section class="{{ $card }}">
                <form method="POST" action="{{ route('admin.integraciones.webhooks.store') }}" class="grid gap-4 p-5">
                    @csrf
                    <h3 class="text-lg font-extrabold text-admin-text">Nuevo webhook</h3>
                    <label class="block"><span class="{{ $label }}">Nombre</span><input type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="Ej.: ERP, Courier" class="{{ $input }}"></label>
                    <label class="block"><span class="{{ $label }}">URL de destino</span><input type="url" name="url" value="{{ old('url') }}" placeholder="https://" class="{{ $input }}"></label>
                    <fieldset>
                        <legend class="{{ $label }}">Eventos</legend>
                        <div class="grid gap-1.5 text-sm sm:grid-cols-2">
                            @foreach ($events as $event => $text)
                                <label class="flex items-center gap-2"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, old('events', ['order.confirmed']), true)) class="size-4"> {{ $text }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div><button type="submit" class="{{ $primary }}">Crear webhook</button></div>
                </form>
            </section>
        </div>

        <section class="{{ $card }} p-5 text-sm text-slate-700">
            <h3 class="text-lg font-extrabold text-admin-text">Qué recibe el destino</h3>
            <p class="mt-2">Un <strong>POST</strong> JSON por evento, con el pedido completo (mismo formato que <code class="font-mono text-xs">GET /orders/{id}</code>):</p>
            <code class="{{ $code }}">{"id": "…", "event": "order.confirmed",
 "created_at": "…", "data": { …pedido… }}</code>
            <p class="mt-3">Encabezados:</p>
            <code class="{{ $code }}">X-Overshark-Event: order.confirmed
X-Overshark-Delivery: (id del aviso)
X-Overshark-Signature: sha256=…</code>
            <p class="mt-3">Valida la firma calculando HMAC-SHA256 del cuerpo recibido con el secreto del webhook y compárala con <code class="font-mono text-xs">X-Overshark-Signature</code>. Responde 2xx en menos de 10 segundos; si no, se reintenta hasta 5 veces (1 min, 5 min, 30 min, 2 h y 6 h).</p>
        </section>
    </div>
@endsection
