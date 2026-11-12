<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Services\ZazuInventorySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZazuInventorySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_only_keeps_the_store_companies(): void
    {
        config([
            'services.zazu.base_url' => 'https://zazu.test',
            'services.zazu.token' => 'token',
            'services.zazu.companies' => ['OVERSHARK', 'BRAVOS'],
        ]);

        // Un producto de Box Prime que quedó de una sincronización anterior.
        Producto::query()->create([
            'name' => 'AIRPODS PRO 2', 'default_code' => 'BOXP_REF0001', 'stock' => 5, 'price' => 99,
            'zazu_source_key' => 'producto:900', 'zazu_product_id' => 900, 'empresa_nombre' => 'BOX PRIME',
        ]);

        $item = fn (int $id, string $name, string $company) => [
            'zazu_product_id' => $id, 'zazu_variant_id' => $id, 'sku' => "SKU-{$id}", 'nombre' => $name,
            'talla' => 'M', 'color' => 'NEGRO', 'disponible_venta' => 4, 'precio_venta' => 50,
            'empresa' => ['id' => $id, 'nombre' => $company],
        ];

        Http::fake(['zazu.test/*' => Http::response([
            'data' => [
                $item(1, 'CLASICO', 'OVERSHARK PERU S.A.C'),
                $item(2, 'POLO BRAVOS', 'BRAVOS'),
                $item(3, 'CASE LA', 'BOX PRIME'),
            ],
            'last_page' => 1,
        ])]);

        $result = app(ZazuInventorySyncService::class)->sync();

        $this->assertSame(2, $result['synced']);
        $this->assertDatabaseHas('productos', ['name' => 'CLASICO', 'stock' => 4]);
        $this->assertDatabaseHas('productos', ['name' => 'POLO BRAVOS', 'stock' => 4]);
        $this->assertDatabaseMissing('productos', ['name' => 'CASE LA']);
        // Lo de otras empresas ya guardado (BOX PRIME) se borra, no queda con stock 0.
        $this->assertDatabaseMissing('productos', ['name' => 'AIRPODS PRO 2']);
        $this->assertSame(1, $result['foreign_removed']);
    }

    public function test_variant_without_its_own_price_uses_the_product_price(): void
    {
        config(['services.zazu.base_url' => 'https://zazu.test', 'services.zazu.token' => 'token', 'services.zazu.companies' => ['OVERSHARK']]);

        Http::fake(['zazu.test/*' => Http::response([
            'data' => [[
                'zazu_product_id' => 7, 'zazu_variant_id' => 70, 'sku' => 'BT-NEGRO-S', 'nombre' => 'BABY TY',
                'talla' => 'S', 'color' => 'NEGRO', 'disponible_venta' => 3, 'precio_venta' => null,
                'producto' => ['id' => 7, 'nombre' => 'BABY TY', 'precio_venta' => '17.00'],
                'empresa' => ['id' => 1, 'nombre' => 'OVERSHARK PERU S.A.C'],
            ]],
            'last_page' => 1,
        ])]);

        app(ZazuInventorySyncService::class)->sync();

        $this->assertDatabaseHas('productos', ['name' => 'BABY TY', 'color' => 'NEGRO', 'price' => 17, 'stock' => 3]);
    }
}
