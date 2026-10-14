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
            ->assertSeeText('Arma tu outfit con IA');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('¿Qué quieres explorar?')
            ->assertSeeText('Arma tu outfit con IA');
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

    public function test_outfit_card_links_to_the_outfit_builder(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSee('href="'.route('web.outfit.show').'"', false)
            ->assertSee('Crear mi modelo');
    }
}
