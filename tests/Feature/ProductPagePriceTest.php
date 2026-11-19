<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GivesProductsColorPhotos;
use Tests\TestCase;

/** La ficha muestra el precio de la variante elegida (color + talla), el mismo que se cobra en el carrito. */
class ProductPagePriceTest extends TestCase
{
    use GivesProductsColorPhotos;
    use RefreshDatabase;

    private function variant(string $color, string $size, float $price): Producto
    {
        return Producto::query()->create([
            'name' => 'CUELLO CHINO WAFFLE', 'default_code' => "CCW-{$color}-{$size}", 'color' => $color, 'talla' => $size, 'stock' => 5, 'price' => $price,
            'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK PERU S.A.C', 'zazu_source_key' => "variante:{$color}:{$size}",
        ]);
    }

    public function test_the_page_price_follows_the_chosen_variant_and_matches_the_cart(): void
    {
        $cheap = $this->variant('NEGRO', 'M', 3);
        $this->variant('AZUL', 'S', 35);

        $response = $this->get(route('web.products.show', $cheap->id))->assertOk();

        // Cada color y talla lleva su precio: así la ficha lo cambia al elegir y no muestra solo el menor.
        $map = $response->viewData('priceMap');
        $this->assertEquals(3, $map['NEGRO']['M']['now']);
        $this->assertEquals(35, $map['AZUL']['S']['now']);
        $response->assertSee('data-pdp-prices=', false)->assertSee('data-pdp-price-now', false);

        // Lo que el carrito cobra por AZUL S es lo que la ficha anuncia para esa variante.
        $this->post(route('web.cart.store'), ['producto_id' => $cheap->id, 'color' => 'AZUL', 'talla' => 'S', 'qty' => 1]);
        $line = collect(session('cart.items'))->first();
        $this->assertEquals($map['AZUL']['S']['now'], $line['price']);
    }
}
