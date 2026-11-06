<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZazuAgencySyncService
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.zazu.base_url'), '/');
        $token = trim((string) config('services.zazu.token'));

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Faltan ZAZU_BASE_URL o ZAZU_INVENTORY_TOKEN en el archivo .env.');
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
