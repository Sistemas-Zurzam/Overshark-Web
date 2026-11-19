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

    /**
     * El campo «Token» de Zazu admite dos credenciales, separadas por coma:
     *  - el token del servidor de Zazu (CRM_WEBHOOK_SECRET): lee /api/external/inventario de todas las empresas y las sedes;
     *  - API keys de Zazu (zazu_live_… / zazu_test_…, Zazu → API Keys): cada una lee /api/v1/existencias de SU empresa.
     * Con API keys el inventario sale de ellas; las sedes Shalom solo se pueden traer con el token del servidor.
     *
     * @return array{secret: ?string, keys: list<string>}
     */
    public static function zazuCredentials(?string $token = null): array
    {
        $parts = preg_split('/[\s,;]+/', trim((string) ($token ?? config('services.zazu.token'))), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $keys = array_values(array_unique(array_filter($parts, fn (string $part): bool => self::isZazuApiKey($part))));
        $secret = collect($parts)->first(fn (string $part): bool => ! self::isZazuApiKey($part));

        return ['secret' => $secret, 'keys' => $keys];
    }

    public static function isZazuApiKey(string $value): bool
    {
        return str_starts_with($value, 'zazu_live_') || str_starts_with($value, 'zazu_test_');
    }

    /** Para mensajes: la clave sin su parte secreta (zazu_live_ITn0…). */
    public static function maskZazuApiKey(string $key): string
    {
        return mb_substr($key, 0, 14).'…';
    }

    /** Respuesta de Zazu que no es JSON: la URL apunta a la página web (p. ej. dev.zazu.com.pe) y no a la API. */
    public const ZAZU_NOT_API = 'La URL de Zazu respondió una página web, no la API. Usa https://zazu.com.pe (producción) o https://api-dev.zazu.com.pe (pruebas).';

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
        ['secret' => $secret, 'keys' => $keys] = self::zazuCredentials();
        if ($base === '' || ($secret === null && $keys === [])) {
            return ['ok' => false, 'message' => 'Falta la URL o el token de Zazu.'];
        }

        return $keys !== [] ? self::testZazuApiKeys($base, $keys, $secret) : self::testZazuSecret($base, $secret);
    }

    /** Token del servidor de Zazu: se prueba con la lista de sedes, que es liviana. */
    private static function testZazuSecret(string $base, string $secret): array
    {
        try {
            $response = Http::baseUrl($base)->withToken($secret)->acceptJson()->connectTimeout(10)->timeout(20)
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
        // La página web de Zazu responde 200 a cualquier ruta: sin JSON no es la API (antes salía «Conectado: 0 sedes»).
        if (! is_array($response->json())) {
            return ['ok' => false, 'message' => self::ZAZU_NOT_API];
        }

        return ['ok' => true, 'message' => 'Conectado a Zazu: '.count((array) $response->json('data', [])).' sedes disponibles.'];
    }

    /** API keys de Zazu: /api/v1/ping dice de qué empresa es cada una; se avisa si esa empresa no la vende la tienda. */
    private static function testZazuApiKeys(string $base, array $keys, ?string $secret): array
    {
        $companies = [];
        foreach ($keys as $key) {
            try {
                $response = Http::baseUrl($base)->withHeaders(['X-Api-Key' => $key])->acceptJson()->connectTimeout(10)->timeout(20)
                    ->get('/api/v1/ping');
            } catch (\Throwable $e) {
                return ['ok' => false, 'message' => 'No se pudo conectar con Zazu ('.$base.'): '.$e->getMessage()];
            }

            if (in_array($response->status(), [401, 403], true)) {
                return ['ok' => false, 'message' => 'Zazu rechazó la API key '.self::maskZazuApiKey($key).': '.($response->json('message') ?? 'HTTP '.$response->status()).' ¿Es de este mismo Zazu (producción o pruebas)?'];
            }
            if (! $response->successful()) {
                return ['ok' => false, 'message' => 'Zazu respondió HTTP '.$response->status().' a la API key '.self::maskZazuApiKey($key).'.'];
            }
            if (! is_array($response->json())) {
                return ['ok' => false, 'message' => self::ZAZU_NOT_API];
            }
            $companies[] = trim((string) $response->json('empresa.nombre'));
        }

        $sold = array_filter($companies, fn (string $name): bool => self::storeSellsCompany($name));
        $notSold = array_values(array_diff($companies, $sold));
        if ($sold === []) {
            return ['ok' => false, 'message' => 'Las API keys son de '.implode(', ', $companies).', que no está en «Empresas que vende la tienda»: no se traería ningún producto. Crea en Zazu una API key de cada empresa que vende la tienda.'];
        }

        return ['ok' => true, 'message' => 'Conectado a Zazu con '.count($keys).' API '.(count($keys) === 1 ? 'key' : 'keys').': '.implode(', ', $sold).'.'
            .($notSold !== [] ? ' '.implode(', ', $notSold).' no está en «Empresas que vende la tienda»: sus productos no se traerán.' : '')
            .($secret === null ? ' Las agencias Shalom solo se actualizan con el token del servidor de Zazu; mientras tanto se conservan las actuales.' : '')];
    }

    /** Misma regla que la sincronización: parte del nombre, sin importar mayúsculas; sin lista configurada, todas. */
    public static function storeSellsCompany(string $name): bool
    {
        $allowed = (array) config('services.zazu.companies', []);

        return $allowed === [] || $name === ''
            || collect($allowed)->contains(fn (string $part): bool => str_contains(mb_strtoupper($name), mb_strtoupper($part)));
    }
}
