<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use App\Services\Payments\MercadoPagoGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::query()->get()
            ->sortBy(fn (PaymentGateway $gateway) => array_search($gateway->provider, PaymentGateway::PROVIDERS, true))
            ->values();

        return view('admin.pasarelas-pago.index', compact('gateways'));
    }

    public function update(Request $request, PaymentGateway $gateway, MercadoPagoGateway $mercadoPago)
    {
        $keys = match ($gateway->provider) {
            'culqi' => ['public_key', 'secret_key'],
            'mercadopago' => ['public_key', 'access_token', 'client_id', 'client_secret', 'webhook_secret'],
            'izipay' => ['shop_id', 'password', 'public_key', 'hmac_key'],
        };
        $validated = $request->validate([
            'environment' => ['required', Rule::in(['test', 'production'])],
            'enabled' => ['nullable', 'boolean'],
            ...collect($keys)->mapWithKeys(fn ($key) => ["credentials.$key" => ['nullable', 'string', 'max:4096']])->all(),
        ]);

        $credentials = $gateway->credentials ?? [];
        foreach ($keys as $key) {
            $value = trim((string) data_get($validated, "credentials.$key", ''));
            if ($value !== '') {
                $credentials[$key] = $value;
            }
        }

        $gateway->credentials = $credentials;
        $gateway->environment = $validated['environment'];
        $gateway->enabled = (bool) ($validated['enabled'] ?? false);
        if ($gateway->enabled && ! $gateway->isConfigured()) {
            return back()->withInput()->withErrors(['credentials' => 'Para activar '.$gateway->label().' falta: '.implode(', ', $gateway->missingCredentials()).'.']);
        }

        // Mercado Pago: antes de mostrarlo en la tienda se comprueba que el Access Token funcione en el ambiente elegido.
        $check = $gateway->provider === 'mercadopago' && $gateway->credential('access_token')
            ? $mercadoPago->checkCredentials($gateway->credential('access_token'), $gateway->environment)
            : null;
        if ($check && ! $check['ok'] && $check['definitive'] && $gateway->enabled) {
            return back()->withInput()->withErrors(['credentials' => $check['message']]);
        }
        $gateway->save();

        $saved = back()->with('status', $gateway->label().' guardado.'.($check && $check['ok'] ? ' '.$check['message'] : ''));

        return $check && ! $check['ok'] ? $saved->withErrors(['credentials' => $check['message']]) : $saved;
    }

    /** Prueba la conexión con la pasarela usando las credenciales guardadas (sin cambiarlas). */
    public function test(PaymentGateway $gateway, MercadoPagoGateway $mercadoPago)
    {
        abort_unless($gateway->provider === 'mercadopago', 404);

        $token = $gateway->credential('access_token');
        if (! $token) {
            return back()->withErrors(['credentials' => 'Guarda primero el Access Token de Mercado Pago.']);
        }

        $check = $mercadoPago->checkCredentials($token, $gateway->environment);

        return $check['ok'] ? back()->with('status', $check['message']) : back()->withErrors(['credentials' => $check['message']]);
    }

    public function clear(Request $request, PaymentGateway $gateway)
    {
        $gateway->update(['enabled' => false, 'credentials' => []]);

        return back()->with('status', 'Credenciales eliminadas y pasarela desactivada.');
    }
}
