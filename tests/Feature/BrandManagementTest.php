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
            ->assertSee('Polo Bravos')
            ->assertDontSee('Polo Overshark');
    }

    public function test_bravos_page_uses_its_own_design_and_the_bravos_company_products(): void
    {
        // Sin marca asignada en el admin, cuenta la empresa de Zazu.
        Producto::query()->create([
            'name' => 'POLERA NERU', 'default_code' => 'NERU-001', 'color' => 'NEGRO', 'stock' => 5, 'price' => 64.9,
            'empresa_nombre' => 'BRAVOS', 'zazu_company_id' => 2,
        ]);
        Producto::query()->create([
            'name' => 'POLERA CLASICA', 'default_code' => 'OVER-002', 'color' => 'NEGRO', 'stock' => 5, 'price' => 40,
            'empresa_nombre' => 'BRAVOS', 'marca' => 'OVERSHARK', 'zazu_company_id' => 2,
        ]);

        $this->get(route('web.brands.show', Brand::query()->where('slug', 'bravos')->firstOrFail()))
            ->assertOk()
            ->assertSee('theme-bravos', false)
            ->assertSeeInOrder(['TUS REGLAS', 'LOS FAVORITOS', 'Polera Neru', 'MÁS ACTITUD', 'BAJA EL RITMO', 'MISMA CALLE', 'SIN COMPLICACIONES', 'SIGUE EXPLORANDO'])
            ->assertSee('Un modelo para tu estilo.')
            ->assertDontSee('Polera Clasica');

        $this->get(route('web.products.search', ['marca' => 'BRAVOS']))
            ->assertOk()
            ->assertSee('TIENDA BRAVOS')
            ->assertSee('1 modelo')
            ->assertSee('Polera Neru')
            ->assertDontSee('Polera Clasica');

        // Su ficha usa el diseño de Bravos; la de un producto de otra marca, el general.
        $neru = Producto::query()->where('name', 'POLERA NERU')->firstOrFail();
        $this->get(route('web.products.show', $neru))
            ->assertOk()
            ->assertSee('theme-bravos', false)
            ->assertSeeInOrder(['POLERA NERU', 'S/ 64.90', 'Agregar al carrito', 'SOBRE ESTA PRENDA']);

        $this->get(route('web.products.show', Producto::query()->where('name', 'POLERA CLASICA')->firstOrFail()))
            ->assertOk()
            ->assertDontSee('theme-bravos', false);
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

        // Bravos tiene portada propia sin combos; la página genérica de marca sí los lista.
        $this->get(route('web.brands.show', Brand::query()->where('slug', 'overshark-girls')->firstOrFail()))
            ->assertOk()
            ->assertSee('Combo Girls')
            ->assertDontSee('Combo Bravos');
    }

    public function test_bravos_outfit_adds_the_whole_look_to_the_cart(): void
    {
        $neru = Producto::query()->create([
            'name' => 'POLERA NERU', 'default_code' => 'NERU-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 3, 'price' => 64.9,
            'empresa_nombre' => 'BRAVOS', 'zazu_company_id' => 2,
        ]);
        $bratz = Producto::query()->create([
            'name' => 'PANTALON BRATZ', 'default_code' => 'BRATZ-L', 'color' => 'CEMENTO', 'talla' => 'L', 'stock' => 2, 'price' => 85.9,
            'empresa_nombre' => 'BRAVOS', 'zazu_company_id' => 2,
        ]);

        $this->get(route('web.bravos.outfit'))
            ->assertOk()
            ->assertSee('ARMA TU OUTFIT.')
            ->assertSee('01 / ELIGE TU TOP')
            ->assertSee('POLERA NERU')
            ->assertSee('PANTALON BRATZ');

        $this->post(route('web.cart.look'), ['items' => [
            ['producto_id' => $neru->id, 'color' => 'NEGRO', 'talla' => 'M'],
            ['producto_id' => $bratz->id, 'color' => 'CEMENTO', 'talla' => 'L'],
        ]])->assertRedirect()->assertSessionHas('cart_open', true);

        $this->assertEqualsCanonicalizing([$neru->id, $bratz->id], collect(session('cart.items'))->pluck('variant_id')->all());

        // Sin stock en esa talla no se agrega nada.
        $this->post(route('web.cart.look'), ['items' => [
            ['producto_id' => $neru->id, 'color' => 'NEGRO', 'talla' => 'XL'],
        ]])->assertSessionHasErrors('items');
    }

    public function test_about_and_contact_pages_switch_to_bravos_when_coming_from_bravos(): void
    {
        $this->get(route('web.nosotros', ['marca' => 'bravos']))
            ->assertOk()
            ->assertSee('os-hh--bravos', false)
            ->assertSee('theme-bravos', false)
            ->assertSee(route('web.contact', ['marca' => 'bravos']), false);

        $this->get(route('web.contact', ['marca' => 'bravos']))
            ->assertOk()
            ->assertSee('os-hh--bravos', false)
            ->assertSee('icon-correo-bravos.svg', false)
            ->assertSee('name="marca" value="bravos"', false);

        $this->get(route('web.contact'))
            ->assertOk()
            ->assertDontSee('os-hh--bravos', false);
    }
}
