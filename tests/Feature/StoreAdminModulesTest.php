<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Cliente;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\Promocion;
use App\Models\Admin\StatusOrder;
use App\Models\CustomerAccount;
use App\Models\User;
use App\Services\ZazuInventorySyncService;
use App\Support\Checkout;
use App\Support\Promotions;
use App\Support\SiteContent;
use App\Support\StorefrontCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\GivesProductsColorPhotos;
use Tests\TestCase;

/** Hospedaje de lo que el panel administra de la tienda: ocultar productos, categorías, fotos, clientes y agencias. */
class StoreAdminModulesTest extends TestCase
{
    use GivesProductsColorPhotos;
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function product(string $name = 'CLASICO', array $extra = []): Producto
    {
        return Producto::query()->create($extra + [
            'name' => $name, 'default_code' => $name.'-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 5, 'price' => 40,
            'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK', 'marca' => 'OVERSHARK', 'zazu_source_key' => 'variante:'.$name,
        ]);
    }

    // ---- Ocultar productos -------------------------------------------------------------------------------------

    public function test_a_hidden_product_disappears_from_the_store_and_cannot_be_bought_but_the_rest_stays(): void
    {
        $polo = $this->product('CLASICO');
        $second = $this->product('CLASICO', ['talla' => 'L', 'zazu_source_key' => 'variante:CLASICO-L']);
        $other = $this->product('WAFFLE');

        $this->get(route('web.products.show', $polo->id))->assertOk();
        $this->withSession(['storefront.line' => 'hombre'])->get(route('web.products.search'))->assertSee('Clasico');

        $this->admin()->patch(route('admin.productos.visibility', $polo))->assertRedirect();

        $this->assertTrue((bool) $polo->fresh()->oculto);
        $this->assertTrue((bool) $second->fresh()->oculto, 'se ocultan todas las variantes del producto');
        $this->assertFalse((bool) $other->fresh()->oculto);

        $this->get(route('web.products.show', $polo->id))->assertNotFound();
        $this->withSession(['storefront.line' => 'hombre'])->get(route('web.products.search'))->assertDontSee('Clasico')->assertSee('Waffle');
        $this->post(route('web.cart.store'), ['producto_id' => $polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1])->assertSessionHasErrors('cart');
        $this->assertEmpty(session('cart.items', []));
    }

    public function test_a_product_hidden_while_in_the_cart_blocks_the_checkout_and_showing_it_again_restores_it(): void
    {
        $polo = $this->product();
        $this->post(route('web.cart.store'), ['producto_id' => $polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1]);
        $this->assertNull(Checkout::verifyCart()['error']);

        $this->admin()->patch(route('admin.productos.visibility', $polo));
        $this->assertStringContainsString('ya no tiene stock suficiente', (string) Checkout::verifyCart()['error']);

        $this->admin()->patch(route('admin.productos.visibility', $polo));
        $this->assertNull(Checkout::verifyCart()['error']);
        $this->get(route('web.products.show', $polo->id))->assertOk();
    }

    public function test_the_zazu_sync_does_not_show_a_hidden_product_again(): void
    {
        config(['services.zazu.base_url' => 'https://zazu.test', 'services.zazu.token' => 'token', 'services.zazu.companies' => ['OVERSHARK']]);
        Http::fake(['zazu.test/*' => Http::response(['data' => [[
            'zazu_product_id' => 1, 'zazu_variant_id' => 1, 'sku' => 'CL-N-M', 'nombre' => 'CLASICO', 'talla' => 'M', 'color' => 'NEGRO',
            'disponible_venta' => 9, 'precio_venta' => 55, 'empresa' => ['id' => 1, 'nombre' => 'OVERSHARK'],
        ]], 'last_page' => 1])]);

        app(ZazuInventorySyncService::class)->sync();
        $polo = Producto::query()->where('name', 'CLASICO')->sole();
        $this->admin()->patch(route('admin.productos.visibility', $polo));

        app(ZazuInventorySyncService::class)->sync();

        $polo = $polo->fresh();
        $this->assertTrue((bool) $polo->oculto);
        $this->assertSame(9, (int) $polo->stock, 'el stock sí se sigue actualizando');
    }

    public function test_the_admin_list_marks_hidden_products(): void
    {
        $polo = $this->product();
        $this->admin()->patch(route('admin.productos.visibility', $polo));

        $this->admin()->get(route('admin.productos.index'))->assertOk()->assertSee('Oculto de la tienda');
        $this->admin()->get(route('admin.productos.show', $polo))->assertOk()->assertSee('Mostrar en la tienda');
    }

    // ---- Categorías --------------------------------------------------------------------------------------------

