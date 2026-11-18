<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\Order;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\Producto;
use App\Models\Admin\Provincia;
use App\Models\User;
use App\Services\Payments\MercadoPagoGateway;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MercadoPagoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'APP_USR-1234567890-111111-abcdef';

    private const SECRET = 'webhook-secret-de-prueba';

    private Producto $polo;

    private Distrito $miraflores;

    /** Pago que devuelve la API simulada de Mercado Pago (GET /v1/payments/{id} y la búsqueda). */
    private array $payment = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 3, 'price' => 40, 'zazu_company_id' => 1,
        ]);
        $lima = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Lima'])->id, 'name' => 'Lima']);
        $this->miraflores = Distrito::query()->create(['provincia_id' => $lima->id, 'name' => 'Miraflores']);

        Http::fake(function (HttpRequest $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                $path === '/checkout/preferences' => Http::response([
                    'id' => 'pref-1', 'init_point' => 'https://www.mercadopago.com.pe/checkout/v1/redirect?pref_id=pref-1',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com.pe/checkout/v1/redirect?pref_id=pref-1',
                ], 201),
                $path === '/v1/payments' => Http::response($this->payment, 201),
                $path === '/v1/payments/search' =>Http::response(['results' => $this->payment ? [$this->payment] : []]),
                $path === '/v1/payments/404' => Http::response(['message' => 'Payment not found'], 404),
                $path === '/v1/payments/500' => Http::response(['message' => 'internal_error'], 500),
                str_starts_with($path, '/v1/payments/') => Http::response($this->payment),
                $path === '/users/me' => $request->header('Authorization')[0] === 'Bearer '.self::TOKEN
                    ? Http::response(['id' => 99, 'nickname' => 'OVERSHARKPE', 'site_id' => 'MPE'])
                    : Http::response(['message' => 'invalid access token'], 401),
                default => Http::response([], 404),
            };
        });
    }

    private function enableMercadoPago(array $credentials = ['access_token' => self::TOKEN, 'webhook_secret' => self::SECRET], string $environment = 'production'): void
    {
        PaymentGateway::query()->where('provider', 'mercadopago')->sole()
            ->update(['enabled' => true, 'environment' => $environment, 'credentials' => $credentials]);
    }

    private function placeOrder(): Order
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), [
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ]);
        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('Mercado Pago');

        $this->post(route('web.checkout.place'), ['method' => 'mercadopago', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'])
            ->assertRedirect('https://www.mercadopago.com.pe/checkout/v1/redirect?pref_id=pref-1');

        return Order::query()->sole();
    }

    private function approvedPayment(Order $order, array $overrides = []): array
    {
        return array_merge([
            'id' => 123456789, 'status' => 'approved', 'status_detail' => 'accredited', 'external_reference' => (string) $order->id,
            'transaction_amount' => 95.0, 'currency_id' => 'PEN', 'live_mode' => true,
        ], $overrides);
    }

    private function webhook(string $paymentId, ?string $secret = self::SECRET, string $type = 'payment')
    {
        $ts = '1742505638683';
        $signature = $secret ? 'ts='.$ts.',v1='.hash_hmac('sha256', "id:{$paymentId};request-id:req-1;ts:{$ts};", $secret) : null;

        return $this->postJson('/webhooks/mercadopago?data.id='.$paymentId.'&type='.$type,
            ['action' => 'payment.updated', 'type' => $type, 'data' => ['id' => $paymentId]],
            array_filter(['x-signature' => $signature, 'x-request-id' => 'req-1']));
    }

    /** Alerta de fraude como la de la simulación de Mercado Pago: sin data.id en la URL, el pago va en data.payment_id. */
    private function fraudAlert(string $paymentId, ?string $secret = self::SECRET)
    {
        $ts = '1742505638683';
        $signature = $secret ? 'ts='.$ts.',v1='.hash_hmac('sha256', "request-id:req-2;ts:{$ts};", $secret) : null;

        return $this->postJson('/webhooks/mercadopago?type='.MercadoPagoGateway::FRAUD_ALERT, [
            'action' => 'Created', 'type' => MercadoPagoGateway::FRAUD_ALERT, 'id' => '123456', 'live_mode' => true,
            'data' => ['description' => 'Posible fraude con tarjeta', 'merchant_order' => 45679012, 'payment_id' => (int) $paymentId, 'site_id' => 'MPE'],
        ], array_filter(['x-signature' => $signature, 'x-request-id' => 'req-2']));
    }

    private function orderStatus(Order $order): ?string
    {
        return $order->fresh('statusOrder')->statusName();
    }

    public function test_mercado_pago_is_offered_only_when_enabled_with_an_access_token(): void
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1]);
        $this->post(route('web.checkout.personal.store'), [
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ]);
        $this->get(route('web.checkout.payment'))->assertOk()->assertDontSee('checkout seguro de Mercado Pago');

        // Sin secreto de webhook igual se ofrece: el pago se verifica consultando la API.
        $this->enableMercadoPago(['access_token' => self::TOKEN]);
        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('checkout seguro de Mercado Pago');
    }

    public function test_placing_an_order_creates_a_preference_for_the_order_and_sends_the_buyer_to_mercado_pago(): void
    {
        $this->enableMercadoPago();
        $order = $this->placeOrder();

        $this->assertSame('mercadopago', $order->payment_gateway);
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $order->statusName());
        $this->assertSame([], session('cart.items', []));
        Http::assertSent(function (HttpRequest $request) use ($order) {
            return $request->url() === MercadoPagoGateway::API.'/checkout/preferences'
                && $request->header('Authorization')[0] === 'Bearer '.self::TOKEN
                && $request['external_reference'] === (string) $order->id
                && $request['items'][0]['unit_price'] == 95.0
                && $request['items'][0]['currency_id'] === 'PEN'
                && $request['payer']['email'] === 'ana@example.com'
                && $request['payer']['identification'] === ['type' => 'DNI', 'number' => '12345678']
                && $request['back_urls']['success'] === route('web.checkout.done', $order)
                && $request['binary_mode'] === true
                && $request['expires'] === true
                && ! isset($request['notification_url']); // en http://localhost Mercado Pago no acepta avisos
        });

        // En el dominio real (https) el aviso y el retorno automático van en cada preferencia.
        URL::forceRootUrl('https://overshark.com.pe');
        URL::forceScheme('https');
        $preference = app(MercadoPagoGateway::class)->preference($order);
        $this->assertSame('https://overshark.com.pe/webhooks/mercadopago?source_news=webhooks', $preference['notification_url']);
        $this->assertSame('approved', $preference['auto_return']);
    }

    public function test_in_test_mode_the_buyer_goes_to_the_sandbox_checkout(): void
    {
        $this->enableMercadoPago(environment: 'test');
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), [
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ]);
        $this->get(route('web.checkout.payment'));

        $this->post(route('web.checkout.place'), ['method' => 'mercadopago', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'])
            ->assertRedirect('https://sandbox.mercadopago.com.pe/checkout/v1/redirect?pref_id=pref-1');
    }

    public function test_only_a_signed_webhook_for_an_approved_payment_of_the_exact_amount_confirms_the_order(): void
    {
        $this->enableMercadoPago();
        $order = $this->placeOrder();
        $this->flushSession(); // el aviso lo envía el servidor de Mercado Pago, no el navegador

        // Firma ausente o con otro secreto: rechazado sin tocar el pedido.
        $this->webhook('123456789', null)->assertStatus(401);
        $this->webhook('123456789', 'otro-secreto')->assertStatus(401);

        // Firmado, pero el pago no coincide: se recibe y el pedido sigue esperando.
        $this->payment = $this->approvedPayment($order, ['transaction_amount' => 1.0]);
        $this->webhook('123456789')->assertOk();
        $this->payment = $this->approvedPayment($order, ['live_mode' => false]);
        $this->webhook('123456789')->assertOk();
        $this->payment = $this->approvedPayment($order, ['status' => 'rejected']);
        $this->webhook('123456789')->assertOk();
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $this->orderStatus($order));

        // Otros eventos, pagos de otra cuenta (404) y la API caída (503, para que Mercado Pago reintente).
        $this->webhook('123456789', type: 'merchant_order')->assertOk();
        $this->webhook('404')->assertOk();
        $this->webhook('500')->assertStatus(503);

        $this->payment = $this->approvedPayment($order);
        $this->webhook('123456789')->assertOk();
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));
        $this->assertSame('123456789', $order->fresh()->pago_referencia);

        // Reintento del mismo aviso: no vuelve a procesar el pedido.
        $this->webhook('123456789')->assertOk();
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));
    }

    public function test_without_webhook_secret_the_payment_is_still_checked_in_the_api(): void
    {
        $this->enableMercadoPago(['access_token' => self::TOKEN]);
        $order = $this->placeOrder();

        $this->payment = $this->approvedPayment($order, ['external_reference' => '999']);
        $this->webhook('123456789', null)->assertOk();
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $this->orderStatus($order));

        $this->payment = $this->approvedPayment($order);
        $this->webhook('123456789', null)->assertOk();
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));
    }

    public function test_returning_from_mercado_pago_confirms_the_order_by_asking_the_api(): void
    {
        $this->enableMercadoPago();
        $order = $this->placeOrder();

        // La URL dice "approved" pero la API no: no se confirma.
        $this->payment = $this->approvedPayment($order, ['status' => 'rejected']);
        $this->get(route('web.checkout.done', $order).'?payment_id=123456789&status=approved&external_reference='.$order->id)
            ->assertRedirect(route('web.checkout.done', $order));
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $this->orderStatus($order));
        $this->get(route('web.checkout.done', $order))->assertOk()
            ->assertSee('PAGAR CON MERCADO PAGO')
            ->assertSee('Aún no vemos tu pago aprobado');

        $this->payment = $this->approvedPayment($order);
        $this->get(route('web.checkout.done', $order).'?payment_id=123456789&status=approved')
            ->assertRedirect(route('web.checkout.done', $order));
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('¡TU PEDIDO ESTÁ CONFIRMADO!');
    }

    public function test_a_rejected_payment_can_be_retried_and_already_paid_finds_the_payment(): void
    {
        $this->enableMercadoPago();
        $order = $this->placeOrder();

        $this->get(route('web.checkout.done', $order).'?payment_id=null&status=null')->assertRedirect(route('web.checkout.done', $order));
        $this->get(route('web.checkout.done', $order))->assertSee('El pago no se completó en Mercado Pago');

        // Reintentar abre otra preferencia del mismo pedido.
        $this->get(route('web.checkout.pay', $order))->assertRedirect('https://www.mercadopago.com.pe/checkout/v1/redirect?pref_id=pref-1');
        $this->assertCount(2, Http::recorded(fn (HttpRequest $request) => str_ends_with($request->url(), '/checkout/preferences')));

        // "Ya pagué": busca el pago aprobado del pedido por external_reference.
        $this->payment = $this->approvedPayment($order);
        $this->get(route('web.checkout.done', [$order, 'verificar' => 1]))->assertRedirect(route('web.checkout.done', $order));
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));
    }

    public function test_a_signed_fraud_alert_flags_the_order_in_the_admin_until_it_is_reviewed(): void
    {
        $this->enableMercadoPago();
        $order = $this->placeOrder();
        $this->payment = $this->approvedPayment($order);
        $this->webhook('123456789')->assertOk();
        $this->flushSession();

        // Sin firma o de un pago ajeno (la simulación de Mercado Pago): se recibe sin marcar nada.
        $this->fraudAlert('123456789', null)->assertStatus(401);
        $this->fraudAlert('123456789', 'otro-secreto')->assertStatus(401);
        $this->fraudAlert('404')->assertOk();
        $this->assertNull($order->fresh()->fraud_alert_at);

        $this->fraudAlert('123456789')->assertOk();
        $order->refresh();
        $this->assertTrue($order->hasOpenFraudAlert());
        $this->assertSame('Pago 123456789: Posible fraude con tarjeta', $order->fraud_alert_detail);
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order)); // el estado no cambia: decide el equipo

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.index'))->assertOk()
            ->assertSee('con alerta de fraude. No los despaches hasta revisarlos.', false)
            ->assertSee('Alerta de fraude')
            ->assertSee('1 con alerta de fraude');
        $this->get(route('admin.pedidos.show', $order))->assertOk()
            ->assertSee('No despaches este pedido')
            ->assertSee('Posible fraude con tarjeta');

        $this->patch(route('admin.pedidos.fraud-alert.review', $order))->assertSessionHas('status');
        $this->assertFalse($order->fresh()->hasOpenFraudAlert());
        $this->get(route('admin.pedidos.index'))->assertOk()->assertDontSee('No los despaches hasta revisarlos');
        $this->get(route('admin.pedidos.show', $order))->assertOk()->assertDontSee('No despaches este pedido')->assertSee('revisada el');
    }

    public function test_a_fraud_alert_before_the_payment_is_confirmed_finds_the_order_through_the_api(): void
    {
        $this->enableMercadoPago();
        $order = $this->placeOrder();
        $this->payment = $this->approvedPayment($order, ['id' => 555]);

        $this->fraudAlert('555')->assertOk();
        $this->assertTrue($order->fresh()->hasOpenFraudAlert());
    }

    public function test_with_a_public_key_the_buyer_pays_with_the_card_form_inside_the_store(): void
    {
        $this->enableMercadoPago(['access_token' => self::TOKEN, 'public_key' => 'APP_USR-public-key-123']);
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), [
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ]);
        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('formulario seguro de Mercado Pago');

        $this->post(route('web.checkout.place'), ['method' => 'mercadopago', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'])
            ->assertRedirect(route('web.checkout.pay', Order::query()->sole()));
        $order = Order::query()->sole();
        $this->assertSame('mercadopago', $order->payment_gateway);
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $order->statusName());
        Http::assertNotSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/checkout/preferences'));

        $this->get(route('web.checkout.pay', $order))->assertOk()
            ->assertSee('sdk.mercadopago.com/js/v2', false)
            ->assertSee('APP_USR-public-key-123')
            ->assertSee('mp-card-brick');

        $card = [
            'token' => 'card-token-1', 'payment_method_id' => 'visa', 'issuer_id' => '1001', 'installments' => 1,
            // El importe que mande el navegador no cuenta: se cobra el del pedido.
            'transaction_amount' => 1,
            'payer' => ['email' => 'ana@example.com', 'identification' => ['type' => 'DNI', 'number' => '12345678']],
        ];

        // Rechazado: mensaje para probar otra tarjeta y el pedido sigue esperando.
        $this->payment = $this->approvedPayment($order, ['status' => 'rejected', 'status_detail' => 'cc_rejected_insufficient_amount']);
        $this->postJson(route('web.checkout.mercadopago.charge', $order), $card)
            ->assertStatus(422)->assertJson(['message' => 'Tu tarjeta no tiene fondos suficientes. Prueba con otra tarjeta.']);
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $this->orderStatus($order));

        $this->payment = $this->approvedPayment($order);
        $this->postJson(route('web.checkout.mercadopago.charge', $order), ['token' => 'card-token-2'] + $card)
            ->assertOk()->assertJson(['redirect' => route('web.checkout.done', $order)]);
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));
        $this->assertSame('123456789', $order->fresh()->pago_referencia);

        Http::assertSent(function (HttpRequest $request) use ($order) {
            return $request->url() === MercadoPagoGateway::API.'/v1/payments'
                && $request->method() === 'POST'
                && $request->header('Authorization')[0] === 'Bearer '.self::TOKEN
                && $request->header('X-Idempotency-Key')[0] === 'os-'.$order->id.'-'.sha1('card-token-2')
                && $request['token'] === 'card-token-2'
                && $request['transaction_amount'] == 95.0
                && $request['external_reference'] === (string) $order->id
                && $request['issuer_id'] === 1001
                && $request['binary_mode'] === true
                && $request['payer']['identification'] === ['type' => 'DNI', 'number' => '12345678'];
        });

        // Ya pagado: no se vuelve a cobrar.
        $this->postJson(route('web.checkout.mercadopago.charge', $order), ['token' => 'card-token-3'] + $card)
            ->assertOk()->assertJson(['redirect' => route('web.checkout.done', $order)]);
        $this->assertCount(2, Http::recorded(fn (HttpRequest $request) => $request->url() === MercadoPagoGateway::API.'/v1/payments'));
    }

    public function test_the_buyer_can_pay_with_yape_in_the_same_page(): void
    {
        $this->enableMercadoPago(['access_token' => self::TOKEN, 'public_key' => 'APP_USR-public-key-123']);
        $order = $this->placeOrderWithCardForm();
        $this->get(route('web.checkout.pay', $order))->assertOk()->assertSee('data-mp-yape-form', false)->assertSee('CON YAPE');

        // Aunque el navegador mande otro medio o cuotas, Yape se cobra como "yape" en una cuota.
        $yape = ['method' => 'yape', 'token' => 'yape-token-1', 'payment_method_id' => 'visa', 'installments' => 6, 'payer' => ['email' => 'ana@example.com']];

        $this->payment = $this->approvedPayment($order, ['status' => 'rejected', 'status_detail' => 'cc_rejected_insufficient_amount', 'payment_method_id' => 'yape']);
        $this->postJson(route('web.checkout.mercadopago.charge', $order), $yape)
            ->assertStatus(422)->assertJsonFragment(['message' => 'No tienes saldo suficiente en Yape o el monto supera tu límite por pago. Revisa tu app o paga con tarjeta.']);

        $this->payment = $this->approvedPayment($order, ['payment_method_id' => 'yape', 'payment_type_id' => 'debit_card']);
        $this->postJson(route('web.checkout.mercadopago.charge', $order), ['token' => 'yape-token-2'] + $yape)
            ->assertOk()->assertJson(['redirect' => route('web.checkout.done', $order)]);
        $this->assertSame(Checkout::STATUS_PAID, $this->orderStatus($order));

        Http::assertSent(fn (HttpRequest $request) => $request->url() === MercadoPagoGateway::API.'/v1/payments'
            && $request['token'] === 'yape-token-2'
            && $request['payment_method_id'] === 'yape'
            && $request['installments'] === 1
            && ! isset($request['issuer_id'])
            && $request['transaction_amount'] == 95.0
            && $request['payer']['email'] === 'ana@example.com');
    }

    public function test_only_the_buyer_session_can_charge_the_card_form(): void
    {
        $this->enableMercadoPago(['access_token' => self::TOKEN, 'public_key' => 'APP_USR-public-key-123']);
        $order = $this->placeOrderWithCardForm();
        $this->flushSession();

        $this->postJson(route('web.checkout.mercadopago.charge', $order), ['token' => 't', 'payment_method_id' => 'visa', 'installments' => 1])
            ->assertNotFound();
    }

    private function placeOrderWithCardForm(): Order
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), [
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ]);
        $this->get(route('web.checkout.payment'));
        $this->post(route('web.checkout.place'), ['method' => 'mercadopago', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00']);

        return Order::query()->sole();
    }

    public function test_admin_rejects_a_test_public_key_in_production(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $gateway = PaymentGateway::query()->where('provider', 'mercadopago')->sole();

        $this->from(route('admin.pasarelas-pago.index'))->put(route('admin.pasarelas-pago.update', $gateway), [
            'environment' => 'production', 'enabled' => 1, 'credentials' => ['access_token' => self::TOKEN, 'public_key' => 'TEST-abc'],
        ])->assertSessionHasErrors('credentials');
        $this->assertFalse($gateway->fresh()->enabled);
    }

    public function test_admin_checks_the_access_token_before_enabling_mercado_pago(): void
    {
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $gateway = PaymentGateway::query()->where('provider', 'mercadopago')->sole();
        $url = route('admin.pasarelas-pago.update', $gateway);

        $this->get(route('admin.pasarelas-pago.index'))->assertOk()->assertSee('Falta: Access Token');

        // Token de prueba en producción y token rechazado: no se activa.
        $this->from(route('admin.pasarelas-pago.index'))->put($url, ['environment' => 'production', 'enabled' => 1, 'credentials' => ['access_token' => 'TEST-123']])
            ->assertSessionHasErrors('credentials');
        $this->put($url, ['environment' => 'production', 'enabled' => 1, 'credentials' => ['access_token' => 'APP_USR-malo']])
            ->assertSessionHasErrors('credentials');
        $this->assertFalse($gateway->fresh()->enabled);

        $this->put($url, ['environment' => 'production', 'enabled' => 1, 'credentials' => ['access_token' => ' '.self::TOKEN.' ', 'webhook_secret' => self::SECRET]])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'OVERSHARKPE'));
        $this->assertTrue($gateway->fresh()->enabled);
        $this->assertSame(self::TOKEN, $gateway->fresh()->credential('access_token'));
        $this->assertTrue($gateway->fresh()->isAvailable());

        $this->post(route('admin.pasarelas-pago.test', $gateway))->assertSessionHas('status', fn ($status) => str_contains($status, 'OVERSHARKPE'));
        $this->get(route('admin.pasarelas-pago.index'))->assertOk()->assertSee('Visible en tienda')->assertSee('Probar conexión')->assertDontSee(self::TOKEN);
    }
}
