<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\ApiKey;
use App\Models\Admin\Cliente;
use App\Models\Admin\IntegrationSetting;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\StatusOrder;
use App\Models\Admin\WebhookEndpoint;
use App\Models\User;
use App\Services\Integrations\Webhooks;
use App\Services\Orders\OrderPayments;
use App\Support\Checkout;
use App\Support\Integrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function key(array $abilities): string
    {
        return ApiKey::issue('ERP', $abilities)[1];
    }

    private function order(string $status, array $overrides = []): Order
    {
        $cliente = Cliente::query()->create(['name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'ana@example.com']);

        return Order::query()->create(array_merge([
            'number_zazu' => 'WEB-'.uniqid(), 'precio_total' => 95, 'subtotal' => 80, 'envio' => 15, 'pago_monto' => 95,
            'cliente_id' => $cliente->id, 'cliente_name' => 'Ana Ríos', 'email' => 'ana@example.com', 'cliente_cel' => '987654321',
            'entrega' => 'domicilio', 'direccion' => 'Av. Larco 123', 'metodo_pago_nombre' => 'Tarjeta',
            'status_order_id' => StatusOrder::query()->firstOrCreate(['name' => $status])->id,
            'productos_json' => [['tipo' => 'producto', 'producto' => 'CLASICO', 'variant_id' => 7, 'color' => 'NEGRO', 'talla' => 'M', 'cantidad' => 2, 'precio' => 40]],
        ], $overrides));
    }

    public function test_the_api_needs_an_active_key_with_the_right_permission(): void
    {
        $this->getJson('/api/v1/ping')->assertStatus(401)->assertJson(['message' => 'Clave API inválida o revocada.']);
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer osk_inventada'])->assertStatus(401);

        $catalog = $this->key(['catalog.read']);
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$catalog])->assertOk()->assertJsonPath('key.name', 'ERP');
        $this->getJson('/api/v1/orders', ['Authorization' => 'Bearer '.$catalog])->assertStatus(403);
        $this->get('/api/v1/products', ['X-Api-Key' => $catalog])->assertOk(); // también con X-Api-Key y sin Accept
        $this->assertNotNull(ApiKey::query()->sole()->last_used_at);

        ApiKey::query()->sole()->update(['revoked_at' => now()]);
        $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$catalog])->assertStatus(401);
        $this->assertSame(64, strlen(ApiKey::query()->sole()->token_hash)); // la clave en claro nunca se guarda
    }

    public function test_the_catalog_lists_products_with_their_variants_and_stock(): void
    {
        $base = ['name' => 'CLASICO', 'color' => 'NEGRO', 'price' => 40, 'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK SAC', 'marca' => 'OVERSHARK'];
        $m = Producto::query()->create($base + ['default_code' => 'CL-N-M', 'talla' => 'M', 'stock' => 3]);
        Producto::query()->create($base + ['default_code' => 'CL-N-L', 'talla' => 'L', 'stock' => 0]);
        Producto::query()->create(['name' => 'OCULTO', 'default_code' => 'OC-1', 'talla' => 'M', 'stock' => 5, 'price' => 30])->forceFill(['oculto' => true])->save();
        $headers = ['Authorization' => 'Bearer '.$this->key(['catalog.read'])];

        $this->getJson('/api/v1/products', $headers)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $m->id)
            ->assertJsonPath('data.0.stock', 3)
            ->assertJsonPath('data.0.url', route('web.products.show', $m->id))
            ->assertJsonCount(2, 'data.0.variants')
            ->assertJsonPath('data.0.variants.1.sku', 'CL-N-L')
            ->assertJsonPath('data.0.variants.1.stock', 0)
            ->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/products?in_stock=1', $headers)->assertJsonCount(1, 'data.0.variants');
        $this->getJson('/api/v1/products?updated_since='.urlencode(now()->addDay()->toIso8601String()), $headers)->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products/'.$m->id, $headers)->assertOk()->assertJsonPath('data.name', 'CLASICO');
        $this->getJson('/api/v1/products/999999', $headers)->assertNotFound();
    }

    public function test_orders_can_be_read_and_their_status_updated_with_a_signed_webhook(): void
    {
        Http::fake(['erp.test/*' => Http::response(['ok' => true])]);
        $webhook = WebhookEndpoint::query()->create(['name' => 'ERP', 'url' => 'https://erp.test/hooks', 'events' => ['order.shipped'], 'secret' => 'whsec_test', 'enabled' => true]);
        $paid = $this->order(Checkout::STATUS_PAID);
        $awaiting = $this->order(Checkout::STATUS_AWAITING_QR);
        $read = ['Authorization' => 'Bearer '.$this->key(['orders.read'])];
        $write = ['Authorization' => 'Bearer '.$this->key(['orders.write'])];

        // Por defecto solo los confirmados; status=all incluye los que esperan pago.
        $this->getJson('/api/v1/orders', $read)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paid->id)
            ->assertJsonPath('data.0.customer.email', 'ana@example.com')
            ->assertJsonPath('data.0.totals.total', 95)
            ->assertJsonPath('data.0.items.0.quantity', 2);
        $this->getJson('/api/v1/orders?status=all', $read)->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/orders/'.$paid->id, $read)->assertOk()->assertJsonPath('data.status', Checkout::STATUS_PAID);
        $this->patchJson('/api/v1/orders/'.$paid->id.'/status', ['status' => 'Enviado'], $read)->assertStatus(403);

        $this->patchJson('/api/v1/orders/'.$paid->id.'/status', ['status' => 'Pago confirmado'], $write)->assertStatus(422);
        $this->patchJson('/api/v1/orders/'.$awaiting->id.'/status', ['status' => 'shipped'], $write)->assertStatus(409);
        $this->patchJson('/api/v1/orders/'.$paid->id.'/status', ['status' => 'shipped'], $write)->assertOk()->assertJsonPath('data.status', 'Enviado');
        $this->assertSame('Enviado', $paid->fresh('statusOrder')->statusName());

        Http::assertSent(function (HttpRequest $request) use ($paid) {
            return $request->url() === 'https://erp.test/hooks'
                && $request->header('X-Overshark-Event')[0] === 'order.shipped'
                && $request->header('X-Overshark-Signature')[0] === Webhooks::signature($request->body(), 'whsec_test')
                && $request['data']['id'] === $paid->id;
        });
        $this->assertSame(200, $webhook->fresh()->last_status);
    }

    public function test_a_confirmed_payment_notifies_the_subscribed_webhooks_once(): void
    {
        Http::fake(['erp.test/*' => Http::response([], 500)]);
        $endpoint = WebhookEndpoint::query()->create(['name' => 'ERP', 'url' => 'https://erp.test/hooks', 'events' => ['order.confirmed'], 'secret' => 'whsec_test', 'enabled' => true]);
        WebhookEndpoint::query()->create(['name' => 'Pausado', 'url' => 'https://erp.test/pausado', 'events' => ['order.confirmed'], 'secret' => 'x', 'enabled' => false]);
        $order = $this->order(Checkout::STATUS_AWAITING_QR);

        // Aunque el destino falle, el pago se confirma igual (el aviso se reintenta por la cola).
        OrderPayments::confirm($order, 'pay-1');
        OrderPayments::confirm($order, 'pay-1');

        $this->assertSame(Checkout::STATUS_PAID, $order->fresh('statusOrder')->statusName());
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://erp.test/hooks' && $request['event'] === 'order.confirmed');
        $this->assertSame(500, $endpoint->fresh()->last_status);
        $this->assertSame('Respondió HTTP 500.', $endpoint->fresh()->last_error);
    }

    public function test_admin_manages_api_keys_and_webhooks(): void
    {
        Http::fake(['hooks.test/*' => Http::response(['ok' => true])]);
        $this->admin()->get(route('admin.integraciones.index'))->assertOk()
            ->assertSee('Conexiones de Overshark con otros sistemas')
            ->assertSee(url('/api/v1'))
            ->assertSee('Aún no hay claves');

        $this->post(route('admin.integraciones.keys.store'), ['name' => 'Courier'])->assertSessionHasErrors('abilities');
        $this->post(route('admin.integraciones.keys.store'), ['name' => 'Courier', 'abilities' => ['orders.read', 'orders.write']])
            ->assertSessionHas('new_api_key');
        $plain = session('new_api_key');
        $this->getJson('/api/v1/orders', ['Authorization' => 'Bearer '.$plain])->assertOk();
        $this->get(route('admin.integraciones.index'))->assertSee('Courier')->assertSee($plain); // se muestra una sola vez
        $this->get(route('admin.integraciones.index'))->assertSee('Courier')->assertDontSee($plain);

        $this->delete(route('admin.integraciones.keys.revoke', ApiKey::query()->sole()))->assertSessionHas('status');
        $this->getJson('/api/v1/orders', ['Authorization' => 'Bearer '.$plain])->assertStatus(401);

        $this->post(route('admin.integraciones.webhooks.store'), ['name' => 'ERP', 'url' => 'https://hooks.test/overshark', 'events' => ['order.confirmed', 'order.shipped']])
            ->assertSessionHas('status');
        $webhook = WebhookEndpoint::query()->sole();
        $this->assertStringStartsWith('whsec_', $webhook->secret);
        $this->post(route('admin.integraciones.webhooks.test', $webhook))->assertSessionHas('status', 'Webhook «ERP»: Respondió HTTP 200.');
        Http::assertSent(fn (HttpRequest $request) => $request['event'] === 'ping'
            && $request->header('X-Overshark-Signature')[0] === Webhooks::signature($request->body(), $webhook->secret));

        $this->put(route('admin.integraciones.webhooks.update', $webhook), ['name' => 'ERP', 'url' => 'https://hooks.test/v2', 'events' => ['order.delivered']]);
        $this->assertFalse($webhook->fresh()->enabled);
        $this->assertSame(['order.delivered'], $webhook->fresh()->events);
        $this->delete(route('admin.integraciones.webhooks.destroy', $webhook));
        $this->assertSame(0, WebhookEndpoint::query()->count());
    }

    public function test_crm_and_zazu_settings_from_the_panel_override_the_env_and_can_be_tested(): void
    {
        config(['services.crm.base_url' => 'https://crm-env.test', 'services.crm.api_key' => 'env-key', 'services.crm.send_sales' => false]);
        Http::fake([
            'crm.test/api/v1/sales' => Http::sequence()->push(['message' => 'The given data was invalid.'], 422)->push(['message' => 'Clave API inválida'], 401),
            'zazu.test/*' => Http::response(['data' => [['ter_id' => '1'], ['ter_id' => '2']]]),
        ]);
        $this->admin();

        $this->put(route('admin.integraciones.connections.update', 'crm'), ['settings' => ['base_url' => 'https://crm.test', 'api_key' => 'panel-key'], 'enabled' => 1]);
        $this->assertSame('https://crm.test', config('services.crm.base_url'));
        $this->assertSame('panel-key', config('services.crm.api_key'));
        $this->assertTrue(config('services.crm.send_sales'));

        // Clave vacía: se conserva la guardada.
        $this->put(route('admin.integraciones.connections.update', 'crm'), ['settings' => ['base_url' => 'https://crm.test', 'api_key' => ''], 'enabled' => 1]);
        $this->assertSame('panel-key', IntegrationSetting::for('crm')->value('api_key'));

        $this->post(route('admin.integraciones.connections.test', 'crm'))->assertSessionHas('status', 'Conectado al CRM: la clave API es válida y puede registrar ventas.');
        $this->post(route('admin.integraciones.connections.test', 'crm'))->assertSessionHasErrors('integration');
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://crm.test/api/v1/sales' && $request->header('Authorization')[0] === 'Bearer panel-key');

        $this->put(route('admin.integraciones.connections.update', 'zazu'), ['settings' => ['base_url' => 'https://zazu.test', 'token' => 'tok', 'companies' => 'overshark, bravos, girls']]);
        $this->assertSame(['OVERSHARK', 'BRAVOS', 'GIRLS'], config('services.zazu.companies'));
        $this->assertFalse(Integrations::zazuSyncEnabled()); // guardado sin marcar: se pausa la sincronización
        $this->post(route('admin.integraciones.connections.test', 'zazu'))->assertSessionHas('status', 'Conectado a Zazu: 2 sedes disponibles.');
    }

    public function test_pixels_load_in_the_store_and_report_add_to_cart_and_purchase(): void
    {
        $this->admin();
        $this->put(route('admin.integraciones.connections.update', 'pixels'), ['settings' => ['meta_pixel_id' => '<script>'], 'enabled' => 1])
            ->assertSessionHasErrors('settings.meta_pixel_id');
        $this->put(route('admin.integraciones.connections.update', 'pixels'), ['settings' => ['meta_pixel_id' => '1234567890123456', 'ga4_id' => 'G-ABC123XYZ', 'tiktok_pixel_id' => 'CABC123DEF456GHI'], 'enabled' => 1])
            ->assertSessionHasNoErrors();

        $polo = Producto::query()->create(['name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 3, 'price' => 40, 'zazu_company_id' => 1]);
        $this->post(route('web.cart.store'), ['producto_id' => $polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->get(route('web.cart.index'))->assertOk()
            ->assertSee("fbq('init', \"1234567890123456\")", false)
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false)
            ->assertSee('ttq.load("CABC123DEF456GHI")', false)
            ->assertSee('"AddToCart"', false);
        $this->get(route('web.cart.index'))->assertDontSee('"AddToCart"', false); // el evento se envía una sola vez

        $order = $this->order(Checkout::STATUS_PAID);
        $this->withSession(['checkout.orders' => [$order->id]]);
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('"Purchase"', false)->assertSee($order->displayNumber());
        $this->get(route('web.checkout.done', $order))->assertOk()->assertDontSee('"Purchase"', false);

        // Desactivados: la tienda no carga nada.
        $this->put(route('admin.integraciones.connections.update', 'pixels'), ['settings' => ['meta_pixel_id' => '1234567890123456']]);
        $this->get(route('web.cart.index'))->assertDontSee('fbq(', false);
    }
}
