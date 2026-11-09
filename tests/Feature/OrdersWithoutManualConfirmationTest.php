<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Cliente;
use App\Models\Admin\Order;
use App\Models\Admin\StatusOrder;
use App\Models\User;
use App\Services\Orders\OrderPayments;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pedidos del panel: el pago lo confirman Culqi y Ligo Pay; aquí solo se prepara, envía, entrega o anula. */
class OrdersWithoutManualConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function order(string $status, string $number, array $extra = []): Order
    {
        $cliente = Cliente::query()->firstOrCreate(['email' => 'ana@example.com'], ['name' => 'Ana']);

        return Order::query()->create($extra + [
            'number_zazu' => $number, 'precio_total' => 50, 'cliente_id' => $cliente->id, 'cliente_name' => 'Ana', 'email' => 'ana@example.com',
            'status_order_id' => StatusOrder::query()->firstOrCreate(['name' => $status])->id,
        ]);
    }

    public function test_there_is_no_confirm_payment_tab_or_button_and_unpaid_orders_are_listed_as_pending(): void
    {
        $pending = $this->order(Checkout::STATUS_PENDING, 'OS-PENDIENTE');
        $this->order(Checkout::STATUS_AWAITING_QR, 'OS-QR');
        $this->order(Checkout::STATUS_PAID, 'OS-PAGADO');

        $page = $this->admin()->get(route('admin.pedidos.index'))->assertOk();

        $page->assertDontSee('Por confirmar pago')->assertDontSee('>Confirmar<', false)
            ->assertSee('En preparación')->assertSee('Enviado')->assertSee('Entregado')->assertSee('Anulado')
            // Los sin pagar siguen a la vista en «Todos», con su estado.
            ->assertSee('OS-PENDIENTE')->assertSee('OS-QR')->assertSee('Pendiente de pago');

        // La pestaña que ya no existe no filtra nada.
        $this->admin()->get(route('admin.pedidos.index', ['tab' => 'por_confirmar']))->assertSee('OS-PENDIENTE')->assertSee('OS-PAGADO');
        $this->admin()->get(route('admin.pedidos.index', ['tab' => 'preparacion']))->assertSee('OS-PAGADO')->assertDontSee('OS-PENDIENTE');
        $this->assertNotNull($pending);
    }

    public function test_the_panel_can_no_longer_confirm_a_payment_by_hand(): void
    {
        $order = $this->order(Checkout::STATUS_PENDING, 'OS-1');

        foreach ([Checkout::STATUS_PAID, Checkout::STATUS_DEPOSIT_PAID, Checkout::STATUS_COD, Checkout::STATUS_PENDING] as $status) {
            $this->admin()->patch(route('admin.pedidos.status', $order), ['status' => $status])->assertSessionHasErrors('status');
        }

        $this->assertSame(Checkout::STATUS_PENDING, $order->fresh()->statusName());
        $this->assertSame(0, $order->tasks()->count(), 'no arranca la venta en el CRM desde el panel');
    }

    public function test_the_team_can_ship_deliver_or_cancel_an_order(): void
    {
        $order = $this->order(Checkout::STATUS_PAID, 'OS-2');

        foreach (['Enviado', 'Entregado', Checkout::STATUS_CANCELLED] as $status) {
            $this->admin()->patch(route('admin.pedidos.status', $order), ['status' => $status])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($status, $order->fresh()->statusName());
        }
    }

    public function test_the_order_page_only_offers_the_states_the_team_can_set_and_shows_the_automatic_one(): void
    {
        $order = $this->order(Checkout::STATUS_PAID, 'OS-3');

        $html = $this->admin()->get(route('admin.pedidos.show', $order))->assertOk()->getContent();

        $this->assertStringContainsString('Pago confirmado (automático)', $html);
        $this->assertStringContainsString('<option value="Enviado"', $html);
        $this->assertStringContainsString('<option value="Entregado"', $html);
        $this->assertStringNotContainsString('<option value="'.Checkout::STATUS_PAID.'"', $html);
        $this->assertStringNotContainsString('<option value="'.Checkout::STATUS_PENDING.'"', $html);
    }

    public function test_the_payment_confirmed_by_the_system_moves_the_order_to_preparation_and_starts_the_crm_sale(): void
    {
        $order = $this->order(Checkout::STATUS_PENDING, 'OS-4', ['entrega' => 'domicilio']);

        OrderPayments::confirm($order, 'chr_123');

        $this->assertSame(Checkout::STATUS_PAID, $order->fresh()->statusName());
        $this->assertGreaterThan(0, $order->tasks()->count());
        $this->admin()->get(route('admin.pedidos.index', ['tab' => 'preparacion']))->assertSee('OS-4');
    }

    public function test_a_cash_on_delivery_pickup_goes_straight_to_preparation_with_no_payment_to_confirm(): void
    {
        $order = $this->order(Checkout::STATUS_PENDING, 'OS-5', ['entrega' => 'almacen', 'metodo_pago_nombre' => Checkout::COD_NAME, 'separo' => 0]);

        OrderPayments::markCashOnDelivery($order);

        $order = $order->fresh();
        $this->assertSame(Checkout::STATUS_COD, $order->statusName());
        $this->assertGreaterThan(0, $order->tasks()->count(), 'la venta viaja a Livex');

        $this->admin()->get(route('admin.pedidos.index', ['tab' => 'preparacion']))->assertSee('OS-5')->assertSee('En preparación');
        $this->admin()->get(route('admin.pedidos.index', ['tab' => 'preparacion']))->assertDontSee('Pendiente de pago');
        // No cuenta como venta cobrada: el dinero se recibe al entregar.
        $this->assertNotContains(Checkout::STATUS_COD, OrderPayments::PAID_STATUSES);
    }

    public function test_the_sidebar_badge_counts_what_is_left_to_prepare_and_ship(): void
    {
        $this->order(Checkout::STATUS_PENDING, 'OS-A');
        $this->order(Checkout::STATUS_AWAITING_QR, 'OS-B');
        $this->order(Checkout::STATUS_PAID, 'OS-C');
        $this->order(Checkout::STATUS_COD, 'OS-D');
        $this->order('Enviado', 'OS-E');

        $this->admin()->get(route('admin.dashboard'))->assertSee('2 pedidos por preparar y enviar');
    }

    public function test_the_migration_moves_started_cash_on_delivery_pickups_out_of_pending_and_leaves_the_rest(): void
    {
        $started = $this->order(Checkout::STATUS_PENDING, 'OS-6', ['metodo_pago_nombre' => Checkout::COD_NAME, 'separo' => 0]);
        $started->tasks()->create(['task' => 'crm_venta', 'status' => 'listo']);
        $idle = $this->order(Checkout::STATUS_PENDING, 'OS-7', ['metodo_pago_nombre' => Checkout::COD_NAME, 'separo' => 0]);
        $wallet = $this->order(Checkout::STATUS_PENDING, 'OS-8', ['metodo_pago_nombre' => 'Plin']);
        $wallet->tasks()->create(['task' => 'crm_venta', 'status' => 'listo']);

        (require base_path('database/migrations/2026_11_09_000600_move_cod_orders_to_preparation.php'))->up();

        $this->assertSame(Checkout::STATUS_COD, $started->fresh()->statusName(), 'contraentrega ya enviada al CRM');
        $this->assertSame(Checkout::STATUS_PENDING, $idle->fresh()->statusName(), 'sin tareas: no se toca');
        $this->assertSame(Checkout::STATUS_PENDING, $wallet->fresh()->statusName(), 'un pago por captura no es contraentrega');
    }
}
