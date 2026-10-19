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
            ->assertSee('Compras en vivo')
            ->assertSee('Asesoría personalizada')
            ->assertSee('Compra manual')
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
            ->assertSee('libre como la marea')
            ->assertSee('Overshark Girls')
            ->assertDontSee('dominar');
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
            ->assertSee('S/109.00')
            ->assertSee(str_replace('/', '\/', route('web.combos.cart.store', $combo)), false)
            ->assertSee('Clasico', false)
            ->assertDontSee('data-ob-pick="'.($combo->id + 1).'"', false); // el combo que no es de outfit no aparece
    }
}
