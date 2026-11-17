<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::query()->orderByRaw("FIELD(provider, 'culqi', 'izipay', 'mercadopago')")->get();

        return view('admin.pasarelas-pago.index', compact('gateways'));
    }

    public function update(Request $request, PaymentGateway $gateway)
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
            return back()->withInput()->withErrors(['credentials' => 'Completa las credenciales requeridas antes de activar esta pasarela.']);
        }
        $gateway->save();

        return back()->with('status', $gateway->label().' guardado.');
    }

    public function clear(Request $request, PaymentGateway $gateway)
    {
        $gateway->update(['enabled' => false, 'credentials' => []]);

        return back()->with('status', 'Credenciales eliminadas y pasarela desactivada.');
    }
}
