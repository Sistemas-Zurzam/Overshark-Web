<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Services\ZazuAgencySyncService;
use App\Services\ZazuInventorySyncService;
use App\Support\Integrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Inventario de Zazu con API keys (una por empresa, Zazu → API Keys) en vez del token del servidor, y las protecciones
 * para que una URL o credencial equivocada nunca deje la tienda agotada.
 */
class ZazuApiKeysTest extends TestCase
{
    use RefreshDatabase;

    private const COMPANIES = [
        'zazu_live_overshark' => ['id' => 1, 'nombre' => 'OVERSHARK PERU S.A.C'],
        'zazu_live_bravos' => ['id' => 2, 'nombre' => 'BRAVOS'],
        'zazu_live_zurzam' => ['id' => 3, 'nombre' => 'OUTSOURCING ZURZAM SAC'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.zazu.base_url' => 'https://zazu.test', 'services.zazu.companies' => ['OVERSHARK', 'BRAVOS']]);
    }

    /** Zazu falso: /api/v1/ping y /api/v1/existencias según la API key; Overshark viene en dos páginas. */
    private function fakeZazu(array $stock): void
    {
        Http::fake(function (Request $request) use ($stock) {
            $key = $request->header('X-Api-Key')[0] ?? '';
            $company = self::COMPANIES[$key] ?? null;
            if ($company === null) {
                return Http::response(['message' => 'API key inválida o revocada.'], 401);
            }
            if (str_contains($request->url(), '/api/v1/ping')) {
                return Http::response(['ok' => true, 'empresa' => $company + ['ruc' => '20000000000'], 'entorno' => 'produccion']);
            }

            $pages = array_chunk($stock[$key] ?? [], 1);
            $page = (int) ($request->data()['page'] ?? 1);

            return Http::response(['data' => $pages[$page - 1] ?? [], 'meta' => ['last_page' => max(1, count($pages)), 'current_page' => $page]]);
        });
    }

    private function row(int $variant, string $name, float $price, float $available, string $color = 'NEGRO', string $size = 'M'): array
    {
        return [
            'sku' => "SKU-{$variant}", 'producto_id' => $variant * 10, 'variante_id' => $variant, 'nombre' => $name,
            'categoria' => 'POLOS', 'talla' => $size, 'color' => $color, 'precio_venta' => $price,
            'imagen_url' => 'https://zazu.test/storage/p.jpg', 'activo' => true, 'stock_disponible' => $available,
        ];
    }

    private function product(int $variant, string $company, int $stock, float $price = 40): Producto
    {
        return Producto::query()->create([
            'name' => "PRODUCTO {$variant}", 'default_code' => "SKU-{$variant}", 'stock' => $stock, 'price' => $price,
            'zazu_source_key' => "variante:{$variant}", 'zazu_variant_id' => $variant, 'empresa_nombre' => $company,
        ]);
    }

    public function test_each_api_key_brings_the_stock_of_its_company_variant_by_variant(): void
    {
        config(['services.zazu.token' => 'zazu_live_overshark, zazu_live_bravos']);
        $this->product(1, 'OVERSHARK PERU S.A.C', 5, 35);
        $gone = $this->product(9, 'OVERSHARK PERU S.A.C', 7);
        $this->fakeZazu([
            // El precio 0 de Zazu no pisa el de la tienda (S/ 35).
            'zazu_live_overshark' => [$this->row(1, 'WAFFLE CAMISERO', 0, 12), $this->row(2, 'WAFFLE CAMISERO', 35, 3, 'AZUL')],
            'zazu_live_bravos' => [$this->row(3, 'POLERA NERU', 40, 8.6)],
        ]);

        $result = app(ZazuInventorySyncService::class)->sync();

        $this->assertSame(3, $result['synced']);
        $this->assertSame(['OVERSHARK PERU S.A.C', 'BRAVOS'], $result['companies']);
        $this->assertDatabaseHas('productos', ['zazu_variant_id' => 1, 'name' => 'WAFFLE CAMISERO', 'stock' => 12, 'price' => 35, 'empresa_nombre' => 'OVERSHARK PERU S.A.C']);
        $this->assertDatabaseHas('productos', ['zazu_variant_id' => 2, 'color' => 'AZUL', 'stock' => 3]);
        $this->assertDatabaseHas('productos', ['zazu_variant_id' => 3, 'name' => 'POLERA NERU', 'stock' => 9, 'empresa_nombre' => 'BRAVOS']);
        $this->assertSame(0, $gone->fresh()->stock); // Zazu ya no lo envía
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/v1/existencias') && ($request->data()['page'] ?? null) == 2);
    }

    public function test_without_the_key_of_a_company_its_products_keep_their_stock(): void
    {
        config(['services.zazu.token' => 'zazu_live_overshark']);
        $bravos = $this->product(3, 'BRAVOS', 6);
        $this->fakeZazu(['zazu_live_overshark' => [$this->row(1, 'CLASICO', 17, 4)]]);

        $result = app(ZazuInventorySyncService::class)->sync();

        $this->assertSame(0, $result['stale_zeroed']);
        $this->assertSame(6, $bravos->fresh()->stock);
    }

    public function test_a_key_of_a_company_the_store_does_not_sell_changes_nothing(): void
    {
        config(['services.zazu.token' => 'zazu_live_zurzam']);
        $product = $this->product(1, 'OVERSHARK PERU S.A.C', 5);
        $this->fakeZazu(['zazu_live_zurzam' => [$this->row(50, 'SERVICIO', 10, 1)]]);

        try {
            app(ZazuInventorySyncService::class)->sync();
            $this->fail('Debió rechazar la sincronización.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('OUTSOURCING ZURZAM SAC', $e->getMessage());
        }

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseMissing('productos', ['name' => 'SERVICIO']);
    }

    public function test_a_web_page_or_an_empty_inventory_never_leaves_the_store_without_stock(): void
    {
        config(['services.zazu.token' => 'secreto-del-servidor']);
        $product = $this->product(1, 'OVERSHARK PERU S.A.C', 5);

        // dev.zazu.com.pe responde la página web (200, HTML) a cualquier ruta de /api.
        Http::fake(['zazu.test/*' => Http::sequence()
            ->push('<!doctype html><html><body>Zazu</body></html>', 200, ['Content-Type' => 'text/html'])
            ->push(['data' => [], 'last_page' => 1])]);

        foreach ([Integrations::ZAZU_NOT_API, 'Zazu no devolvió productos'] as $expected) {
            try {
                app(ZazuInventorySyncService::class)->sync();
                $this->fail('Debió rechazar la sincronización.');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString($expected, $e->getMessage());
            }
            $this->assertSame(5, $product->fresh()->stock);
        }
    }

    public function test_the_connection_test_names_the_companies_of_the_keys(): void
    {
        config(['services.zazu.token' => 'zazu_live_overshark,zazu_live_zurzam']);
        $this->fakeZazu([]);
        $result = Integrations::testZazu();
        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('OVERSHARK PERU S.A.C', $result['message']);
        $this->assertStringContainsString('OUTSOURCING ZURZAM SAC no está en «Empresas que vende la tienda»', $result['message']);
        $this->assertStringContainsString('agencias Shalom', $result['message']);

        config(['services.zazu.token' => 'zazu_live_zurzam']);
        $this->assertFalse(Integrations::testZazu()['ok']);

        config(['services.zazu.token' => 'zazu_live_revocada']);
        $this->assertStringContainsString('Zazu rechazó la API key zazu_live_revo…', Integrations::testZazu()['message']);
    }

    public function test_the_connection_test_no_longer_accepts_the_web_page_as_connected(): void
    {
        config(['services.zazu.token' => 'secreto-del-servidor']);
        Http::fake(['zazu.test/*' => Http::response('<!doctype html><html></html>', 200, ['Content-Type' => 'text/html'])]);
        $this->assertSame(['ok' => false, 'message' => Integrations::ZAZU_NOT_API], Integrations::testZazu());
    }

    public function test_the_token_field_mixes_the_server_secret_and_api_keys(): void
    {
        $this->assertSame(
            ['secret' => 'secreto', 'keys' => ['zazu_live_a', 'zazu_test_b']],
            Integrations::zazuCredentials(" zazu_live_a,\nsecreto ; zazu_test_b, zazu_live_a"),
        );

        // Solo con API keys las sedes no se pueden traer: se conservan las actuales.
        config(['services.zazu.token' => 'zazu_live_overshark']);
        $this->expectExceptionMessage('Las sedes Shalom solo se traen con el token del servidor de Zazu');
        app(ZazuAgencySyncService::class)->sync();
    }
}
