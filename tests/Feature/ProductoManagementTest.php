<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Models\User;
use App\Http\Middleware\AuthenticateJwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_a_brand_to_all_product_variants(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        $product = Producto::query()->create([
            'name' => 'POLO CLASICO',
            'default_code' => 'OVER-001-S',
            'stock' => 4,
            'price' => 35,
            'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            'zazu_company_id' => 1,
        ]);

        $variant = Producto::query()->create([
            'name' => 'POLO CLASICO',
            'default_code' => 'OVER-001-M',
            'stock' => 3,
            'price' => 35,
            'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            'zazu_company_id' => 1,
        ]);

        $this->patch(route('admin.productos.brand', $product), [
            'marca' => 'Overshark',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('productos', ['id' => $product->id, 'marca' => 'Overshark']);
        $this->assertDatabaseHas('productos', ['id' => $variant->id, 'marca' => 'Overshark']);
    }

    public function test_product_catalog_filters_by_name_sku_company_and_brand(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        Producto::query()->create([
            'name' => 'PANTALON CATANIA',
            'default_code' => 'BRAVOS-001',
            'stock' => 5,
            'price' => 45,
            'empresa_nombre' => 'BRAVOS',
            'marca' => 'Bravos',
            'zazu_company_id' => 2,
        ]);

        Producto::query()->create([
            'name' => 'POLO CLASICO',
            'default_code' => 'GIRLS-001',
            'stock' => 5,
            'price' => 35,
            'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            'marca' => 'Overshark Girls',
            'zazu_company_id' => 1,
        ]);

        $this->get(route('admin.productos.index', [
            'search' => 'BRAVOS-001',
            'empresa' => 'BRAVOS',
            'marca' => 'Bravos',
        ]))
            ->assertOk()
            ->assertSee('PANTALON CATANIA')
            ->assertDontSee('POLO CLASICO');
    }

    public function test_public_brand_menu_filters_available_products(): void
    {
        Producto::query()->create([
            'name' => 'POLO OVERSHARK',
            'default_code' => 'OVER-001',
            'stock' => 5,
            'price' => 35,
            'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            'marca' => 'Overshark',
            'zazu_company_id' => 1,
        ]);

        Producto::query()->create([
            'name' => 'POLO BRAVOS',
            'default_code' => 'BRAVOS-001',
            'stock' => 5,
            'price' => 35,
            'empresa_nombre' => 'BRAVOS',
            'marca' => 'Bravos',
            'zazu_company_id' => 2,
        ]);

        $this->get(route('web.products.search', ['marca' => 'Overshark']))
            ->assertOk()
            ->assertSee('Overshark')
            ->assertSee('POLO OVERSHARK')
            ->assertDontSee('POLO BRAVOS');
    }
}
