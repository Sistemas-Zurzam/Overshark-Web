<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_brand_with_its_palette(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class);
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.brands.store'), [
            'name' => 'Nueva Marca',
            'slug' => 'nueva-marca',
            'primary_color' => '#123456',
            'secondary_color' => '#111111',
            'accent_color' => '#E8F4FF',
            'background_color' => '#F1F2F4',
            'text_color' => '#111111',
            'sort_order' => 40,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('brands', [
            'name' => 'Nueva Marca',
            'slug' => 'nueva-marca',
            'primary_color' => '#123456',
        ]);
    }

    public function test_brand_page_only_shows_products_assigned_to_that_brand(): void
    {
        Producto::query()->create([
            'name' => 'POLO BRAVOS',
            'default_code' => 'BRAVOS-001',
            'stock' => 5,
            'price' => 45,
            'empresa_nombre' => 'BRAVOS',
            'marca' => 'BRAVOS',
            'zazu_company_id' => 2,
        ]);

        Producto::query()->create([
            'name' => 'POLO OVERSHARK',
            'default_code' => 'OVER-001',
            'stock' => 5,
            'price' => 35,
            'empresa_nombre' => 'OVERSHARK PERU S.A.C',
            'marca' => 'OVERSHARK',
            'zazu_company_id' => 1,
        ]);

        $this->get(route('web.brands.show', Brand::query()->where('slug', 'bravos')->firstOrFail()))
            ->assertOk()
            ->assertSee('POLO BRAVOS')
            ->assertDontSee('POLO OVERSHARK');
    }

    public function test_brand_page_only_shows_combos_assigned_to_that_brand(): void
    {
        Combo::query()->create([
            'name' => 'Combo Bravos',
            'brand' => 'BRAVOS',
            'price' => 99,
            'imagen' => 'images/default-hero-banner.png',
            'status' => true,
        ]);

        Combo::query()->create([
            'name' => 'Combo Girls',
            'brand' => 'OVERSHARK GIRLS',
            'price' => 89,
            'imagen' => 'images/default-hero-banner.png',
            'status' => true,
        ]);

        $this->get(route('web.brands.show', Brand::query()->where('slug', 'bravos')->firstOrFail()))
            ->assertOk()
            ->assertSee('Combo Bravos')
            ->assertDontSee('Combo Girls');
    }
}