    public function test_categories_can_be_renamed_and_regrouped_and_an_empty_field_restores_the_original(): void
    {
        $this->assertSame('Polos', StorefrontCategories::label('polos', 'hombre'));
        $this->assertSame('polos', StorefrontCategories::slugFor('JERSEY MANGA LARGA', null, 'hombre'));

        $this->admin()->put(route('admin.contenido.update'), ['c' => ['categorias' => ['hombre' => [
            'polos' => ['label' => 'Camisetas', 'terms' => 'polo, jersey, Slim Fit'],
            'camisas' => ['label' => '', 'terms' => ''],
        ]]]])->assertSessionHasNoErrors();

        $this->assertSame('Camisetas', StorefrontCategories::label('polos', 'hombre'));
        $this->assertSame('Camisas', StorefrontCategories::label('camisas', 'hombre'), 'vacío = original');
        $this->assertSame('polos', StorefrontCategories::slugFor('SLIM FIT NEGRO', null, 'hombre'));
        $this->assertNull(StorefrontCategories::slugFor('CLASICO', null, 'hombre'), 'ya no es de Polos: se quitó «clasico»');

        $this->admin()->put(route('admin.contenido.update'), ['c' => ['categorias' => ['hombre' => ['polos' => ['label' => '', 'terms' => '']]]]]);
        $this->assertSame('Polos', StorefrontCategories::label('polos', 'hombre'));
        $this->assertSame('polos', StorefrontCategories::slugFor('CLASICO', null, 'hombre'));
    }

    public function test_a_promotion_by_store_category_applies_only_to_that_category_and_line(): void
    {
        $polo = $this->product('CLASICO');
        $shirt = $this->product('CAMISA LINO', ['zazu_source_key' => 'variante:camisa']);
        $baby = $this->product('BABY TY', ['marca' => 'OVERSHARK GIRLS', 'zazu_source_key' => 'variante:baby']);

        Promocion::query()->create([
            'name' => 'Polos', 'scope_type' => 'categoria', 'scope_values' => ['hombre:polos', 'mujer:baby-tees'],
            'discount_type' => 'porcentaje', 'discount_value' => 50, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'active' => true,
        ]);

        $this->assertSame(20.0, Promotions::priceFor($polo->fresh()));
        $this->assertSame(40.0, Promotions::priceFor($shirt->fresh()), 'camisas no están en la promoción');
        $this->assertSame(20.0, Promotions::priceFor($baby->fresh()), 'baby tee de la línea mujer');

        $names = Promotions::constrain(Producto::query())->pluck('name')->all();
        $this->assertContains('CLASICO', $names);
        $this->assertContains('BABY TY', $names);
        $this->assertNotContains('CAMISA LINO', $names);
    }

