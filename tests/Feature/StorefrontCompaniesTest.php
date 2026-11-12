<?php

namespace Tests\Feature;

use App\Models\Admin\Producto;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GivesProductsColorPhotos;
use Tests\TestCase;

/** La tienda pública solo vende lo de las empresas de ZAZU_COMPANIES (Overshark y Bravos): nunca BOX PRIME ni otras. */
class StorefrontCompaniesTest extends TestCase
{
    use GivesProductsColorPhotos;
    use RefreshDatabase;

    private function product(string $name, ?string $company, array $extra = []): Producto
    {
        return Producto::query()->create($extra + [
            'name' => $name, 'default_code' => $name.'-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 5, 'price' => 40,
            'zazu_company_id' => 1, 'empresa_nombre' => $company, 'zazu_source_key' => 'variante:'.$name,
        ]);
    }

    public function test_products_of_other_companies_never_show_even_with_stock_and_photos(): void
    {
        $own = $this->product('CLASICO', 'OVERSHARK PERU S.A.C');
        $bravos = $this->product('POLERA NERU', 'BRAVOS', ['marca' => 'BRAVOS']);
        $legacy = $this->product('WAFFLE', null);
        $boxPrime = $this->product('[Boxp_Ref0001] Airpods Pro 2', 'BOX PRIME', ['price' => 99]);

        $this->withSession(['storefront.line' => 'hombre'])->get(route('web.products.search'))
            ->assertOk()->assertSee('Clasico')->assertSee('Waffle')->assertDontSee('Airpods')->assertDontSee('Boxp_Ref0001');

        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertOk()->assertDontSee('Airpods');
        $this->get(route('web.products.search', ['q' => 'airpods']))->assertDontSee('Airpods');

        $this->get(route('web.products.show', $own->id))->assertOk();
        $this->get(route('web.products.show', $bravos->id))->assertOk();
        $this->get(route('web.products.show', $legacy->id))->assertOk();
        $this->get(route('web.products.show', $boxPrime->id))->assertNotFound();
    }

    public function test_the_combo_form_only_offers_products_of_the_store_companies(): void
    {
        $this->product('CLASICO', 'OVERSHARK PERU S.A.C');
        $this->product('POLERA NERU', 'BRAVOS');
        $this->product('AIR PODS PRO MAX', 'BOX PRIME', ['stock' => 0, 'price' => 0]);

        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateJwt::class)->actingAs(\App\Models\User::factory()->create())
            ->get(route('admin.combos.index'))
            ->assertOk()->assertSee('CLASICO · OVERSHARK PERU S.A.C')->assertSee('POLERA NERU · BRAVOS')
            ->assertDontSee('AIR PODS PRO MAX')->assertDontSee('BOX PRIME');
    }

    public function test_a_product_of_another_company_cannot_be_added_to_the_cart(): void
    {
        $boxPrime = $this->product('CASE LA', 'BOX PRIME', ['price' => 10]);

        $this->post(route('web.cart.store'), ['producto_id' => $boxPrime->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1])
            ->assertSessionHasErrors('cart');
        $this->assertEmpty(session('cart.items', []));
    }

    public function test_a_cart_that_already_had_a_product_of_another_company_is_blocked_at_checkout(): void
    {
        $boxPrime = $this->product('AIRPODS', 'BOX PRIME', ['price' => 99]);

        $this->withSession(['cart.items' => [(string) $boxPrime->id => [
            'producto_id' => $boxPrime->id, 'variant_id' => $boxPrime->id, 'producto' => 'AIRPODS', 'qty' => 1, 'price' => 99.0, 'color' => 'NEGRO', 'talla' => 'M',
        ]]]);

        $this->assertStringContainsString('ya no tiene stock suficiente', (string) Checkout::verifyCart()['error']);
    }

    public function test_the_company_list_never_ends_up_empty(): void
    {
        // Una variable vacía no debe abrir la tienda a todo el inventario de Zazu.
        $previous = [$_ENV['ZAZU_COMPANIES'] ?? null, $_SERVER['ZAZU_COMPANIES'] ?? null];
        $_ENV['ZAZU_COMPANIES'] = $_SERVER['ZAZU_COMPANIES'] = '';

        try {
            $config = require config_path('services.php');
        } finally {
            foreach (['_ENV', '_SERVER'] as $i => $bag) {
                $previous[$i] === null ? $GLOBALS[$bag] = array_diff_key($GLOBALS[$bag], ['ZAZU_COMPANIES' => 1]) : $GLOBALS[$bag]['ZAZU_COMPANIES'] = $previous[$i];
            }
        }

        $this->assertSame(['OVERSHARK', 'BRAVOS'], $config['zazu']['companies']);
    }
}
