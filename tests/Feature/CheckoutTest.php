<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Combo;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Models\Admin\Provincia;
use App\Models\User;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Producto $polo;

    private Distrito $miraflores;

    private Distrito $wanchaq;

    private int $shalomWanchaq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 3, 'price' => 40, 'zazu_company_id' => 1,
        ]);
        $lima = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Lima'])->id, 'name' => 'Lima']);
        $cusco = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Cusco'])->id, 'name' => 'Cusco']);
        $this->miraflores = Distrito::query()->create(['provincia_id' => $lima->id, 'name' => 'Miraflores']);
        $this->wanchaq = Distrito::query()->create(['provincia_id' => $cusco->id, 'name' => 'Wanchaq']);

        // Agencias Shalom (tabla courier): una en el distrito de la clienta y otra en la misma provincia.
        DB::table('courier')->insert([
            ['ter_id' => '900', 'lugar_over' => 'CUSCO - AV. LA CULTURA', 'direccion' => 'AV. LA CULTURA 500, CUSCO', 'zona' => 'CUSCO', 'provincia' => 'CUSCO', 'departamento' => 'CUSCO'],
            ['ter_id' => '901', 'lugar_over' => 'MIRAFLORES - AV. PARDO', 'direccion' => 'AV. PARDO 200', 'zona' => 'MIRAFLORES', 'provincia' => 'LIMA', 'departamento' => 'LIMA'],
        ]);
        $this->shalomWanchaq = DB::table('courier')->insertGetId([
            'ter_id' => '902', 'lugar_over' => 'WANCHAQ - AV. SOL', 'direccion' => 'AV. SOL 100, WANCHAQ', 'zona' => 'WANCHAQ', 'provincia' => 'CUSCO', 'departamento' => 'CUSCO',
        ]);

        // Culqi con llaves de prueba y su API simulada: aprueba todo token salvo "tkn_test_declined".
        config(['services.culqi.public_key' => 'pk_test_x', 'services.culqi.secret_key' => 'sk_test_x', 'services.culqi.base_url' => 'https://api.culqi.test/v2']);
        Http::fake(['api.culqi.test/*' => fn ($request) => $request['source_id'] === 'tkn_test_declined'
            ? Http::response(['object' => 'error', 'type' => 'card_error', 'user_message' => 'Tu tarjeta no tiene fondos suficientes.'], 402)
            : Http::response(['object' => 'charge', 'id' => 'chr_test_'.$request['amount'], 'outcome' => ['type' => 'venta_exitosa', 'user_message' => 'Su compra ha sido exitosa.']], 201)]);
    }

    private function addToCart(int $qty = 2): void
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => $qty]);
    }

    private function customer(array $overrides = []): array
    {
        return array_merge([
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ], $overrides);
    }

    public function test_cart_shows_prices_with_taxes_included_and_shipping_only_after_choosing_delivery(): void
    {
        $this->get(route('web.cart.index'))->assertOk()->assertSee('TU CARRITO ESTÁ VACÍO');

        $this->addToCart();

        // En el carrito aún no hay tipo de entrega: el total es el subtotal, sin fila de envío.
        $this->get(route('web.cart.index'))
            ->assertOk()
            ->assertSee('2 prendas para tu próximo look')
            ->assertSee('S/ 80.00')
            ->assertDontSee('S/ 15.00')
            ->assertDontSee('S/ 95.00')
            ->assertDontSee('IGV');

        $this->get(route('web.checkout.personal'))->assertOk()->assertSee('S/ 95.00');
    }

    public function test_details_are_validated_and_home_delivery_is_only_for_lima(): void
    {
        $this->addToCart();

        $this->post(route('web.checkout.personal.store'), $this->customer(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        // DNI de 8 dígitos y CE de 9 a 12 caracteres.
        $this->post(route('web.checkout.personal.store'), $this->customer(['doc_number' => '729865355555']))
            ->assertSessionHasErrors(['doc_number' => 'El DNI tiene 8 dígitos.']);
        $this->post(route('web.checkout.personal.store'), $this->customer(['doc_type' => 'CE', 'doc_number' => '0012345678901']))
            ->assertSessionHasErrors('doc_number');

        $this->post(route('web.checkout.personal.store'), $this->customer(['distrito_id' => $this->wanchaq->id, 'delivery_lat' => -13.5226, 'delivery_lng' => -71.9673]))
            ->assertSessionHasErrors('delivery');

        // La agencia debe ser una agencia Shalom de la lista.
        $this->post(route('web.checkout.personal.store'), $this->customer(['distrito_id' => $this->wanchaq->id, 'delivery' => 'agencia', 'agency' => 'Mi casa']))
            ->assertSessionHasErrors(['agency' => 'Elige una agencia Shalom de la lista.']);

        $this->post(route('web.checkout.personal.store'), $this->customer(['distrito_id' => $this->wanchaq->id, 'delivery' => 'agencia', 'agency' => $this->shalomWanchaq]))
            ->assertRedirect(route('web.checkout.payment'));
        $this->assertSame($this->shalomWanchaq, session('checkout.customer.agency_id'));
        $this->assertSame('Shalom WANCHAQ - AV. SOL — AV. SOL 100, WANCHAQ', session('checkout.customer.agency'));
    }

    public function test_shalom_agencies_of_the_province_are_listed_with_the_customer_district_first(): void
    {
        $agencies = $this->getJson(route('web.checkout.courier-agencies', ['departamento' => 'Cusco', 'provincia' => 'Cusco', 'distrito' => 'Wanchaq']))
            ->assertOk()
            ->json('agencies');

        $this->assertSame(['WANCHAQ - AV. SOL', 'CUSCO - AV. LA CULTURA'], array_column($agencies, 'name'));
        $this->assertSame([true, false], array_column($agencies, 'near'));

        $this->getJson(route('web.checkout.courier-agencies', ['departamento' => 'Cusco', 'provincia' => 'Cusco', 'distrito' => 'Wanchaq', 'q' => 'cultura']))
            ->assertJsonCount(1, 'agencies')
            ->assertJsonPath('agencies.0.address', 'AV. LA CULTURA 500, CUSCO');
    }

    public function test_wallet_payment_needs_the_receipt_and_creates_a_single_pending_order(): void
    {
        Storage::fake('public');
        $yape = MetodoPago::query()->create(['name' => 'Yape', 'numero' => '999888777', 'titular' => 'Overshark', 'status' => true]);
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());

        $this->get(route('web.checkout.payment'))->assertOk()
            ->assertSee('Método de pago')
            ->assertSee('999888777')
            ->assertSee('Av. Larco 123')
            ->assertSee('Envío a domicilio (2 a 4 días hábiles)')
            ->assertSee('Separo S/ 15.00');
        $token = session('checkout.token');

        $this->post(route('web.checkout.place'), ['method' => (string) $yape->id, 'token' => $token, 'terms' => 1, 'expected_total' => '95.00'])
            ->assertSessionHasErrors('receipt');

        $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image('yape.jpg')])->assertOk();

        $response = $this->post(route('web.checkout.place'), ['method' => (string) $yape->id, 'token' => $token, 'terms' => 1, 'expected_total' => '95.00']);
        $order = Order::query()->sole();
        $response->assertRedirect(route('web.checkout.done', $order));

        $this->assertSame('95.00', (string) $order->precio_total);
        $this->assertSame('Yape', $order->metodo_pago_nombre);
        $this->assertNotNull($order->comprobante_pago);
        $this->assertSame(Checkout::STATUS_PENDING, $order->statusOrder->name);
        $this->assertSame([], session('cart.items', []));

        // Doble envío del mismo formulario: no crea otro pedido.
        $this->post(route('web.checkout.place'), ['method' => (string) $yape->id, 'token' => $token, 'terms' => 1, 'expected_total' => '95.00'])
            ->assertRedirect(route('web.checkout.done', $order));
        $this->assertSame(1, Order::query()->count());

        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('ESTAMOS CONFIRMANDO TU PAGO')->assertSee($order->number_zazu);

        // El panel no confirma pagos: lo hace el sistema de pago (aquí, la confirmación automática) y la clienta ve el pedido confirmado.
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.index'))->assertOk()->assertSee($order->number_zazu);
        \App\Services\Orders\OrderPayments::confirm($order);
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('¡TU PEDIDO ESTÁ CONFIRMADO!');
    }

    public function test_cash_on_delivery_order_and_stock_is_checked_again_before_placing(): void
    {
        $this->addToCart(3);
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'));
        $token = session('checkout.token');

        // El stock bajó mientras compraba: se vuelve al carrito sin perderlo.
        $this->polo->update(['stock' => 1]);
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok', 'token' => $token, 'terms' => 1, 'expected_total' => '135.00'])
            ->assertRedirect(route('web.cart.index'))
            ->assertSessionHasErrors('cart');
        $this->assertNotEmpty(session('cart.items'));
        $this->assertSame(0, Order::query()->count());

        $this->polo->update(['stock' => 5]);
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok', 'token' => $token, 'terms' => 1, 'expected_total' => '135.00']);
        $order = Order::query()->sole();
        $this->assertNull($order->comprobante_pago);
        // Contraentrega a domicilio: el separo de S/ 15 se cobra con tarjeta en el checkout; el saldo al recibir.
        $this->assertSame('15.00', (string) $order->separo);
        $this->assertSame('15.00', (string) $order->pago_monto);
        $this->assertSame('chr_test_1500', $order->pago_referencia);
        $this->assertSame(Checkout::STATUS_DEPOSIT_PAID, $order->statusOrder->name);
        $this->assertSame('Pago contraentrega · separo con Tarjeta de crédito o débito', $order->metodo_pago_nombre);
        Http::assertSent(fn ($request) => $request['amount'] === 1500 && $request['source_id'] === 'tkn_test_ok' && $request['currency_code'] === 'PEN');
        $this->get(route('web.checkout.done', $order))->assertOk()
            ->assertSee('¡RECIBIMOS TU PEDIDO!')
            ->assertSee('SEPARO PAGADO')
            ->assertSee('Recibimos tu separo de <strong>S/ 15.00</strong>', false)
            ->assertSee('S/ 120.00');
    }

    public function test_warehouse_pickup_needs_no_address_and_has_no_shipping_cost(): void
    {
        config(['services.storefront.pickup_address' => 'Jr. Gamarra 100, La Victoria']);
        $this->addToCart();

        $this->post(route('web.checkout.personal.store'), $this->customer(['delivery' => 'almacen', 'distrito_id' => null, 'address' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('web.checkout.payment'));

        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('Recojo en almacén')->assertSee('Jr. Gamarra 100, La Victoria')->assertSee('Sin separo')->assertSee('S/ 80.00');
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '80.00']);

        $order = Order::query()->sole();
        $this->assertSame('almacen', $order->entrega);
        $this->assertSame('0.00', (string) $order->envio);
        $this->assertSame('80.00', (string) $order->precio_total);
        $this->assertNull($order->direccion);
        $this->assertSame('0.00', (string) $order->separo); // recojo en almacén: se paga todo al recoger
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('listo para recoger');
    }

    public function test_an_order_page_is_only_visible_to_its_buyer(): void
    {
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'));
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00']);
        $order = Order::query()->sole();

        $this->flushSession();
        $this->get(route('web.checkout.done', $order))->assertNotFound();
    }

    public function test_cash_on_delivery_to_an_agency_pays_a_30_soles_deposit_with_a_yape_capture(): void
    {
        Storage::fake('public');
        $yape = MetodoPago::query()->create(['name' => 'Yape', 'numero' => '999888777', 'titular' => 'Overshark', 'status' => true]);
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer(['distrito_id' => $this->wanchaq->id, 'delivery' => 'agencia', 'agency' => $this->shalomWanchaq]));
        $this->get(route('web.checkout.payment'))->assertOk()
            ->assertSee('Separo S/ 30.00')
            ->assertSee('Paga el importe exacto de tu separo: <strong>S/ 30.00</strong>', false)
            ->assertSee('Shalom WANCHAQ - AV. SOL — AV. SOL 100, WANCHAQ')
            ->assertSee('Wanchaq, Cusco, Cusco');
        $base = ['method' => Checkout::CASH_ON_DELIVERY, 'token' => session('checkout.token'), 'expected_total' => '94.00'];

        // Sin aceptar los términos, sin elegir cómo pagar el separo o sin la captura, no se crea el pedido.
        $this->post(route('web.checkout.place'), $base + ['deposit_method' => (string) $yape->id])->assertSessionHasErrors('terms');
        $this->post(route('web.checkout.place'), $base + ['terms' => 1])->assertSessionHasErrors(['deposit_method' => 'Elige cómo pagarás el separo.']);
        $this->post(route('web.checkout.place'), $base + ['terms' => 1, 'deposit_method' => (string) $yape->id])
            ->assertSessionHasErrors(['receipt' => 'Sube la captura del pago de tu separo para confirmar el pedido.']);
        $this->assertSame(0, Order::query()->count());

        $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image('separo.jpg')])->assertOk();
        $this->post(route('web.checkout.place'), $base + ['terms' => 1, 'deposit_method' => (string) $yape->id]);

        $order = Order::query()->sole();
        $this->assertSame('14.00', (string) $order->envio); // recojo en agencia Shalom: S/ 14
        $this->assertSame('30.00', (string) $order->separo);
        $this->assertSame('30.00', (string) $order->pago_monto);
        $this->assertNotNull($order->comprobante_pago);
        $this->assertSame(Checkout::STATUS_PENDING, $order->statusOrder->name);
        $this->assertSame('Pago contraentrega · separo con Yape', $order->metodo_pago_nombre);
        Http::assertNothingSent();
        $this->get(route('web.checkout.done', $order))->assertSee('Estamos verificando la captura de tu separo');
    }

    public function test_card_payment_charges_the_total_with_culqi_and_a_declined_card_can_retry(): void
    {
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'))->assertOk()
            ->assertSeeInOrder(['Tarjeta de crédito o débito', 'Datos de la tarjeta', 'Número de tarjeta', 'CVV - Código de seguridad', 'Mes de vencimiento', 'Año de vencimiento', 'Nombre impreso en la tarjeta', 'Yape', 'El pago con Yape (Ligo Pay) aún no está activo', 'Tu QR aparecerá aquí', 'Pago contraentrega'])
            ->assertSee('data-culqi-key="pk_test_x"', false)
            ->assertSee('data-culqi-url="https://secure.culqi.com/v2"', false)
            ->assertDontSee('El pago con tarjeta (Culqi) aún no está activo')
            // Los campos de la tarjeta no tienen name: nunca se envían a nuestro servidor.
            ->assertDontSee('name="card_number"', false)
            ->assertDontSee('name="cvv"', false);
        $base = ['method' => Checkout::CARD, 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'];

        // Sin token de Culqi no se cobra.
        $this->post(route('web.checkout.place'), $base)->assertSessionHasErrors(['method' => 'Ingresa los datos de tu tarjeta para continuar.']);

        // Tarjeta rechazada: no queda pedido y puede intentar con otra.
        $this->post(route('web.checkout.place'), $base + ['culqi_token' => 'tkn_test_declined'])
            ->assertRedirect(route('web.checkout.payment'))
            ->assertSessionHasErrors(['method' => 'Tu tarjeta no tiene fondos suficientes.']);
        $this->assertSame(0, Order::query()->count());
        $this->assertNotEmpty(session('cart.items'));

        $this->post(route('web.checkout.place'), $base + ['culqi_token' => 'tkn_test_ok']);
        $order = Order::query()->sole();
        $this->assertSame(Checkout::STATUS_PAID, $order->statusOrder->name);
        $this->assertSame('95.00', (string) $order->pago_monto);
        $this->assertSame('chr_test_9500', $order->pago_referencia);
        $this->assertNull($order->separo);
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('¡TU PEDIDO ESTÁ CONFIRMADO!');
    }

    public function test_yape_shows_the_ligo_pay_qr_design_but_cannot_pay_until_ligo_is_integrated(): void
    {
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'))->assertOk()
            ->assertSee('generamos un QR por <strong>S/ 95.00</strong>', false)
            ->assertSee('generamos un QR por <strong>S/ 15.00</strong>', false) // el separo de contraentrega con Yape
            ->assertSee('Pago procesado por Ligo Pay.');

        $base = ['token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'];
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::YAPE])->assertSessionHasErrors('method');
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::YAPE])->assertSessionHasErrors('deposit_method');
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_without_culqi_keys_the_card_and_yape_forms_show_but_cannot_pay(): void
    {
        config(['services.culqi.public_key' => null, 'services.culqi.secret_key' => null]);
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());

        // Los formularios se ven (para revisar el diseño) con un aviso; la contraentrega igual pide separo.
        $this->get(route('web.checkout.payment'))->assertOk()
            ->assertSeeInOrder(['Tarjeta de crédito o débito', 'El pago con tarjeta (Culqi) aún no está activo', 'Número de tarjeta', 'Yape', 'Tu QR aparecerá aquí', 'Pago contraentrega'])
            ->assertSee('data-culqi-key=""', false)
            ->assertSee('Separo S/ 15.00');

        $base = ['token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'];
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok'])->assertSessionHasErrors('method');
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::YAPE, 'culqi_token' => 'ype_test_ok'])->assertSessionHasErrors('method');
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::CASH_ON_DELIVERY])->assertSessionHasErrors('deposit_method');
        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
    }

    public function test_adding_a_size_that_just_sold_out_shows_a_message_instead_of_a_404(): void
    {
        $this->polo->update(['stock' => 0]);

        $this->from(route('web.products.show', $this->polo))
            ->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1])
            ->assertRedirect(route('web.products.show', $this->polo))
            ->assertSessionHasErrors(['cart' => 'Esa talla se acaba de agotar en este color. Elige otra opción.']);
        $this->assertSame([], session('cart.items', []));
    }

    public function test_cart_drawer_reports_stock_limits_and_shows_only_real_prices(): void
    {
        // Pide 5 y quedan 3: se agregan 3 con aviso.
        $this->from(route('web.products.search'))->followingRedirects()
            ->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 5])
            ->assertSee('Solo quedaban 3 unidades disponibles: agregamos 3 a tu carrito.')
            ->assertSee('S/ 120.00')
            ->assertDontSee('S/ 150.00'); // el antiguo precio tachado inventado (precio / 0.8)

        // Ya están todas en el carrito: el aviso de cantidad máxima se ve en el carrito lateral.
        $this->from(route('web.products.search'))->followingRedirects()
            ->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1])
            ->assertSee('Ya tienes en tu carrito todas las unidades disponibles de esta prenda (3).');

        $this->from(route('web.products.search'))->followingRedirects()
            ->patch(route('web.cart.update', $this->polo->id), ['action' => 'increment'])
            ->assertSee('No hay más unidades disponibles de esa prenda.');
        $this->assertSame(3, session('cart.items')[(string) $this->polo->id]['qty']);
    }

    public function test_a_price_change_is_shown_and_the_new_total_must_be_confirmed(): void
    {
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'))->assertSee('S/ 95.00');
        $token = session('checkout.token');

        // El precio sube mientras paga: no se crea el pedido por un importe que no vio.
        $this->polo->update(['price' => 45]);
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok', 'token' => $token, 'terms' => 1, 'expected_total' => '95.00'])
            ->assertRedirect(route('web.checkout.payment'))
            ->assertSessionHasErrors(['method' => 'El precio de tu pedido cambió. El nuevo total es S/ 105.00. Revisa el resumen y confirma de nuevo.']);
        $this->assertSame(0, Order::query()->count());

        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('S/ 105.00')->assertSee('value="105.00"', false);

        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok', 'token' => $token, 'terms' => 1, 'expected_total' => '105.00']);
        $order = Order::query()->sole();
        $this->assertSame('105.00', (string) $order->precio_total);
        $this->assertSame(45.0, (float) $order->productos_json[0]['precio']);
    }

    public function test_the_cart_shows_when_a_price_changed_since_it_was_added(): void
    {
        $this->addToCart();
        $this->polo->update(['price' => 45]);

        $this->get(route('web.cart.index'))
            ->assertOk()
            ->assertSee('El precio de Clasico cambió de S/ 40.00 a S/ 45.00.')
            ->assertSee('S/ 90.00');
        $this->assertSame(45.0, session('cart.items')[(string) $this->polo->id]['price']);
    }

    public function test_the_same_variant_in_a_product_line_and_a_combo_cannot_exceed_stock(): void
    {
        $combo = Combo::query()->create([
            'name' => '2x70', 'brand' => 'Overshark', 'price' => 70,
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 2]], 'selection_mode' => 'fixed', 'status' => true,
        ]);
        $this->addToCart(2);
        $this->post(route('web.combos.cart.store', $combo), ['selections' => [$this->polo->id, $this->polo->id]])
            ->assertRedirect(route('web.cart.index'));
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'));

        // 2 sueltas + 2 en el combo = 4, pero solo hay 3.
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD, 'culqi_token' => 'tkn_test_ok', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '165.00'])
            ->assertRedirect(route('web.cart.index'))
            ->assertSessionHasErrors(['cart' => 'Pediste 4 unidades de Clasico (NEGRO · M) entre prendas sueltas y combos, pero solo quedan 3. Ajusta tu carrito.']);
        $this->assertSame(0, Order::query()->count());
    }
}
