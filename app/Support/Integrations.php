<?php

namespace App\Support;

use App\Models\Admin\IntegrationSetting;
use Illuminate\Support\Facades\Http;

/**
 * Conexiones de salida de Admin → Integraciones. Lo guardado en el panel reemplaza al .env; lo que quede vacío sigue
 * usando el .env (así nada cambia hasta que alguien guarda la integración).
 *
 *  - crm: CRM Livex V2, recibe cada pedido pagado como venta (services.crm, App\Services\Crm\CrmSales).
 *  - zazu: inventario y agencias Shalom (services.zazu, comandos zazu:sync-*).
 *  - pixels: Meta Pixel, Google Analytics 4 y TikTok Pixel en la tienda (partials/tracking-pixels).
 */
class Integrations
{
    /** Contenedor: valores del .env tal como cargaron, para volver a ellos si se vacía un campo en el panel. */
    private const DEFAULTS = 'integrations.env-defaults';

    /** Formato de cada ID de píxel (también evita inyectar código en la página). */
    public const PIXEL_PATTERNS = [
        'meta_pixel_id' => '/^\d{6,20}$/',
        'ga4_id' => '/^G-[A-Z0-9]{4,20}$/',
        'tiktok_pixel_id' => '/^[A-Z0-9]{10,30}$/',
    ];

    /** Copia las conexiones guardadas en el panel a config(); se llama al arrancar y antes de cada trabajo de la cola. */
    public static function apply(): void
    {
        if (! app()->bound(self::DEFAULTS)) {
            app()->instance(self::DEFAULTS, ['crm' => (array) config('services.crm'), 'zazu' => (array) config('services.zazu')]);
        }
        $defaults = app(self::DEFAULTS);
        $rows = IntegrationSetting::query()->whereIn('provider', ['crm', 'zazu'])->get()->keyBy('provider');

        // Sin fila guardada la integración sigue tal cual el .env (no se toca config).
        if ($crm = $rows->get('crm')) {
            config(['services.crm' => array_merge($defaults['crm'], [
                'send_sales' => $crm->enabled,
                'base_url' => $crm->value('base_url', $defaults['crm']['base_url'] ?? null),
                'api_key' => $crm->value('api_key', $defaults['crm']['api_key'] ?? null),
            ])]);
        }

        if ($zazu = $rows->get('zazu')) {
            $companies = array_values(array_filter(array_map('trim', explode(',', mb_strtoupper((string) $zazu->value('companies'))))));
            config(['services.zazu' => array_merge($defaults['zazu'], [
                'base_url' => $zazu->value('base_url', $defaults['zazu']['base_url'] ?? null),
                'token' => $zazu->value('token', $defaults['zazu']['token'] ?? null),
                'companies' => $companies ?: ($defaults['zazu']['companies'] ?? []),
            ])]);
        }
    }

    /** Las sincronizaciones programadas con Zazu corren salvo que se desactiven en el panel. */
    public static function zazuSyncEnabled(): bool
    {
        $zazu = IntegrationSetting::query()->where('provider', 'zazu')->first();

        return $zazu === null || $zazu->enabled;
    }

    /**
     * IDs de los píxeles activos que se cargan en la tienda.
     *
     * @return array{meta: ?string, ga4: ?string, tiktok: ?string}
     */
    public static function pixels(): array
    {
        // Una consulta por solicitud (se guarda en la solicitud, no en el proceso: un cambio del panel aplica enseguida).
        $request = request();
        if ($request->attributes->has('integrations.pixels')) {
            return $request->attributes->get('integrations.pixels');
        }

        try {
            $row = IntegrationSetting::query()->where('provider', 'pixels')->where('enabled', true)->first();
        } catch (\Throwable) {
            $row = null;
        }
        $id = fn (string $key) => ($value = trim((string) $row?->value($key))) !== '' && preg_match(self::PIXEL_PATTERNS[$key], $value) ? $value : null;
        $pixels = ['meta' => $id('meta_pixel_id'), 'ga4' => $id('ga4_id'), 'tiktok' => $id('tiktok_pixel_id')];
        $request->attributes->set('integrations.pixels', $pixels);

        return $pixels;
    }

    /**
     * Prueba la conexión con el CRM: un POST vacío a /api/v1/sales. El CRM valida la clave antes que los datos,
     * así que 422 = clave válida con permiso sales.write (y no se crea ninguna venta).
     *
     * @return array{ok: bool, message: string}
     */
    public static function testCrm(): array
    {
        $base = rtrim((string) config('services.crm.base_url'), '/');
        $key = (string) config('services.crm.api_key');
        if ($base === '' || $key === '') {
            return ['ok' => false, 'message' => 'Falta la URL o la clave API del CRM.'];
        }

        try {
            $response = Http::baseUrl($base)->withToken($key)->acceptJson()->connectTimeout(10)->timeout(15)->post('/api/v1/sales', []);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'No se pudo conectar con el CRM ('.$base.'): '.$e->getMessage()];
        }

        return match (true) {
            $response->status() === 422 => ['ok' => true, 'message' => 'Conectado al CRM: la clave API es válida y puede registrar ventas.'],
            $response->status() === 401 => ['ok' => false, 'message' => 'El CRM rechazó la clave API (inválida o revocada).'],
            $response->status() === 403 => ['ok' => false, 'message' => 'La clave API del CRM no tiene permiso para registrar ventas (sales.write): '.($response->json('message') ?? '')],
            $response->status() === 404 => ['ok' => false, 'message' => 'La URL no parece ser el CRM (no existe /api/v1/sales). Revisa la URL base.'],
            default => ['ok' => false, 'message' => 'El CRM respondió HTTP '.$response->status().'.'],
        };
    }

    /**
     * Prueba la conexión con Zazu pidiendo la lista de sedes (agencias Shalom), que es liviana.
     *
     * @return array{ok: bool, message: string}
     */
    public static function testZazu(): array
    {
        $base = rtrim((string) config('services.zazu.base_url'), '/');
        $token = trim((string) config('services.zazu.token'));
        if ($base === '' || $token === '') {
            return ['ok' => false, 'message' => 'Falta la URL o el token de Zazu.'];
        }

        try {
            $response = Http::baseUrl($base)->withToken($token)->acceptJson()->connectTimeout(10)->timeout(20)
                ->get((string) config('services.zazu.agencies_path', '/api/external/sedes'));
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'No se pudo conectar con Zazu ('.$base.'): '.$e->getMessage()];
        }

        if (in_array($response->status(), [401, 403], true)) {
            return ['ok' => false, 'message' => 'Zazu rechazó el token (HTTP '.$response->status().').'];
        }
        if (! $response->successful()) {
            return ['ok' => false, 'message' => 'Zazu respondió HTTP '.$response->status().'.'];
        }

        return ['ok' => true, 'message' => 'Conectado a Zazu: '.count((array) $response->json('data', [])).' sedes disponibles.'];
    }
}
