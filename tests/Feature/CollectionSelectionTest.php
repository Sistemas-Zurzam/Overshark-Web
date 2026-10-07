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
            ->assertSee('Nacido del océano,')
            ->assertSee('dominar')
            ->assertSee('Compras en vivo')
            ->assertSee('Asesoría personalizada')
            ->assertSee('Outfit con IA');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('¿Qué quieres explorar?')
            ->assertSee('Outfit con IA');
    }

    public function test_women_line_uses_its_own_hero_copy(): void
    {
        $this->withSession(['storefront.line' => 'mujer'])
            ->get('/')
            ->assertOk()
            ->assertSee('Nacida del océano,')
            ->assertSee('brillar')
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

    public function test_outfit_card_links_to_its_configured_url(): void
    {
        config(['services.storefront.outfit_ai_url' => 'https://outfit.example.com']);

        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSee('https://outfit.example.com')
            ->assertSee('Crear modelo');
    }
}
