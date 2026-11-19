<?php

namespace App\Services;

use App\Support\Integrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZazuAgencySyncService
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.zazu.base_url'), '/');
        ['secret' => $token, 'keys' => $keys] = Integrations::zazuCredentials();

        if ($baseUrl === '' || ($token === null && $keys === [])) {
            throw new RuntimeException('Faltan la URL o el token de Zazu (Admin → Integraciones o ZAZU_BASE_URL / ZAZU_INVENTORY_TOKEN en el .env).');
        }
        // Las API keys de Zazu son por empresa y no dan acceso a las sedes: hace falta el token del servidor de Zazu.
        if ($token === null) {
            throw new RuntimeException('Las sedes Shalom solo se traen con el token del servidor de Zazu (las API keys no sirven para eso); se conserva la lista actual.');
        }

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withToken($token)
            ->connectTimeout(10)
            ->timeout((int) config('services.zazu.timeout', 30))
            ->get((string) config('services.zazu.agencies_path', '/api/external/sedes'))
            ->throw()
            ->json();

        $agencies = $response['data'] ?? null;
        if (! is_array($agencies) || $agencies === []) {
            throw new RuntimeException('Zazu no devolvió sedes activas; se conserva la lista actual.');
        }

        $now = now();
        $rows = collect($agencies)->map(function (array $agency) use ($now): ?array {
            $terId = trim((string) ($agency['ter_id'] ?? ''));
            $name = trim((string) ($agency['nombre'] ?? ''));
            if ($terId === '' || $name === '') {
                return null;
            }

            return [
                'ter_id' => $terId,
                'direccion' => trim((string) ($agency['direccion'] ?? '')) ?: null,
                'zona' => trim((string) ($agency['zona'] ?? '')) ?: null,
                'provincia' => trim((string) ($agency['provincia'] ?? '')) ?: null,
                'departamento' => trim((string) ($agency['departamento'] ?? '')) ?: null,
                'lugar_over' => $name,
                'last_synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->filter()->unique('ter_id')->values();

        if ($rows->isEmpty()) {
            throw new RuntimeException('Zazu no devolvió sedes Shalom válidas; se conserva la lista actual.');
        }

        $ids = $rows->pluck('ter_id')->all();
        DB::transaction(function () use ($rows, $ids): void {
            foreach ($rows->chunk(100) as $chunk) {
                DB::table('courier')->upsert(
                    $chunk->all(),
                    ['ter_id'],
                    ['direccion', 'zona', 'provincia', 'departamento', 'lugar_over', 'last_synced_at', 'updated_at']
                );
            }

            DB::table('courier')->whereNotIn('ter_id', $ids)->delete();
        });

        return ['synced' => $rows->count()];
    }
}
