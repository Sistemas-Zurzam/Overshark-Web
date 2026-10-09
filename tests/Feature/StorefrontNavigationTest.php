<?php

namespace Tests\Feature;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_links_to_real_categories_and_not_to_admin(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSee(route('web.products.search', ['orden' => 'novedades']), false)
            ->assertSee(route('web.products.search', ['categoria' => 'polos']), false)
            ->assertSee(route('web.products.search', ['categoria' => 'camisas']), false)
            ->assertSee(route('web.products.search', ['categoria' => 'pantalones']), false)
            ->assertSee(route('web.products.search', ['categoria' => 'accesorios']), false)
            ->assertSee(route('web.explore'), false)
            ->assertSee('Mi cuenta · Próximamente')
            ->assertDontSee(route('admin.dashboard'), false);
    }

    public function test_virtual_fitting_banner_explains_that_ai_is_not_active(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])
            ->get('/')
            ->assertOk()
            ->assertSeeText('Arma tu outfit con IA')
            ->assertSee('La generación del modelo con IA aún no está activa.')
            ->assertSee('href="'.route('web.outfit.show').'"', false)
            ->assertDontSee('disabled aria-describedby', false);
    }

    public function test_category_filter_matches_product_name_or_synced_category(): void
    {
        $bottoms = Categoria::query()->create(['name' => 'Pantalones']);

        $this->product('POLO PIQUE CLASICO');
        $this->product('JOGGER URBANO');
        $this->product('CARGO NEGRO', ['categoria_id' => $bottoms->id]);

        $this->get(route('web.products.search', ['categoria' => 'pantalones']))
            ->assertOk()
            ->assertSee('JOGGER URBANO')
            ->assertSee('CARGO NEGRO')
            ->assertDontSee('POLO PIQUE CLASICO');
    }

    public function test_news_lists_the_latest_products_first(): void
    {
        $this->product('POLO ANTIGUO', ['created_at' => now()->subMonth()]);
        $this->product('POLO RECIENTE', ['created_at' => now()]);

        $this->get(route('web.products.search', ['orden' => 'novedades']))
            ->assertOk()
            ->assertSee('Novedades')
            ->assertSeeInOrder(['POLO RECIENTE', 'POLO ANTIGUO']);
    }

    private function product(string $name, array $attributes = []): Producto
    {
        $product = Producto::query()->create([
            'name' => $name,
            'default_code' => str_replace(' ', '-', $name),
            'stock' => 5,
            'price' => 39.9,
            'zazu_company_id' => 1,
            ...array_diff_key($attributes, ['created_at' => true]),
        ]);

        if (isset($attributes['created_at'])) {
            $product->forceFill(['created_at' => $attributes['created_at']])->saveQuietly();
        }

        return $product;
    }
}
