<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\CartSuggestion;
use App\Models\Admin\Producto;
use App\Models\User;
use App\Support\CartSuggestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Te puede interesar": modal al agregar al carrito, reglas del panel y prendas de respaldo de la misma marca. */
class CartSuggestionsTest extends TestCase
{
    use \Tests\Concerns\GivesProductsColorPhotos;
    use RefreshDatabase;

    private Producto $polo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = $this->product('CLASICO', 'NEGRO', 'M');
    }

    private function product(string $name, string $color = 'NEGRO', string $size = 'M', array $extra = []): Producto
    {
        return Producto::query()->create($extra + [
            'name' => $name, 'default_code' => $name.'-'.$color.'-'.$size, 'color' => $color, 'talla' => $size,
            'stock' => 5, 'price' => 40, 'marca' => 'OVERSHARK', 'zazu_company_id' => 1, 'imagen' => 'https://example.com/prenda.jpg',
        ]);
    }

    private function rule(array $attributes = []): CartSuggestion
    {
        return CartSuggestion::query()->create($attributes + [
            'name' => 'Regla', 'trigger_type' => 'producto', 'trigger_value' => 'CLASICO', 'products' => ['BABY TEE'], 'active' => true, 'sort' => 0,
        ]);
    }

    private function add(Producto $product, array $extra = [])
    {
        return $this->post(route('web.cart.store'), $extra + [
            'producto_id' => $product->id, 'color' => $product->color, 'talla' => $product->talla, 'qty' => 1,
        ]);
    }

    private function names(Producto $added): array
    {
        return CartSuggestions::forProduct($added)->pluck('name')->all();
    }

    public function test_a_product_rule_suggests_its_garments_with_colors_and_sizes(): void
    {
        $this->product('BABY TEE', 'DENIM', 'S');
        $this->product('BABY TEE', 'DENIM', 'M');
        $this->product('BABY TEE', 'ROSA', 'L');
        $this->rule();

        $card = CartSuggestions::forProduct($this->polo)->first();

        $this->assertSame('BABY TEE', $card['name']);
        $this->assertSame(['DENIM' => ['S', 'M'], 'ROSA' => ['L']], $card['colors']);
        $this->assertEquals(40.0, $card['price']);
    }

    public function test_adding_to_the_cart_opens_the_suggestions_modal_instead_of_the_drawer(): void
    {
        $this->product('BABY TEE', 'DENIM', 'S');
        $this->rule(['title' => 'Combínalo con']);

        $this->add($this->polo)->assertSessionHas('cart_suggest', $this->polo->id)->assertSessionMissing('cart_open');

        $this->get(route('web.products.show', $this->polo->id))
            ->assertOk()
            ->assertSee('data-suggest-modal', false)
            ->assertSee('Agregado a tu carrito')
            ->assertSee('Baby Tee')
            ->assertSee('data-open="false"', false);
    }

    public function test_without_suggestions_the_drawer_opens_as_before(): void
    {
        $this->add($this->polo)->assertSessionHas('cart_open', true)->assertSessionMissing('cart_suggest');

        $this->get(route('web.products.show', $this->polo->id))->assertDontSee('data-suggest-modal', false)->assertSee('data-open="true"', false);
    }

    public function test_it_never_suggests_what_is_already_in_the_cart_or_is_sold_out(): void
    {
        $tee = $this->product('BABY TEE', 'DENIM', 'S');
        $this->product('GORRA', 'NEGRO', 'U', ['stock' => 0]);
        $this->product('POLERA', 'NEGRO', 'M');
        $this->rule(['products' => ['BABY TEE', 'GORRA', 'POLERA']]);

        $this->assertSame(['BABY TEE', 'POLERA'], $this->names($this->polo));

        $this->add($tee);
        $this->assertSame(['POLERA'], $this->names($this->polo));
    }

    public function test_a_brand_rule_applies_to_any_garment_of_that_brand_and_product_rules_go_first(): void
    {
        $this->product('BABY TEE', 'DENIM', 'S');
        $this->product('GORRA', 'NEGRO', 'U');
        $this->rule(['name' => 'Marca', 'trigger_type' => 'marca', 'trigger_value' => 'overshark', 'products' => ['GORRA']]);
        $this->rule(['name' => 'Prenda', 'products' => ['BABY TEE']]);

        $this->assertSame(['BABY TEE', 'GORRA'], $this->names($this->polo));
    }

    public function test_inactive_and_out_of_date_rules_are_ignored_and_the_brand_fallback_fills_in(): void
    {
        $this->product('BABY TEE', 'DENIM', 'S');
        $this->product('GORRA', 'NEGRO', 'U', ['marca' => 'BRAVOS']);
        $this->rule(['products' => ['GORRA'], 'active' => false]);
        $this->rule(['products' => ['GORRA'], 'ends_at' => now()->subDay()]);
        $this->rule(['products' => ['GORRA'], 'starts_at' => now()->addDay()]);

        // Sin reglas vigentes: prendas con stock de la misma marca (OVERSHARK), no de otra.
        $this->assertSame(['BABY TEE'], $this->names($this->polo));
    }

    public function test_adding_from_the_cart_page_suggestions_does_not_reopen_the_modal(): void
    {
        $tee = $this->product('BABY TEE', 'DENIM', 'S');
        $this->rule();
        $this->add($this->polo);

        $this->add($tee, ['quiet' => 1])->assertSessionMissing('cart_suggest')->assertSessionMissing('cart_open');

        $this->assertSame(2, collect(session('cart.items'))->count());
    }

    public function test_the_cart_page_shows_suggestions_for_the_whole_cart(): void
    {
        $this->product('BABY TEE', 'DENIM', 'S');
        $this->rule();
        $this->add($this->polo);

        $this->get(route('web.cart.index'))->assertOk()->assertSee('Te puede interesar')->assertSee('Baby Tee');
    }

    public function test_the_admin_can_create_edit_disable_and_delete_a_rule(): void
    {
        $this->product('BABY TEE', 'DENIM', 'S');
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());

        $this->get(route('admin.sugerencias.index'))->assertOk()->assertSee('Sugerencias del carrito');

        $this->post(route('admin.sugerencias.store'), [
            'name' => 'Polo con Baby Tee', 'trigger_type' => 'producto', 'trigger_product' => 'CLASICO', 'products' => ['BABY TEE'], 'sort' => 1,
        ])->assertRedirect(route('admin.sugerencias.index'));
        $rule = CartSuggestion::query()->firstOrFail();
        $this->assertSame('CLASICO', $rule->trigger_value);
        $this->assertTrue($rule->active);

        $this->get(route('admin.sugerencias.edit', $rule))->assertOk()->assertSee('Polo con Baby Tee');
        $this->put(route('admin.sugerencias.update', $rule), [
            'name' => 'Marca', 'trigger_type' => 'marca', 'trigger_brand' => 'OVERSHARK', 'products' => ['BABY TEE'], 'active' => 0,
        ])->assertRedirect();
        $this->assertSame(['marca', 'OVERSHARK', false], [$rule->refresh()->trigger_type, $rule->trigger_value, $rule->active]);

        $this->patch(route('admin.sugerencias.toggle', $rule))->assertRedirect();
        $this->assertTrue($rule->refresh()->active);

        $this->delete(route('admin.sugerencias.destroy', $rule))->assertRedirect();
        $this->assertDatabaseCount('cart_suggestions', 0);
    }

    public function test_the_admin_form_requires_a_trigger_and_at_least_one_garment(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());

        $this->post(route('admin.sugerencias.store'), ['name' => 'X', 'trigger_type' => 'producto', 'products' => []])
            ->assertSessionHasErrors(['trigger_product', 'products']);
    }
}
