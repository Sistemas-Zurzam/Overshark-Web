<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductStoreCompaniesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_list_hides_products_of_companies_the_store_does_not_sell(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        $make = fn (string $name, string $company, int $companyId) => Producto::query()->create([
            'name' => $name, 'default_code' => $name, 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 5, 'price' => 20,
            'zazu_company_id' => $companyId, 'empresa_nombre' => $company,
        ]);
        $make('POLO CLASICO', 'OVERSHARK PERU S.A.C', 1);
        $make('AIR PODS PRO MAX', 'BOX PRIME', 2);

        $this->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee('POLO CLASICO')
            ->assertDontSee('AIR PODS PRO MAX')
            ->assertDontSee('BOX PRIME');
    }
}
