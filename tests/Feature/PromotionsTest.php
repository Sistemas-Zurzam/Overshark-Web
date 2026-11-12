<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Producto;
use App\Models\Admin\Promocion;
use App\Models\User;
use App\Support\Checkout;
use App\Support\Promotions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GivesProductsColorPhotos;
use Tests\TestCase;

class PromotionsTest extends TestCase
{
    use GivesProductsColorPhotos;
    use RefreshDatabase;

    private Producto $polo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 10, 'price' => 100,
            'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK', 'marca' => 'OVERSHARK',
        ]);
    }

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function promo(array $overrides = []): Promocion
    {
        return Promocion::query()->create(array_merge([
            'name' => 'Relámpago',
            'scope_type' => 'producto',
            'scope_values' => ['CLASICO'],
            'discount_type' => 'porcentaje',
            'discount_value' => 30,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
            'active' => true,
        ], $overrides));
    }

    public function test_percent_and_fixed_prices_never_raise_the_price(): void
    {
        $this->assertSame(70.0, $this->promo()->priceFor(100.0));
        $this->assertSame(59.9, $this->promo(['discount_type' => 'precio_fijo', 'discount_value' => 59.90])->priceFor(100.0));
        // Un precio fijo mayor al regular no sube el precio.
        $this->assertSame(50.0, $this->promo(['discount_type' => 'precio_fijo', 'discount_value' => 80])->priceFor(50.0));
    }

    public function test_status_follows_the_clock_and_the_pause_switch(): void
    {
        $this->assertSame('activa', $this->promo()->status());
        $this->assertSame('programada', $this->promo(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)])->status());
        $this->assertSame('finalizada', $this->promo(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()])->status());
        $this->assertSame('inactiva', $this->promo(['active' => false])->status());
    }

    public function test_the_promotion_applies_by_product_brand_or_category_and_the_cheapest_wins(): void
    {
        $this->promo(['scope_type' => 'marca', 'scope_values' => ['overshark'], 'discount_value' => 10]);
        $this->assertSame(90.0, Promotions::priceFor($this->polo->fresh()));

        // «CLASICO» es un polo de la línea hombre (categorías de la tienda: Admin → Contenido de la tienda).
        $this->promo(['scope_type' => 'categoria', 'scope_values' => ['hombre:polos'], 'discount_value' => 40]);
        $this->assertSame(60.0, Promotions::priceFor($this->polo->fresh()));

        $this->promo(['discount_value' => 20]); // por prenda
        $this->assertSame(60.0, Promotions::priceFor($this->polo->fresh()), 'no se acumulan: gana la más barata');

        $other = Producto::query()->create(['name' => 'OTRO', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 1, 'price' => 100, 'marca' => 'BRAVOS', 'empresa_nombre' => 'BRAVOS']);
        $this->assertSame(100.0, Promotions::priceFor($other), 'otra marca y sin categoría: precio regular');
    }

    public function test_a_scheduled_expired_or_paused_promotion_does_not_change_the_price(): void
    {
        $this->promo(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(3)]);
        $this->promo(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
        $this->promo(['active' => false]);

        $this->assertSame(100.0, Promotions::priceFor($this->polo->fresh()));
    }

    public function test_the_product_page_shows_the_promo_price_with_countdown_and_the_cart_charges_it(): void
    {
        $this->promo();

        $this->get(route('web.products.show', $this->polo->id))
            ->assertOk()
            ->assertSee('S/ 70.00')
            ->assertSee('S/ 100.00')
            ->assertSee('-30%')
            ->assertSee('data-countdown', false);

        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);

        $item = session('cart.items')[(string) $this->polo->id];
        $this->assertSame(70.0, $item['price']);
        $this->assertSame(100.0, $item['regular_price']);

        $this->get(route('web.cart.index'))->assertOk()->assertSee('Oferta')->assertSee('S/ 140.00');
    }

    public function test_checkout_charges_the_promo_price_and_returns_to_regular_when_it_ends(): void
    {
        $this->promo();
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1]);

        $verified = Checkout::verifyCart();
        $this->assertNull($verified['error']);
        $this->assertSame(70.0, (float) $verified['lines']->first()['price']);

        // La oferta termina con el carrito abierto: se avisa el cambio y se cobra el precio regular.
        $this->travel(3)->hours();

        $this->get(route('web.cart.index'))
            ->assertOk()
            ->assertSee('El precio de Clasico cambió de S/ 70.00 a S/ 100.00.');

        $verified = Checkout::verifyCart();
        $this->assertSame(100.0, (float) $verified['lines']->first()['price']);
        $this->assertNull(session('cart.items')[(string) $this->polo->id]['regular_price']);
    }

    public function test_pausing_a_promotion_brings_the_regular_price_back_at_once(): void
    {
        $promo = $this->promo();
        $this->assertSame(70.0, Promotions::priceFor($this->polo->fresh()));

        $this->admin()->patch(route('admin.promociones.toggle', $promo))->assertRedirect();

        $this->assertSame(100.0, Promotions::priceFor($this->polo->fresh()));
    }

    public function test_the_home_shows_flash_deals_with_countdown_only_while_a_promotion_is_live(): void
    {
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()->assertDontSee('Ofertas relámpago');

        $promo = $this->promo();

        $this->withSession(['storefront.line' => 'hombre'])->get('/')
            ->assertOk()
            ->assertSee('Ofertas relámpago')
            ->assertSee('S/ 70.00')
            ->assertSee('data-countdown', false);

        $promo->update(['active' => false]);
        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()->assertDontSee('Ofertas relámpago');
    }

    public function test_admin_creates_a_promotion_in_lima_time_and_it_is_stored_in_utc(): void
    {
        $this->admin();

        $this->post(route('admin.promociones.store'), [
            'name' => 'Viernes',
            'scope_type' => 'producto',
            'scope_products' => ['CLASICO'],
            'discount_type' => 'porcentaje',
            'discount_value' => 25,
            'starts_at' => '2026-11-13T18:00',
            'ends_at' => '2026-11-13T22:30',
            'active' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.promociones.index'));

        $promo = Promocion::query()->sole();
        // Lima es UTC-5: las 18:00 de Lima son las 23:00 UTC.
        $this->assertSame('2026-11-13 23:00', $promo->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-11-13 18:00', $promo->local('starts_at')->format('Y-m-d H:i'));
        $this->assertSame(['CLASICO'], $promo->scope_values);

        $this->get(route('admin.promociones.index'))->assertOk()->assertSee('Viernes')->assertSee('18:00');
        $this->get(route('admin.promociones.edit', $promo))->assertOk()->assertSee('2026-11-13T18:00', false);
    }

    public function test_admin_validates_scope_discount_and_dates(): void
    {
        $this->admin();

        $this->post(route('admin.promociones.store'), [
            'name' => 'Mala',
            'scope_type' => 'marca',
            'discount_type' => 'porcentaje',
            'discount_value' => 95,
            'starts_at' => '2026-11-13T18:00',
            'ends_at' => '2026-11-13T17:00',
        ])->assertSessionHasErrors(['scope_brands', 'discount_value', 'ends_at']);

        $this->assertDatabaseCount('promociones', 0);
    }

    public function test_the_list_warns_when_a_promotion_changes_no_price(): void
    {
        // Un precio fijo de S/ 150 sobre una prenda de S/ 100 nunca baja nada; uno de S/ 80 sí.
        $this->promo(['name' => 'Fijo alto', 'discount_type' => 'precio_fijo', 'discount_value' => 150]);
        $this->promo(['name' => 'Fijo bajo', 'discount_type' => 'precio_fijo', 'discount_value' => 80]);
        // Una prenda que no existe a la venta: tampoco se vería.
        $this->promo(['name' => 'Sin prendas', 'scope_values' => ['NO EXISTE']]);

        $response = $this->admin()->get(route('admin.promociones.index'))->assertOk();

        $response->assertSee('no baja el precio de ninguna prenda', false)
            ->assertSee('La más cara de las elegidas cuesta S/ 100.00', false)
            ->assertSee('ninguna prenda a la venta coincide con lo elegido');
        $this->assertSame(2, substr_count($response->getContent(), 'Esta promoción no se ve en la tienda'));
    }
}
