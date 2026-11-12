<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Mail\OrderPaidMail;
use App\Models\Admin\Departamento;
use App\Models\Admin\Distrito;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Models\Admin\Producto;
use App\Models\Admin\Provincia;
use App\Models\User;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\PaysWithIzipay;
use Tests\TestCase;

/** Correo de confirmación del pedido (App\Mail\OrderPaidMail), enviado como tarea posterior al pago. */
class OrderEmailTest extends TestCase
{
    use PaysWithIzipay;
    use RefreshDatabase;

    private Producto $polo;

    private Distrito $miraflores;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polo = Producto::query()->create([
            'name' => 'CLASICO', 'default_code' => 'CL-N-M', 'color' => 'NEGRO', 'talla' => 'M', 'stock' => 9, 'price' => 40, 'zazu_company_id' => 1,
        ]);
        $lima = Provincia::query()->create(['departamento_id' => Departamento::query()->create(['name' => 'Lima'])->id, 'name' => 'Lima']);
        $this->miraflores = Distrito::query()->create(['provincia_id' => $lima->id, 'name' => 'Miraflores']);
        config(['services.payments.simulate' => true]);
        Http::fake();
        Mail::fake();
    }

    private function buy(array $customer = [], array $place = []): Order
    {
        $this->post(route('web.cart.store'), ['producto_id' => $this->polo->id, 'color' => 'NEGRO', 'talla' => 'M', 'qty' => 2]);
        $this->post(route('web.checkout.personal.store'), array_merge([
            'email' => 'ana@example.com', 'nombres' => 'Ana', 'apellidos' => 'Ríos', 'phone' => '987654321',
            'doc_type' => 'DNI', 'doc_number' => '12345678', 'distrito_id' => $this->miraflores->id,
            'address' => 'Av. Larco 123', 'delivery' => 'domicilio', 'delivery_lat' => -12.1211, 'delivery_lng' => -77.0297, 'invoice' => 'boleta',
        ], $customer));
        $total = $this->get(route('web.checkout.payment'))->viewData('total');
        $this->post(route('web.checkout.place'), array_merge(
            ['method' => Checkout::CARD, 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => number_format($total, 2, '.', '')],
            $place,
        ))->assertSessionHasNoErrors();

        $order = Order::query()->latest('id')->first();

        // Con tarjeta el pedido espera el pago: la clienta paga en el formulario de Izipay (resultado firmado).
        if (($place['method'] ?? Checkout::CARD) === Checkout::CARD || ($place['deposit_method'] ?? null) === Checkout::CARD) {
            $this->payWithIzipay($order);
        }

        return $order->fresh();
    }

    public function test_a_paid_order_sends_the_confirmation_email_with_its_sales_note_items_and_totals(): void
    {
        $order = $this->buy();

        Mail::assertSent(OrderPaidMail::class, function (OrderPaidMail $mail) use ($order) {
            return $mail->hasTo('ana@example.com')
                && $mail->envelope()->subject === "Pedido {$order->refresh()->nota_venta} confirmado | Overshark";
        });
        Mail::assertSentCount(1);

        $mail = new OrderPaidMail($order->refresh());
        $mail->assertSeeInHtml($order->nota_venta);
        $mail->assertSeeInHtml('¡Tu pago fue aprobado!');
        $mail->assertSeeInHtml('Clasico');
        $mail->assertSeeInHtml('Talla M');
        $mail->assertSeeInHtml('S/ 80.00');   // 2 × S/ 40
        $mail->assertSeeInHtml('S/ 95.00');   // total con envío
        $mail->assertSeeInHtml('Pagado en línea');
        $mail->assertSeeInHtml('Av. Larco 123');
        $mail->assertDontSeeInHtml('Saldo por pagar');

        $task = $order->tasks()->where('task', 'correo')->first();
        $this->assertSame(OrderTask::DONE, $task->status);
    }

    public function test_cash_on_delivery_with_a_card_deposit_shows_the_deposit_and_the_balance(): void
    {
        $order = $this->buy(place: ['method' => Checkout::CASH_ON_DELIVERY, 'deposit_method' => Checkout::CARD]);

        $this->assertSame(Checkout::STATUS_DEPOSIT_PAID, $order->statusName());
        $mail = new OrderPaidMail($order->refresh());
        $mail->assertSeeInHtml('¡Recibimos tu separo!');
        $mail->assertSeeInHtml('Pagado en línea');
        $mail->assertSeeInHtml('S/ 15.00');           // separo a domicilio
        $mail->assertSeeInHtml('Saldo por pagar al recibir');
        $mail->assertSeeInHtml('S/ 80.00');           // 95 − 15
        Mail::assertSentCount(1);
    }

    public function test_warehouse_pickup_without_payment_sends_a_received_email(): void
    {
        $order = $this->buy(['delivery' => 'almacen', 'distrito_id' => null, 'address' => ''], ['method' => Checkout::CASH_ON_DELIVERY]);

        $mail = new OrderPaidMail($order->refresh());
        $mail->assertSeeInHtml('¡Recibimos tu pedido!');
        $mail->assertSeeInHtml('Recojo en almacén');
        $mail->assertSeeInHtml('listo para recoger');
        $mail->assertDontSeeInHtml('Pagado en línea');
        Mail::assertSentCount(1);
    }

    public function test_the_email_goes_through_the_queue_and_checkout_does_not_wait_for_it(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $order = $this->buy();

        // La venta en el CRM ya corrió en el momento; el correo quedó en la cola.
        $this->assertSame(OrderTask::DONE, $order->tasks()->where('task', 'crm_venta')->value('status'));
        $this->assertSame(OrderTask::PENDING, $order->tasks()->where('task', 'correo')->value('status'));
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\RunOrderTask::class, fn ($job) => $job->task === 'correo');
        Mail::assertNothingSent();
    }

    public function test_a_failing_mail_server_is_recorded_and_the_admin_can_preview_and_resend(): void
    {
        $order = $this->buy();
        Mail::assertSentCount(1);

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.pedidos.show', $order))->assertOk()->assertSee('Ver correo')->assertSee('Reenviar al cliente');
        $this->get(route('admin.pedidos.email.preview', $order))->assertOk()->assertSee('¡Tu pago fue aprobado!', false);

        $this->post(route('admin.pedidos.email.resend', $order))->assertRedirect();
        Mail::assertSentCount(2); // cola sync en pruebas: el reenvío sale de inmediato
        $this->assertSame(OrderTask::DONE, $order->tasks()->where('task', 'correo')->value('status'));
    }

    public function test_an_order_without_email_skips_the_task(): void
    {
        $order = $this->buy();
        Mail::fake();
        $order->update(['email' => null]);
        $order->tasks()->where('task', 'correo')->update(['status' => OrderTask::PENDING]);

        (new \App\Jobs\RunOrderTask($order->id, 'correo'))->handle();

        $this->assertSame(OrderTask::SKIPPED, $order->tasks()->where('task', 'correo')->value('status'));
        Mail::assertNothingSent();
    }
}
