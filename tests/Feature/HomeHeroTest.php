<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_men_hero_shows_the_figma_texts_by_default(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])->get('/')
            ->assertOk()
            ->assertSee('Colección hombre · 2027')
            ->assertSee('Nacido del')
            ->assertSee('Océano.')
            ->assertSee('Ver colección')
            ->assertSee('Arma tu outfit')
            ->assertSee('Más vendido')
            ->assertSee('Camisas')
            ->assertSee('Joggers');
    }

    public function test_the_buttons_and_footer_categories_come_from_the_admin(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create())
            ->put(route('admin.contenido.update'), ['c' => ['hero' => ['hombre' => [
                'cta1_text' => 'Comprar ahora',
                'cta1_url' => '/buscar?orden=nuevos',
                'cta2_text' => 'Crea tu look',
                'categories' => 'Polos, Casacas',
            ]]]])->assertSessionHasNoErrors();

        $this->withSession(['storefront.line' => 'hombre'])->get('/')
            ->assertOk()
            ->assertSee('Comprar ahora')
            ->assertSee('/buscar?orden=nuevos', false)
            ->assertSee('Crea tu look')
            ->assertSee('categoria=casacas', false);
    }
}
