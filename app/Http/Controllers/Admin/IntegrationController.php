<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ApiKey;
use App\Models\Admin\IntegrationSetting;
use App\Models\Admin\WebhookEndpoint;
use App\Services\Integrations\Webhooks;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin → Configuración → Integraciones: conexiones de salida (CRM Livex, Zazu, píxeles), claves de la API de Overshark
 * para sistemas externos y webhooks de pedidos.
 */
class IntegrationController extends Controller
{
    /** Campos de cada conexión de salida: [clave, etiqueta, ¿secreto?]. */
    public const FIELDS = [
        'crm' => [['base_url', 'URL del CRM', false], ['api_key', 'Clave API (permiso sales.write)', true]],
        'zazu' => [['base_url', 'URL de Zazu', false], ['token', 'Token de inventario', true], ['companies', 'Empresas que vende la tienda (separadas por coma)', false]],
        'pixels' => [['meta_pixel_id', 'ID del Meta Pixel', false], ['ga4_id', 'ID de Google Analytics 4 (G-…)', false], ['tiktok_pixel_id', 'ID del TikTok Pixel', false]],
    ];

    public function index(): View
    {
        return view('admin.integraciones.index', [
            'connections' => collect(IntegrationSetting::PROVIDERS)->mapWithKeys(fn (string $provider) => [$provider => IntegrationSetting::for($provider)]),
            'fields' => self::FIELDS,
            'apiKeys' => ApiKey::query()->latest('id')->get(),
            'abilities' => ApiKey::ABILITIES,
            'webhooks' => WebhookEndpoint::query()->latest('id')->get(),
            'events' => WebhookEndpoint::EVENTS,
            'apiBase' => url('/api/v1'),
        ]);
    }

    public function updateConnection(Request $request, string $provider): RedirectResponse
    {
        abort_unless(isset(self::FIELDS[$provider]), 404);

        $rules = collect(self::FIELDS[$provider])->mapWithKeys(fn (array $field) => ["settings.{$field[0]}" => ['nullable', 'string', 'max:2000']])->all();
        if ($provider === 'pixels') {
            foreach (Integrations::PIXEL_PATTERNS as $key => $pattern) {
                $rules["settings.$key"] = ['nullable', 'string', 'regex:'.$pattern];
            }
        } else {
            $rules['settings.base_url'] = ['nullable', 'url:http,https', 'max:255'];
        }
        $validated = $request->validate($rules + ['enabled' => ['nullable', 'boolean']], [
            'settings.meta_pixel_id.regex' => 'El ID del Meta Pixel son solo números (p. ej. 1234567890123456).',
            'settings.ga4_id.regex' => 'El ID de Google Analytics 4 empieza con G- (p. ej. G-ABC123XYZ).',
            'settings.tiktok_pixel_id.regex' => 'El ID del TikTok Pixel son letras mayúsculas y números (p. ej. CABC123DEF456GHI).',
            'settings.base_url.url' => 'Escribe la URL completa, con https://.',
        ]);

        $setting = IntegrationSetting::for($provider);
        $settings = $setting->settings ?? [];
        foreach (self::FIELDS[$provider] as [$key, , $secret]) {
            $value = trim((string) data_get($validated, "settings.$key", ''));
            // Un secreto vacío conserva el guardado; los demás campos se pueden vaciar (vuelven al .env).
            if ($value !== '' || ! $secret) {
                $settings[$key] = $value;
            }
        }
        $setting->fill(['settings' => $settings, 'enabled' => (bool) ($validated['enabled'] ?? false)])->save();
        Integrations::apply();

        return back()->with('status', $this->label($provider).' guardado.');
    }

    public function testConnection(string $provider): RedirectResponse
    {
        $result = match ($provider) {
            'crm' => Integrations::testCrm(),
            'zazu' => Integrations::testZazu(),
            default => abort(404),
        };

        return $result['ok'] ? back()->with('status', $result['message']) : back()->withErrors(['integration' => $result['message']]);
    }

    public function storeKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys(ApiKey::ABILITIES))],
        ], ['abilities.required' => 'Elige al menos un permiso para la clave.']);

        [$key, $plain] = ApiKey::issue($validated['name'], $validated['abilities']);

        // El token completo solo se muestra esta vez.
        return back()->with('status', "Clave «{$key->name}» creada. Cópiala ahora: no se volverá a mostrar.")->with('new_api_key', $plain);
    }

    public function revokeKey(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['revoked_at' => now()]);

        return back()->with('status', "Clave «{$apiKey->name}» revocada: ya no puede usar la API.");
    }

    public function storeWebhook(Request $request): RedirectResponse
    {
        $validated = $this->validateWebhook($request);
        $webhook = WebhookEndpoint::query()->create($validated + ['secret' => WebhookEndpoint::newSecret(), 'enabled' => true]);

        return back()->with('status', "Webhook «{$webhook->name}» creado. Usa su secreto para validar la firma de cada aviso.");
    }

    public function updateWebhook(Request $request, WebhookEndpoint $webhook): RedirectResponse
    {
        $webhook->update($this->validateWebhook($request) + ['enabled' => $request->boolean('enabled')]);

        return back()->with('status', "Webhook «{$webhook->name}» guardado.");
    }

    public function destroyWebhook(WebhookEndpoint $webhook): RedirectResponse
    {
        $webhook->delete();

        return back()->with('status', "Webhook «{$webhook->name}» eliminado.");
    }

    /** Envía un aviso de prueba (evento «ping») y muestra lo que respondió el destino. */
    public function testWebhook(WebhookEndpoint $webhook): RedirectResponse
    {
        $result = Webhooks::send($webhook, Webhooks::payload('ping', ['message' => 'Prueba de webhook de Overshark']));

        return $result['ok']
            ? back()->with('status', "Webhook «{$webhook->name}»: {$result['message']}")
            : back()->withErrors(['integration' => "Webhook «{$webhook->name}»: {$result['message']}"]);
    }

    private function validateWebhook(Request $request): array
    {
        // En producción solo https: los avisos llevan datos personales de clientes.
        $schemes = app()->isProduction() ? 'https' : 'http,https';

        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url:'.$schemes, 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(array_keys(WebhookEndpoint::EVENTS))],
        ], [
            'url.url' => 'Escribe la URL completa del destino'.(app()->isProduction() ? ', con https://.' : '.'),
            'events.required' => 'Elige al menos un evento.',
        ]);
    }

    private function label(string $provider): string
    {
        return ['crm' => 'CRM Livex', 'zazu' => 'Zazu', 'pixels' => 'Píxeles de anuncios'][$provider];
    }
}
