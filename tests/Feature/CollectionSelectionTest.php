<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_shows_the_collection_selector(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('¿Qué quieres explorar?')
            ->assertSee(route('web.home', ['linea' => 'hombre']), false)
            ->assertSee(route('web.home', ['linea' => 'mujer']), false);
    }

    public function test_choosing_a_line_remembers_it_and_shows_the_store(): void
    {
        $this->get(route('web.home', ['linea' => 'hombre']))
            ->assertOk()
            ->assertSessionHas('storefront.line', 'hombre')
            ->assertSee('Nacido del océano')
            ->assertSee('Hecho para dominar')
            ->assertDontSee('Compras en vivo')
            ->assertDontSee('Asesoría personalizada')
            ->assertDontSee('Compra manual')
            ->assertDontSeeText('Arma tu outfit con IA');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('¿Qué quieres explorar?')
            ->assertDontSeeText('Arma tu outfit con IA');
    }

    public function test_women_products_page_keeps_the_girls_design(): void
    {
        $this->get(route('web.girls.products'))
            ->assertOk()
            ->assertSee('Overshark Girls · Productos')
            ->assertSee('images/girls/logo-girls.png', false)
            ->assertSessionHas('storefront.line', 'mujer');
    }

    public function test_women_line_uses_its_own_hero_copy(): void
    {
        $this->withSession(['storefront.line' => 'mujer'])
            ->get('/')
            ->assertOk()
            ->assertSee('Nacida del arrecife')
            ->assertSee('Hecha para brillar')
            ->assertSee('os-hh--girls', false)
            ->assertSeeInOrder(['9 prendas × S/ 99', 'Baby tees', 'Calidad que se nota', '¡Arma tu outfit!', 'Compra con confianza', 'Otras tiendas'], false)
            ->assertDontSee('dominar');
    }

    public function test_women_home_lists_only_girls_best_sellers(): void
    {
        foreach (['BABY TY BASICA' => 'BABY-1', 'POLO WAFFLE' => 'WAF-1'] as $name => $code) {
            \App\Models\Admin\Producto::query()->create([
                'name' => $name, 'default_code' => $code, 'talla' => 'M', 'color' => 'ROSA', 'stock' => 5, 'price' => 39.9, 'zazu_company_id' => 1,
            ]);
        }

        $this->withSession(['storefront.line' => 'mujer'])
            ->get('/')
            ->assertOk()
            ->assertSee('Los más vendidos')
            ->assertSee('Baby Ty Basica · Rosa')
            ->assertDontSee('Polo Waffle');
    }

    public function test_unknown_line_still_shows_the_selector(): void
    {
        $this->get('/?linea=ninos')
            ->assertOk()
            ->assertSee('¿Qué quieres explorar?')
            ->assertSessionMissing('storefront.line');
    }

    public function test_selector_stays_available_after_choosing(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])
            ->get(route('web.explore'))
            ->assertOk()
            ->assertSee('¿Qué quieres explorar?');
    }

    public function test_outfit_builder_lists_outfit_combos_with_their_garments(): void
    {
        $polo = \App\Models\Admin\Producto::query()->create(['name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 4, 'price' => 52.9, 'zazu_company_id' => 1]);
        $pant = \App\Models\Admin\Producto::query()->create(['name' => 'PANTALON BRATZ', 'default_code' => 'PB-B-L', 'color' => 'BEIGE', 'talla' => 'L', 'stock' => 3, 'price' => 85.9, 'zazu_company_id' => 1]);
        $combo = \App\Models\Admin\Combo::query()->create([
            'name' => 'Combo Básico', 'brand' => 'Overshark', 'modality' => 'Outfit', 'price' => 109,
            'imagen' => 'combos/outfit-basico.jpg', 'status' => true, 'selection_mode' => 'fixed',
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 1], ['zazu' => 'PANTALON BRATZ', 'cantidad' => 1]],
        ]);
        $combo->productos()->sync([$polo->id => ['cantidad' => 1], $pant->id => ['cantidad' => 1]]);
        \App\Models\Admin\Combo::query()->create([
            'name' => 'Promo Live', 'brand' => 'Overshark', 'modality' => 'Live', 'price' => 99,
            'imagen' => 'combos/x.jpg', 'status' => true, 'selection_mode' => 'fixed', 'items' => [],
        ]);

        $this->get(route('web.outfit'))
            ->assertOk()
            ->assertSee('Combo Básico')
            ->assertSee('S/ 109.00')
            ->assertSee(str_replace('/', '\/', route('web.combos.cart.store', $combo)), false)
            ->assertSee('Clasico', false)
            ->assertDontSee('data-ob-pick="'.($combo->id + 1).'"', false); // el combo que no es de outfit no aparece
    }

    public function test_outfit_builder_shows_only_the_looks_of_the_chosen_line(): void
    {
        $tee = \App\Models\Admin\Producto::query()->create(['name' => 'BABY TY BASICA', 'default_code' => 'BT-L-S', 'color' => 'LILA', 'talla' => 'S', 'stock' => 4, 'price' => 19.9, 'zazu_company_id' => 1]);
        $girlsLook = \App\Models\Admin\Combo::query()->create([
            'name' => 'Soft Pink', 'brand' => 'Overshark Girls', 'modality' => 'Outfit', 'price' => 59.9,
            'imagen' => 'combos/soft-pink.jpg', 'status' => true, 'selection_mode' => 'fixed',
            'items' => [['zazu' => 'BABY TY BASICA', 'cantidad' => 1]],
        ]);
        $girlsLook->productos()->sync([$tee->id => ['cantidad' => 1]]);
        $polo = \App\Models\Admin\Producto::query()->create(['name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 4, 'price' => 52.9, 'zazu_company_id' => 1]);
        $menLook = \App\Models\Admin\Combo::query()->create([
            'name' => 'Casual Chino', 'brand' => 'Overshark', 'modality' => 'Outfit', 'price' => 79.9,
            'imagen' => 'combos/casual.jpg', 'status' => true, 'selection_mode' => 'fixed',
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 1]],
        ]);
        $menLook->productos()->sync([$polo->id => ['cantidad' => 1]]);

        $this->withSession(['storefront.line' => 'mujer'])->get(route('web.outfit'))
            ->assertOk()
            ->assertSee('os-hh--girls', false)
            ->assertSee('collage-girls-1.webp', false)
            ->assertSee('Soft Pink')
            ->assertDontSee('Casual Chino');

        $this->withSession(['storefront.line' => 'hombre'])->get(route('web.outfit'))
            ->assertOk()
            ->assertDontSee('os-hh--girls', false)
            ->assertSee('Casual Chino')
            ->assertDontSee('Soft Pink');
    }
}
