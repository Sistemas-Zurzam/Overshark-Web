<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Jobs\RunOrderTask;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Models\Admin\Producto;
use App\Models\Admin\Provincia;
use App\Models\User;
use App\Services\Orders\OrderFulfillment;
use App\Services\Orders\Tasks\OrderTaskHandler;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/** Modo simulación y lo que pasa después del pago (OrderPayments + OrderFulfillment). */
class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private Producto $polo;

    private Distrito $miraflores;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 5, 'price' => 40, 'zazu_company_id' => 1,
        ]);
        $lima = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Lima'])->id, 'name' => 'Lima']);
        $this->miraflores = Distrito::query()->create(['provincia_id' => $lima->id, 'name' => 'Miraflores']);
        config(['services.culqi.public_key' => null, 'services.culqi.secret_key' => null]);
        Http::fake();
    }

    private function checkout(array $customer = []): array
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), array_merge([
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ], $customer));
        $this->get(route('web.checkout.payment'));

        return ['token' => session('checkout.token'), 'terms' => 1, 'expected_total' => '95.00'];
    }

    private function tasks(Order $order): array
    {
        $tasks = $order->tasks()->pluck('status', 'task')->all();
        ksort($tasks);

        return $tasks;
    }

    private function allTasks(string $status): array
    {
        $tasks = array_fill_keys(array_keys(OrderFulfillment::TASKS), $status);
        ksort($tasks);

        return $tasks;
    }

    public function test_simulation_pays_by_card_and_simulates_sales_note_erp_invoice_and_email(): void
    {
        config(['services.payments.simulate' => true]);
        $base = $this->checkout();

        $this->get(route('web.checkout.payment'))->assertSee('MODO SIMULACIÓN')->assertSee('data-simulate="1"', false);

        // 4000 0000 0000 0002: rechazada, sin pedido y puede reintentar.
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::CARD, 'culqi_token' => 'sim_tkn_declined'])
            ->assertSessionHasErrors('method');
        $this->assertSame(0, Order::query()->count());

        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::CARD, 'culqi_token' => 'sim_tkn_ok']);
        $order = Order::query()->sole();
        $this->assertSame(Checkout::STATUS_PAID, $order->statusName());
        $this->assertStringStartsWith('sim_chr_', $order->pago_referencia);
        Http::assertNothingSent();

        // Sin CRM configurado, la venta en el CRM se simula con su nota de venta.
        $this->assertSame($this->allTasks(OrderTask::DONE), $this->tasks($order));
        $this->assertSame('NV-SIM-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT), $order->refresh()->nota_venta);

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.show', $order))->assertOk()
            ->assertSee($order->nota_venta)->assertSee('Nota de venta del CRM · pedido web '.$order->number_zazu)
            ->assertSeeInOrder(['Después del pago', 'Venta en el CRM (nota de venta, Zazu, ERP y boleta)', '(simulación)']);
    }

    public function test_simulated_yape_qr_waits_for_payment_then_confirms_the_deposit(): void
    {
        config(['services.payments.simulate' => true]);
        $base = $this->checkout();

        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::YAPE]);
        $order = Order::query()->sole();
        $this->assertSame(Checkout::STATUS_AWAITING_QR, $order->statusName());
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $order->pago_qr);
        $this->assertSame('15.00', (string) $order->pago_monto);
        $this->assertSame([], $this->tasks($order)); // nada sigue hasta que se pague

        $this->get(route('web.checkout.done', $order))->assertOk()
            ->assertSee('PAGA CON YAPE')
            ->assertSee('S/ 15.00')
            ->assertSee('SIMULAR PAGO CON YAPE');

        $this->post(route('web.checkout.simulate-qr', $order))->assertRedirect(route('web.checkout.done', $order));
        $order->refresh();
        $this->assertSame(Checkout::STATUS_DEPOSIT_PAID, $order->statusName());
        $this->assertNull($order->pago_qr);
        $this->assertSame(OrderTask::DONE, $this->tasks($order)['crm_venta']);
        $this->get(route('web.checkout.done', $order))->assertSee('SEPARO PAGADO');
    }

    public function test_an_unpaid_qr_expires_and_the_order_is_cancelled(): void
    {
        config(['services.payments.simulate' => true]);
        $base = $this->checkout();
        $this->post(route('web.checkout.place'), $base + ['method' => Checkout::YAPE]);
        $order = Order::query()->sole();

        $this->travel(11)->minutes();
        $this->artisan('pedidos:vencer-qr')->assertSuccessful();

        $this->assertSame(Checkout::STATUS_CANCELLED, $order->refresh()->statusName());
        $this->get(route('web.checkout.done', $order))->assertSee('PEDIDO CANCELADO')->assertSee('el QR de pago venció');
        $this->post(route('web.checkout.simulate-qr', $order));
        $this->assertSame(Checkout::STATUS_CANCELLED, $order->refresh()->statusName());
    }

    public function test_without_simulation_the_simulate_route_does_not_exist_and_pending_tasks_are_marked_as_not_integrated(): void
    {
        Storage::fake('public');
        $yape = MetodoPago::query()->create(['name' => 'Plin', 'numero' => '999888777', 'titular' => 'Overshark', 'status' => true]);
        $base = $this->checkout();

        $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image('plin.jpg')]);
        $this->post(route('web.checkout.place'), $base + ['method' => (string) $yape->id]);
        $order = Order::query()->sole();
        $this->assertSame([], $this->tasks($order)); // captura: espera a que el equipo valide

        $this->post(route('web.checkout.simulate-qr', $order))->assertNotFound();

        // El equipo valida la captura en el admin: arranca la venta en el CRM, aún sin configurar.
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->patch(route('admin.pedidos.status', $order), ['status' => Checkout::STATUS_PAID])->assertRedirect();
        $this->assertSame(['correo' => OrderTask::DONE, 'crm_venta' => OrderTask::SKIPPED], $this->tasks($order));
        $this->assertStringContainsString('CRM_SEND_SALES', $order->tasks()->where('task', 'crm_venta')->value('message'));

        // Volver a confirmar no duplica las tareas.
        $this->patch(route('admin.pedidos.status', $order), ['status' => Checkout::STATUS_PAID]);
        $this->assertSame(2, $order->tasks()->count());
    }

    public function test_a_failing_task_is_recorded_and_can_be_retried_from_the_admin(): void
    {
        config(['services.payments.simulate' => true]);
        $base = $this->checkout(['delivery' => 'almacen', 'distrito_id' => null, 'address' => '']);
        $this->post(route('web.checkout.place'), array_merge($base, ['method' => Checkout::CASH_ON_DELIVERY, 'expected_total' => '80.00']));
        $order = Order::query()->sole();
        // Contraentrega en almacén (sin separo): se procesa en el momento.
        $this->assertSame(OrderTask::DONE, $this->tasks($order)['crm_venta']);

        // Si el CRM no responde, la tarea guarda el error; al agotar los intentos queda "fallido".
        $task = $order->tasks()->where('task', 'crm_venta')->first();
        $task->update(['status' => OrderTask::PENDING]);
        $this->app->bind(\App\Services\Orders\Tasks\RegisterCrmSale::class, fn () => new class implements OrderTaskHandler
        {
            public function handle(Order $order): array
            {
                throw new RuntimeException('El CRM no responde (timeout).');
            }
        });
        $job = new RunOrderTask($order->id, 'crm_venta');
        try {
            $job->handle();
        } catch (RuntimeException) {
            $job->failed(null);
        }
        $task->refresh();
        $this->assertSame(OrderTask::FAILED, $task->status);
        $this->assertSame('El CRM no responde (timeout).', $task->message);

        $this->app->forgetInstance(\App\Services\Orders\Tasks\RegisterCrmSale::class);
        $this->app->offsetUnset(\App\Services\Orders\Tasks\RegisterCrmSale::class);
        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.show', $order))->assertSee('Reintentar');
        $this->post(route('admin.pedidos.tasks.retry', [$order, $task]))->assertRedirect();
        $this->assertSame(OrderTask::DONE, $task->refresh()->status);
    }
}