    public function test_the_promotion_form_lists_the_store_categories_and_saves_them(): void
    {
        $this->admin()->get(route('admin.promociones.create'))->assertOk()->assertSee('Hombre · Polos')->assertSee('Mujer · Baby tees');

        $this->admin()->post(route('admin.promociones.store'), [
            'name' => 'Polos', 'scope_type' => 'categoria', 'scope_categories' => ['hombre:polos'], 'discount_type' => 'porcentaje',
            'discount_value' => 20, 'starts_at' => '2026-12-01T10:00', 'ends_at' => '2026-12-01T12:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['hombre:polos'], Promocion::query()->sole()->scope_values);
        $this->admin()->get(route('admin.promociones.index'))->assertSee('Hombre · Polos');
    }

    // ---- Fotos y textos de las secciones -----------------------------------------------------------------------

    public function test_an_uploaded_photo_replaces_the_original_everywhere_and_removing_it_brings_the_original_back(): void
    {
        Storage::fake('public');
        $original = asset('images/home-hombre/promo.webp');
        $this->assertSame($original, SiteContent::asset('images/home-hombre/promo.webp'));

        $this->admin()->put(route('admin.contenido.update'), ['images' => ['promo_hombre' => UploadedFile::fake()->image('promo.jpg', 800, 400)]])
            ->assertSessionHasNoErrors();

        $stored = \App\Models\PageContent::contentFor(SiteContent::SLUG)['images']['promo_hombre'];
        Storage::disk('public')->assertExists($stored);
        $this->assertSame('/storage/'.$stored, SiteContent::asset('images/home-hombre/promo.webp'));
        $this->assertSame(asset('images/home-mujer/promo.webp'), SiteContent::asset('images/home-mujer/promo.webp'), 'la de Mujer no cambia');

        $this->withSession(['storefront.line' => 'hombre'])->get('/')->assertSee('/storage/'.$stored, false);

        $this->admin()->put(route('admin.contenido.update'), ['remove_images' => ['promo_hombre' => '1']]);
        $this->assertSame($original, SiteContent::asset('images/home-hombre/promo.webp'));
        Storage::disk('public')->assertMissing($stored);
    }

    public function test_the_home_quality_fabric_and_trust_texts_are_editable(): void
    {
        $this->admin()->put(route('admin.contenido.update'), ['c' => ['home' => [
            'qualities' => [0 => ['title' => 'No pierde el color']],
            'fabrics' => [1 => ['text' => 'Cuadros que no se deforman']],
            'benefits' => [0 => ['text' => 'Lima {lima} y agencia {agencia}.', 'cta' => 'Ver envíos']],
        ]]])->assertSessionHasNoErrors();

        $this->withSession(['storefront.line' => 'hombre'])->get('/')
            ->assertSee('No pierde el color')
            ->assertSee('No encoge')
            ->assertSee('Cuadros que no se deforman')
            ->assertSee('Lima S/ 15 y agencia S/ 14.')
            ->assertSee('Ver envíos');
    }

    // ---- Clientes ----------------------------------------------------------------------------------------------

    public function test_clients_screen_lists_buyers_and_web_accounts_with_search_and_detail(): void
    {
        $status = StatusOrder::query()->firstOrCreate(['name' => 'Pagado']);
        $ana = Cliente::query()->create(['name' => 'Ana', 'apellidos' => 'Pérez', 'documento_identidad' => '75351525', 'cel' => '999111222', 'email' => 'ana@example.com']);
        Cliente::query()->create(['name' => 'Luis', 'apellidos' => 'Rojas', 'documento_identidad' => '11112222', 'email' => 'luis@example.com']);
        $order = Order::query()->create(['number_zazu' => 'OS-1', 'precio_total' => 120, 'cliente_id' => $ana->id, 'cliente_name' => 'Ana', 'status_order_id' => $status->id]);
        CustomerAccount::query()->create(['name' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@example.com', 'password' => 'secreto123'])
            ->forceFill(['email_verified_at' => now()])->save();
        CustomerAccount::query()->create(['name' => 'Sin', 'apellidos' => 'Compras', 'email' => 'sin@example.com', 'password' => 'secreto123']);

        $this->admin()->get(route('admin.clientes.index'))
            ->assertOk()->assertSee('Ana Pérez')->assertSee('Luis Rojas')->assertSee('75351525')->assertSee('S/ 120.00')
            ->assertSee('Compradores (2)')->assertSee('Cuentas web (2)');

        $this->admin()->get(route('admin.clientes.index', ['q' => 'luis']))->assertSee('Luis Rojas')->assertDontSee('Ana Pérez');
        $this->admin()->get(route('admin.clientes.index', ['q' => 'zzz']))->assertSee('Ningún comprador coincide');

        $this->admin()->get(route('admin.clientes.index', ['tab' => 'cuentas']))
            ->assertSee('sin@example.com')->assertSee('Pendiente')->assertSee('Sí');

        $this->admin()->get(route('admin.clientes.show', $ana))
            ->assertOk()->assertSee('ana@example.com')->assertSee('S/ 120.00')->assertSee('correo verificado')
            ->assertSee(route('admin.pedidos.show', $order), false);
    }

    // ---- Agencias Shalom ---------------------------------------------------------------------------------------

    public function test_shalom_agencies_can_be_consulted_filtered_and_searched(): void
    {
        DB::table('courier')->insert([
            ['ter_id' => '900', 'lugar_over' => 'CUSCO - AV. LA CULTURA', 'direccion' => 'AV. LA CULTURA 500', 'zona' => 'CUSCO', 'provincia' => 'CUSCO', 'departamento' => 'CUSCO', 'last_synced_at' => now()],
            ['ter_id' => '901', 'lugar_over' => 'MIRAFLORES - AV. PARDO', 'direccion' => 'AV. PARDO 200', 'zona' => 'MIRAFLORES', 'provincia' => 'LIMA', 'departamento' => 'LIMA', 'last_synced_at' => now()],
        ]);

        $this->admin()->get(route('admin.agencias.index'))->assertOk()->assertSee('AV. LA CULTURA 500')->assertSee('AV. PARDO 200')->assertSee('Última sincronización');
        $this->admin()->get(route('admin.agencias.index', ['departamento' => 'LIMA']))->assertSee('AV. PARDO 200')->assertDontSee('AV. LA CULTURA 500');
        $this->admin()->get(route('admin.agencias.index', ['q' => 'cultura']))->assertSee('AV. LA CULTURA 500')->assertDontSee('AV. PARDO 200');
    }
}
