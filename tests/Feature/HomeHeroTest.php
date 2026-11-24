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
            ->assertSee('Colección hombre ')
            ->assertSee('Nacido del')
            ->assertSee('Océano.')
            ->assertSee('Ver colección')
            ->assertSee('Arma tu outfit')
            ->assertSee('Polo Clásico Jersey')
            ->assertSee('Camisas')
            ->assertSee('categoria=camisas', false)
            ->assertSee('categoria=polos', false);
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

    public function test_the_men_categories_section_has_polos_camisas_and_jeans(): void
    {
        $this->assertSame('jeans', \App\Support\StorefrontCategories::slugFor('JEANS', null, 'hombre'));
        $this->assertSame('pantalones', \App\Support\StorefrontCategories::slugFor('PANTALON BRATZ', null, 'hombre'));

        $html = $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()
            ->assertSee('categoria=jeans', false)
            ->getContent();

        $section = substr($html, (int) strpos($html, 'Básicos para todos los días'));
        $section = substr($section, 0, (int) strpos($section, 'Por qué elegir Overshark'));
        $this->assertStringContainsString('Jeans', $section);
        $this->assertStringNotContainsString('Poleras', $section);
        $this->assertStringNotContainsString('Joggers', $section);
    }

    public function test_the_men_shop_filter_only_offers_polos_camisas_and_jeans(): void
    {
        $this->assertSame(['polos', 'camisas', 'jeans'], array_keys(\App\Support\StorefrontCategories::shop('hombre')));
        // Las demás siguen existiendo (Bravos, outfit, promociones) y su enlace directo funciona.
        $this->assertNotNull(\App\Support\StorefrontCategories::find('pantalones', 'hombre'));

        $html = $this->withSession(['storefront.line' => 'hombre'])->get(route('web.products.search'))->assertOk()->getContent();
        $this->assertStringContainsString('id="f-cat"', $html);
        $filter = substr($html, (int) strpos($html, 'id="f-cat"'));
        $filter = substr($filter, 0, (int) strpos($filter, 'id="f-size"') ?: null);
        $this->assertStringContainsString('Jeans', $filter);
        $this->assertStringNotContainsString('Poleras', $filter);
        $this->assertStringNotContainsString('Pantalones', $filter);
        $this->assertStringNotContainsString('Accesorios', $filter);
    }

    public function test_the_women_shop_and_home_only_offer_baby_tees_and_tops(): void
    {
        $this->assertSame(['baby-tees', 'tops'], array_keys(\App\Support\StorefrontCategories::shop('mujer')));
        $this->assertNotNull(\App\Support\StorefrontCategories::find('joggers', 'mujer'));

        $html = $this->withSession(['storefront.line' => 'mujer'])->get(route('web.products.search'))->assertOk()->getContent();
        $filter = substr($html, (int) strpos($html, 'id="f-cat"'));
        $filter = substr($filter, 0, (int) strpos($filter, 'id="f-size"') ?: null);
        $this->assertStringContainsString('Baby tees', $filter);
        $this->assertStringContainsString('Tops', $filter);
        $this->assertStringNotContainsString('Poleras', $filter);
        $this->assertStringNotContainsString('Joggers', $filter);
        $this->assertStringNotContainsString('Sets', $filter);

        $home = $this->withSession(['storefront.line' => 'mujer'])->get('/')->assertOk()->getContent();
        $section = substr($home, (int) strpos($home, 'Básicos para todos los días'));
        $section = substr($section, 0, (int) strpos($section, 'Por qué elegir Overshark'));
        $this->assertStringContainsString('Baby tees', $section);
        $this->assertStringContainsString('Tops', $section);
        $this->assertStringNotContainsString('Poleras', $section);
        $this->assertStringNotContainsString('Joggers', $section);
    }
}
