<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GivesProductsColorPhotos;
use Tests\TestCase;

/** "Construye tu look": la clienta elige prendas del catálogo (también de Bravos), sin depender de combos armados. */
class FreeOutfitBuilderTest extends TestCase
{
    use GivesProductsColorPhotos;
    use RefreshDatabase;

    private function variant(string $name, string $company, string $size = 'M', float $price = 40): Producto
    {
        return Producto::query()->create([
            'name' => $name, 'default_code' => "{$name}-{$size}", 'color' => 'NEGRO', 'talla' => $size, 'stock' => 5, 'price' => $price,
            'zazu_company_id' => $company === 'BRAVOS' ? 2 : 1, 'empresa_nombre' => $company, 'zazu_source_key' => "v:{$name}:{$size}",
        ]);
    }

    public function test_the_builder_works_without_any_outfit_combo_and_offers_bravos_garments(): void
    {
        $this->variant('CLASICO', 'OVERSHARK PERU S.A.C');
        $this->variant('POLERA NERU', 'BRAVOS', 'L', 80);
        $this->variant('PANTALON CARGO', 'BRAVOS', 'L', 90);

        $response = $this->withSession(['storefront.line' => 'hombre'])->get(route('web.outfit'))->assertOk();

        $catalog = collect($response->viewData('catalog'));
        $this->assertEqualsCanonicalizing(['CLASICO', 'POLERA NERU', 'PANTALON CARGO'], $catalog->pluck('name')->all());
        $this->assertSame('Bravos', $catalog->firstWhere('name', 'POLERA NERU')['brand']);
        $this->assertSame('Overshark', $catalog->firstWhere('name', 'CLASICO')['brand']);
        $response->assertSee('data-free-look', false)->assertSee('data-fl-modal', false)->assertDontSee('Estamos preparando los looks');
    }

    public function test_admin_chooses_which_brands_the_builder_offers(): void
    {
        $this->variant('CLASICO', 'OVERSHARK PERU S.A.C');
        $this->variant('POLERA NERU', 'BRAVOS', 'L', 80);

        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class)->actingAs(\App\Models\User::factory()->create());

        // Sin nada guardado se ofrecen todas las marcas.
        $names = fn () => collect($this->withSession(['storefront.line' => 'hombre'])->get(route('web.outfit'))->viewData('catalog'))->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['CLASICO', 'POLERA NERU'], $names());
        $this->get(route('admin.outfits.index'))->assertOk()->assertSee('name="brands[]"', false);

        $this->put(route('admin.outfits.brands'), ['brands' => ['Overshark']])->assertSessionHasNoErrors();
        $this->assertSame(['CLASICO'], $names());

        $this->put(route('admin.outfits.brands'), ['brands' => ['Bravos']]);
        $this->assertSame(['POLERA NERU'], $names());

        // Sin ninguna marca no se guarda: el constructor no puede quedar vacío por error.
        $this->put(route('admin.outfits.brands'), ['brands' => []])->assertSessionHasErrors('brands');
        $this->assertSame(['POLERA NERU'], $names());
    }

    public function test_girls_line_only_offers_girls_garments(): void
    {
        $this->variant('CLASICO', 'OVERSHARK PERU S.A.C');
        $this->variant('BABY TY', 'OVERSHARK PERU S.A.C');

        $catalog = collect($this->withSession(['storefront.line' => 'mujer'])->get(route('web.outfit'))->viewData('catalog'));

        $this->assertSame(['BABY TY'], $catalog->pluck('name')->all());
    }

    public function test_the_chosen_garments_go_to_the_cart_at_their_individual_price(): void
    {
        $top = $this->variant('CLASICO', 'OVERSHARK PERU S.A.C', 'M', 40);
        $bottom = $this->variant('PANTALON CARGO', 'BRAVOS', 'L', 90);

        $this->post(route('web.cart.look'), ['items' => [
            ['producto_id' => $top->id, 'color' => 'NEGRO', 'talla' => 'M'],
            ['producto_id' => $bottom->id, 'color' => 'NEGRO', 'talla' => 'L'],
        ]])->assertRedirect();

        $this->assertEqualsCanonicalizing([40.0, 90.0], collect(session('cart.items'))->pluck('price')->map(fn ($price) => (float) $price)->all());
    }
}
