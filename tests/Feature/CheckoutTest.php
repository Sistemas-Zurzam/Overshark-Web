<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Producto $polo;

    private Distrito $miraflores;

    private Distrito $wanchaq;

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
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'invoice' => 'boleta',
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
            ->assertDontSee('S/ 14.00')
            ->assertDontSee('S/ 94.00')
            ->assertDontSee('IGV');

        $this->get(route('web.checkout.personal'))->assertOk()->assertSee('S/ 94.00');
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

        $this->post(route('web.checkout.personal.store'), $this->customer(['distrito_id' => $this->wanchaq->id]))
            ->assertSessionHasErrors('delivery');

        $this->post(route('web.checkout.personal.store'), $this->customer(['distrito_id' => $this->wanchaq->id, 'delivery' => 'agencia', 'agency' => 'Agencia Cusco — Av. Sol 100']))
            ->assertRedirect(route('web.checkout.payment'));
    }

    public function test_wallet_payment_needs_the_receipt_and_creates_a_single_pending_order(): void
    {
        Storage::fake('public');
        $yape = MetodoPago::query()->create(['name' => 'Yape', 'numero' => '999888777', 'titular' => 'Overshark', 'status' => true]);
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());

        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('ELIGE CÓMO PAGAR')->assertSee('999888777');
        $token = session('checkout.token');

        $this->post(route('web.checkout.place'), ['method' => (string) $yape->id, 'token' => $token, 'terms' => 1])
            ->assertSessionHasErrors('receipt');

        $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image('yape.jpg')])->assertOk();

        $response = $this->post(route('web.checkout.place'), ['method' => (string) $yape->id, 'token' => $token, 'terms' => 1]);
        $order = Order::query()->sole();
        $response->assertRedirect(route('web.checkout.done', $order));

        $this->assertSame('94.00', (string) $order->precio_total);
        $this->assertSame('Yape', $order->metodo_pago_nombre);
        $this->assertNotNull($order->comprobante_pago);
        $this->assertSame(Checkout::STATUS_PENDING, $order->statusOrder->name);
        $this->assertSame([], session('cart.items', []));

        // Doble envío del mismo formulario: no crea otro pedido.
        $this->post(route('web.checkout.place'), ['method' => (string) $yape->id, 'token' => $token, 'terms' => 1])
            ->assertRedirect(route('web.checkout.done', $order));
        $this->assertSame(1, Order::query()->count());

        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('ESTAMOS CONFIRMANDO TU PAGO')->assertSee($order->number_zazu);

        // El admin confirma el pago y la clienta ve el pedido confirmado.
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.index'))->assertOk()->assertSee($order->number_zazu);
        $this->patch(route('admin.pedidos.status', $order), ['status' => Checkout::STATUS_PAID])->assertRedirect();
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
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'token' => $token, 'terms' => 1])
            ->assertRedirect(route('web.cart.index'))
            ->assertSessionHasErrors('cart');
        $this->assertNotEmpty(session('cart.items'));
        $this->assertSame(0, Order::query()->count());

        $this->polo->update(['stock' => 5]);
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'token' => $token, 'terms' => 1]);
        $order = Order::query()->sole();
        $this->assertNull($order->comprobante_pago);
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('¡RECIBIMOS TU PEDIDO!');
    }

    public function test_warehouse_pickup_needs_no_address_and_has_no_shipping_cost(): void
    {
        config(['services.storefront.pickup_address' => 'Jr. Gamarra 100, La Victoria']);
        $this->addToCart();

        $this->post(route('web.checkout.personal.store'), $this->customer(['delivery' => 'almacen', 'distrito_id' => null, 'address' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('web.checkout.payment'));

        $this->get(route('web.checkout.payment'))->assertOk()->assertSee('Recojo en almacén (gratis): Jr. Gamarra 100, La Victoria')->assertSee('S/ 80.00');
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'token' => session('checkout.token'), 'terms' => 1]);

        $order = Order::query()->sole();
        $this->assertSame('almacen', $order->entrega);
        $this->assertSame('0.00', (string) $order->envio);
        $this->assertSame('80.00', (string) $order->precio_total);
        $this->assertNull($order->direccion);
        $this->get(route('web.checkout.done', $order))->assertOk()->assertSee('listo para recoger');
    }

    public function test_an_order_page_is_only_visible_to_its_buyer(): void
    {
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customer());
        $this->get(route('web.checkout.payment'));
        $this->post(route('web.checkout.place'), ['method' => Checkout::CASH_ON_DELIVERY, 'token' => session('checkout.token'), 'terms' => 1]);
        $order = Order::query()->sole();

        $this->flushSession();
        $this->get(route('web.checkout.done', $order))->assertNotFound();
    }
}
