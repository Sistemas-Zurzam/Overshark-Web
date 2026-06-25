<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PeruUbigeoSeeder extends Seeder
{
    private string $baseUrl = 'https://jmc-software-x.github.io/public-ubigeo-pe/data';

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('distritos')->truncate();
        DB::table('provincias')->truncate();
        DB::table('departamentos')->truncate();
        Schema::enableForeignKeyConstraints();

        try {
            DB::beginTransaction();

            $now = now();

            $hierarchy = Http::acceptJson()
                ->timeout(60)
                ->get("{$this->baseUrl}/hierarchy.json")
                ->throw()
                ->json();

            foreach ($hierarchy as $departmentData) {
                $departmentId = DB::table('departamentos')->insertGetId([
                    'name' => trim((string) $departmentData['name']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $provincesResponse = Http::acceptJson()
                    ->timeout(60)
                    ->get("{$this->baseUrl}/departments/{$departmentData['id']}.json")
                    ->throw()
                    ->json();

                $provinceIdMap = [];

                foreach ($provincesResponse['provinces'] as $provinceData) {
                    $provinceId = DB::table('provincias')->insertGetId([
                        'departamento_id' => $departmentId,
                        'name' => trim((string) $provinceData['name']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $provinceIdMap[$provinceData['id']] = $provinceId;
                }

                foreach ($provincesResponse['provinces'] as $provinceData) {
                    $districtsResponse = Http::acceptJson()
                        ->timeout(60)
                        ->get("{$this->baseUrl}/provinces/{$provinceData['id']}.json")
                        ->throw()
                        ->json();

                    $rows = [];

                    foreach ($districtsResponse['districts'] as $districtData) {
                        $rows[] = [
                            'provincia_id' => $provinceIdMap[$districtData['provinceId']],
                            'name' => trim((string) $districtData['name']),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table('distritos')->insert($rows);
                    }
                }
            }

            DB::commit();
            $this->command?->info('Ubigeo importado correctamente.');
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        }
    }
}
