<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Jobs\RunOrderTask;
use App\Models\Admin\Combo;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Models\Admin\Producto;
use App\Models\Admin\Provincia;
use App\Models\User;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/** El pedido pagado se registra como venta en CRM-LIVEX-V2, que asigna la nota de venta (POST /api/v1/sales). */
class CrmSaleTest extends TestCase
{
    use RefreshDatabase;

    private Producto $polo;

    private Producto $bravos;

    private Distrito $miraflores;

    private Distrito $wanchaq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'OVER_REF0100', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 9, 'price' => 40,
            'zazu_company_id' => 1, 'empresa_nombre' => 'OVERSHARK PERU S.A.C',
        ]);
        $this->bravos = Producto::query()->create([
            'name' => 'POLERA NERU', 'default_code' => 'BRAV_REF0007', 'color' => 'NEGRO', 'talla' => 'L', 'stock' => 9, 'price' => 60,
            'zazu_company_id' => 2, 'empresa_nombre' => 'BRAVOS',
        ]);
        $lima = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Lima'])->id, 'name' => 'Lima']);
        $cusco = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Cusco'])->id, 'name' => 'Cusco']);
        $this->miraflores = Distrito::query()->create(['provincia_id' => $lima->id, 'name' => 'Miraflores']);
        $this->wanchaq = Distrito::query()->create(['provincia_id' => $cusco->id, 'name' => 'Wanchaq']);

        config([
            'services.culqi.public_key' => 'pk_test_x', 'services.culqi.secret_key' => 'sk_test_x', 'services.culqi.base_url' => 'https://api.culqi.test/v2',
            'services.crm.base_url' => 'https://crm.test', 'services.crm.api_key' => 'lvx_clave', 'services.crm.send_sales' => true,
        ]);
        // CRM simulado: asigna la siguiente nota de venta de su serie (ya va en NV-00013) y, si la misma venta
        // (external_reference) llega otra vez, devuelve la que ya registró.
        $sales = [];
        $last = 13;
        Http::fake([
            'api.culqi.test/*' => Http::response(['object' => 'charge', 'id' => 'chr_ok'], 201),
            'crm.test/*' => function ($request) use (&$sales, &$last) {
                $reference = $request['external_reference'];
                $isNew = ! isset($sales[$reference]);
                $sales[$reference] ??= ['id' => ++$last, 'number' => 'NV-'.str_pad((string) $last, 5, '0', STR_PAD_LEFT)];

                return Http::response($sales[$reference] + [
                    'source' => 'web', 'external_reference' => $reference, 'status' => 'registrado', 'status_label' => 'Separo verificado',
                    'payment_status' => 'pagado', 'zazu' => ['number' => $sales[$reference]['number'], 'ticket_id' => 9001],
                ], $isNew ? 201 : 200);
            },
        ]);
    }

    private function buy(array $customer = [], ?callable $fillCart = null, array $place = []): Order
    {
        $fillCart ? $fillCart() : $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), array_merge([
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'reference' => 'Frente al parque', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ], $customer))->assertSessionHasNoErrors();
        $total = $this->get(route('web.checkout.payment'))->viewData('total');
        $this->post(route('web.checkout.place'), array_merge(
            ['method' => Checkout::CARD, 'culqi_token' => 'tkn_ok', 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => number_format($total, 2, '.', '')],
            $place,
        ))->assertSessionHasNoErrors();

        return Order::query()->latest('id')->first();
    }

    private function crmRequests(): array
    {
        return Http::recorded()->filter(fn ($pair) => str_contains($pair[0]->url(), 'crm.test'))->map(fn ($pair) => $pair[0])->values()->all();
    }

    public function test_three_distinct_checkout_sales_are_sent_to_livex(): void
    {
        $home = $this->buy();
        $pickup = $this->buy(
            ['delivery' => 'almacen', 'distrito_id' => null, 'address' => ''],
            place: ['method' => Checkout::CASH_ON_DELIVERY],
        );

        $shalom = DB::table('courier')->insertGetId([
            'ter_id' => '902', 'lugar_over' => 'WANCHAQ - AV. SOL',
            'direccion' => 'AV. SOL 100, WANCHAQ', 'zona' => 'WANCHAQ',
            'provincia' => 'CUSCO', 'departamento' => 'CUSCO',
        ]);
        $agency = $this->buy(
            ['distrito_id' => $this->wanchaq->id, 'delivery' => 'agencia', 'agency' => $shalom],
            place: ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD],
        );

        $requests = $this->crmRequests();
        $this->assertCount(3, $requests);
        $this->assertSame(
            [$home->number_zazu, $pickup->number_zazu, $agency->number_zazu],
            array_map(fn ($request) => $request['external_reference'], $requests),
        );
        $this->assertSame(['delivery', 'recojo', 'agencia'], array_map(fn ($request) => $request['delivery']['type'], $requests));
        $this->assertEquals([95, 0, 30], array_map(fn ($request) => $request['payments'][0]['amount'] ?? 0, $requests));
        $this->assertSame(['NV-00014', 'NV-00015', 'NV-00016'], array_map(fn ($order) => $order->refresh()->nota_venta, [$home, $pickup, $agency]));
        foreach ([$home, $pickup, $agency] as $order) {
            $this->assertSame(OrderTask::DONE, $order->tasks()->where('task', 'crm_venta')->value('status'));
        }
    }

    public function test_a_paid_order_is_registered_in_the_crm_and_keeps_its_sales_note(): void
    {
        $order = $this->buy();

        [$request] = $this->crmRequests();
        $this->assertSame('https://crm.test/api/v1/sales', $request->url());
        $this->assertSame('Bearer lvx_clave', $request->header('Authorization')[0]);
        $this->assertSame('web', $request['source']);
        $this->assertSame($order->number_zazu, $request['external_reference']);
        $this->assertSame(['name' => 'Ana Ríos', 'phone' => '987654321', 'document_type' => 'DNI', 'document' => '12345678'], $request['customer']);
        $this->assertSame(['type' => 'delivery', 'address' => 'Av. Larco 123', 'reference' => 'Frente al parque', 'district' => 'MIRAFLORES', 'province' => 'LIMA', 'department' => 'LIMA'], $request['delivery']);
        $this->assertEquals([['sku' => 'OVER_REF0100', 'quantity' => 2, 'unit_price' => 40, 'cross' => false]], $request['items']);
        $this->assertEquals(15, $request['shipping_cost']);
        $this->assertEquals([['method' => 'Tarjeta', 'amount' => 95, 'reference' => 'chr_ok', 'paid_at' => now()->toDateString()]], $request['payments']);

        $order->refresh();
        $this->assertSame(['NV-00014', 14], [$order->nota_venta, $order->crm_sale_id]);
        $task = $order->tasks()->where('task', 'crm_venta')->first();
        $this->assertSame(OrderTask::DONE, $task->status);
        $this->assertSame('Venta NV-00014 registrada en el CRM (Separo verificado, pago pagado) · en Zazu como NV-00014.', $task->message);

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.show', $order))->assertOk()->assertSee('NV-00014')->assertSee("pedido web {$order->number_zazu}");
    }

    public function test_the_confirmation_page_shows_the_crm_sales_note_right_away(): void
    {
        $order = $this->buy();

        // La venta se registra al confirmar: la clienta ve su nota de venta, no el código interno del pedido.
        $this->get(route('web.checkout.done', $order))->assertOk()
            ->assertSee('Pedido #NV-00014')
            ->assertDontSee("Pedido #{$order->number_zazu}");
    }

    public function test_if_the_crm_rejects_the_sale_at_checkout_the_order_is_confirmed_and_retried_in_the_queue(): void
    {
        Queue::fake();
        config(['services.crm.base_url' => 'https://crm-sin-catalogo.test']);
        Http::fake(['crm-sin-catalogo.test/*' => Http::response(['message' => 'El SKU OVER_REF0100 no existe en el catálogo del CRM'], 422)]);

        $order = $this->buy();

        // El pago ya se cobró: el pedido queda confirmado y la venta en el CRM se reintenta en la cola.
        $this->assertSame(Checkout::STATUS_PAID, $order->statusName());
        $this->assertNull($order->nota_venta);
        $task = $order->tasks()->where('task', 'crm_venta')->first();
        $this->assertSame([OrderTask::PENDING, 1], [$task->status, $task->attempts]);
        $this->assertStringContainsString('no existe en el catálogo del CRM', $task->message);
        Queue::assertPushed(RunOrderTask::class, fn (RunOrderTask $job) => $job->orderId === $order->id && $job->task === 'crm_venta');
        $this->get(route('web.checkout.done', $order))->assertSee("Pedido #{$order->number_zazu}");
    }

    public function test_cash_on_delivery_to_a_shalom_agency_sends_the_agency_and_only_the_deposit_as_paid(): void
    {
        $shalom = DB::table('courier')->insertGetId(['ter_id' => '902', 'lugar_over' => 'WANCHAQ - AV. SOL', 'direccion' => 'AV. SOL 100, WANCHAQ', 'zona' => 'WANCHAQ', 'provincia' => 'CUSCO', 'departamento' => 'CUSCO']);
        $this->buy(
            ['distrito_id' => $this->wanchaq->id, 'delivery' => 'agencia', 'agency' => $shalom],
            place: ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD],
        );

        [$request] = $this->crmRequests();
        $this->assertSame('agencia', $request['delivery']['type']);
        $this->assertSame('902', $request['delivery']['shalom_agency_id']);
        $this->assertSame('WANCHAQ - AV. SOL — AV. SOL 100, WANCHAQ', $request['delivery']['agency_name']);
        $this->assertSame(['WANCHAQ', 'CUSCO', 'CUSCO'], [$request['delivery']['district'], $request['delivery']['province'], $request['delivery']['department']]);
        // Total 80 + 14 de envío: el separo de S/ 30 (envío + separo) va como pago y el saldo queda por cobrar en el CRM.
        $this->assertEquals(14, $request['shipping_cost']);
        $this->assertEquals([['method' => 'Tarjeta', 'amount' => 30, 'reference' => 'chr_ok', 'paid_at' => now()->toDateString()]], $request['payments']);
    }

    public function test_warehouse_pickup_without_deposit_goes_as_recojo_with_nothing_paid(): void
    {
        $this->buy(['delivery' => 'almacen', 'distrito_id' => null, 'address' => ''], place: ['method' => Checkout::CASH_ON_DELIVERY]);

        [$request] = $this->crmRequests();
        $this->assertSame(['type' => 'recojo'], $request['delivery']);
        $this->assertArrayNotHasKey('payments', $request->data());
        $this->assertEquals(0, $request['shipping_cost']);
    }

    public function test_a_validated_plin_capture_uses_the_crm_method_and_the_order_code_as_reference(): void
    {
        Storage::fake('public');
        $plin = MetodoPago::query()->create(['name' => 'Plin', 'numero' => '999888777', 'titular' => 'Overshark', 'status' => true]);
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 1]);
        $this->post(route('web.checkout.personal.store'), [
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ]);
        $this->get(route('web.checkout.payment'));
        $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image('plin.jpg')]);
        $this->post(route('web.checkout.place'), ['method' => (string) $plin->id, 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '55.00']);
        $order = Order::query()->sole();
        $this->assertSame([], $this->crmRequests()); // la captura espera la validación del equipo

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        \App\Services\Orders\OrderPayments::confirm($order); // lo confirma el sistema de pago, no el panel

        [$request] = $this->crmRequests();
        $this->assertEquals([['method' => 'Plin', 'amount' => 55, 'reference' => "Web {$order->number_zazu}", 'paid_at' => now()->toDateString()]], $request['payments']);
        $this->assertSame('NV-00014', $order->refresh()->nota_venta);
    }

    public function test_a_cart_with_overshark_and_bravos_is_one_sale_with_cross_selling_lines_and_split_combos(): void
    {
        $combo = Combo::query()->create([
            'name' => '2x70', 'brand' => 'Overshark', 'price' => 70,
            'items' => [['zazu' => 'CLASICO', 'cantidad' => 2]], 'selection_mode' => 'fixed', 'status' => true,
        ]);
        $this->buy(fillCart: function () use ($combo) {
            $this->post(route('web.combos.cart.store', $combo), ['selections' => [$this->polo->id, $this->polo->id]]);
            $this->post(route('web.cart.store'), ['producto_id' => $this->bravos->id, 'color' => 'NEGRO', 'talla' => 'L', 'qty' => 1]);
        });

        [$request] = $this->crmRequests();
        // Una sola venta: la marca principal es la de mayor importe (Overshark, S/ 70) y Bravos va como Cross-Selling.
        $this->assertEquals([
            ['sku' => 'OVER_REF0100', 'quantity' => 2, 'unit_price' => 35, 'cross' => false],
            ['sku' => 'BRAV_REF0007', 'quantity' => 1, 'unit_price' => 60, 'cross' => true],
        ], $request['items']);
    }

    public function test_crm_errors_are_retried_and_resending_keeps_the_same_sale(): void
    {
        config(['services.crm.send_sales' => false]);
        $order = $this->buy();
        $this->assertSame(OrderTask::SKIPPED, $order->tasks()->where('task', 'crm_venta')->value('status'));
        $this->assertSame([], $this->crmRequests());

        // El CRM rechaza la venta (p. ej. un SKU que no tiene): la tarea guarda su motivo y la cola reintenta.
        config(['services.crm.send_sales' => true, 'services.crm.base_url' => 'https://crm-rechaza.test']);
        Http::fake(['crm-rechaza.test/*' => Http::response(['message' => 'El SKU OVER_REF0100 no existe en el catálogo del CRM'], 422)]);
        $order->tasks()->where('task', 'crm_venta')->update(['status' => OrderTask::PENDING, 'attempts' => 0]);
        try {
            (new RunOrderTask($order->id, 'crm_venta'))->handle();
            $this->fail('Debió lanzar una excepción para que la cola reintente.');
        } catch (RuntimeException $exception) {
            $this->assertSame('El CRM respondió 422: El SKU OVER_REF0100 no existe en el catálogo del CRM', $exception->getMessage());
        }
        $this->assertStringContainsString('no existe en el catálogo del CRM', $order->tasks()->where('task', 'crm_venta')->value('message'));
        $this->assertNull($order->refresh()->nota_venta);

        // Resuelto en el CRM, el reintento la registra; reenviarla otra vez devuelve la misma venta.
        config(['services.crm.base_url' => 'https://crm.test']);
        (new RunOrderTask($order->id, 'crm_venta'))->handle();
        $this->assertSame('NV-00014', $order->refresh()->nota_venta);
        $order->tasks()->where('task', 'crm_venta')->update(['status' => OrderTask::PENDING]);
        (new RunOrderTask($order->id, 'crm_venta'))->handle();
        $this->assertSame('NV-00014', $order->refresh()->nota_venta);
    }
}
