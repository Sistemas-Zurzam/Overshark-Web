<?php

namespace Tests\Feature;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Support\ComboOffers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboOffersTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $color, int $stock = 5): Producto
    {
        return Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CLASICO-'.$color, 'color' => $color, 'talla' => 'M',
            'stock' => $stock, 'price' => 40, 'marca' => 'OVERSHARK', 'zazu_company_id' => 1,
            'imagen' => 'https://example.com/jersey.jpg',
        ]);
    }

    private function offer(): Combo
    {
        return Combo::query()->create([
            'name' => '3 Clásicos', 'brand' => 'OVERSHARK', 'status' => true, 'price' => 99,
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 3]], 'selection_mode' => 'fixed',
        ]);
    }

    public function test_the_cart_offer_consumes_only_eligible_units_and_allows_editing_prefilled_variants(): void
    {
        $black = $this->product('NEGRO');
        $white = $this->product('BLANCO');
        $combo = $this->offer();

        $this->post(route('web.cart.store'), [
            'producto_id' => $black->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 4,
        ])->assertSessionHas('cart_suggest', $black->id);

        $this->get(route('web.cart.index'))->assertOk()->assertSee('Convierte tu compra en combo')->assertSee('Aplicar combo');
        $this->get(route('web.combos.show', ['combo' => $combo, 'convert' => 1]))
            ->assertOk()->assertSee('3 prendas individuales')->assertSee('En tu carrito');

        $quote = app(ComboOffers::class)->plan($combo, session('cart.items'))['quote'];
        $this->post(route('web.combos.cart.store', $combo), [
            'convert' => 1, 'convert_quote' => $quote, 'selections' => [$black->id, $white->id, $white->id],
        ])->assertRedirect(route('web.cart.index'));

        $items = session('cart.items');
        $this->assertSame(1, $items[$black->id]['qty']);
        $this->assertSame(2, ComboOffers::quantities($items)[$white->id]);
        $this->assertSame(139.0, array_sum(array_map(fn ($line) => $line['price'] * $line['qty'], $items)));
    }

    public function test_stock_rejection_keeps_the_original_cart(): void
    {
        $black = $this->product('NEGRO');
        $white = $this->product('BLANCO', 2);
        $combo = $this->offer();

        $items = [
            $black->id => ['producto_id' => $black->id, 'variant_id' => $black->id, 'qty' => 3, 'price' => 40],
            'combo-existing' => ['kind' => 'combo', 'qty' => 1, 'selections' => [['variant_id' => $white->id]]],
        ];
        $url = route('web.combos.show', ['combo' => $combo, 'convert' => 1]);
        $quote = app(ComboOffers::class)->plan($combo, $items)['quote'];

        $this->withSession(['cart.items' => $items])->from($url)->post(route('web.combos.cart.store', $combo), [
            'convert' => 1, 'convert_quote' => $quote, 'selections' => [$black->id, $white->id, $white->id],
        ])->assertRedirect($url)->assertSessionHasErrors('selections');

        $this->assertSame($items, session('cart.items'));
    }
}
