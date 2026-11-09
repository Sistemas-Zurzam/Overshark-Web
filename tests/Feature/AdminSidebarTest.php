<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Cliente;
use App\Models\Admin\Order;
use App\Models\Admin\Promocion;
use App\Models\Admin\StatusOrder;
use App\Models\ContactMessage;
use App\Models\LibroReclamacion;
use App\Models\User;
use App\Support\AdminNav;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menú lateral del panel: marcas de pendientes, sección activa, grupos plegables e íconos propios. */
class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
    }

    private function claim(array $extra = []): LibroReclamacion
    {
        return LibroReclamacion::query()->create($extra + [
            'consumer_name' => 'Ana', 'document_type' => 'DNI', 'document_number' => '75351525', 'address' => 'Lima', 'email' => 'ana@example.com',
            'claim_type' => 'reclamo', 'expected_solution' => 'Cambio', 'claim_description' => 'Talla',
        ]);
    }

    public function test_no_badges_when_nothing_is_pending(): void
    {
        $this->assertSame([], AdminNav::badges());

        $this->admin()->get(route('admin.dashboard'))->assertOk()->assertDontSee('admin-nav-badge--', false);
    }

    public function test_badges_count_what_needs_attention_in_each_section(): void
    {
        $pending = StatusOrder::query()->firstOrCreate(['name' => Checkout::STATUS_PENDING]);
        $paid = StatusOrder::query()->firstOrCreate(['name' => Checkout::STATUS_PAID]);
        $cliente = Cliente::query()->create(['name' => 'Ana']);
        foreach ([[$pending, 'OS-1'], [$pending, 'OS-2'], [$paid, 'OS-3']] as [$status, $number]) {
            Order::query()->create(['number_zazu' => $number, 'precio_total' => 50, 'cliente_id' => $cliente->id, 'cliente_name' => 'Ana', 'status_order_id' => $status->id]);
        }
        $this->claim();
        ContactMessage::query()->create(['name' => 'Luis', 'email' => 'l@example.com', 'subject' => 'Hola', 'message' => 'Consulta', 'prefers_contact' => 'email']);
        ContactMessage::query()->create(['name' => 'Leído', 'email' => 'r@example.com', 'subject' => 'Hola', 'message' => 'Consulta', 'prefers_contact' => 'email', 'read_at' => now()]);
        Promocion::query()->create(['name' => 'Ahora', 'scope_type' => 'marca', 'scope_values' => ['OVERSHARK'], 'discount_type' => 'porcentaje', 'discount_value' => 10, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'active' => true]);

        $badges = AdminNav::badges();
        $this->assertSame(2, $badges['admin.pedidos.index']['count'], 'solo los pedidos por confirmar el pago');
        $this->assertSame(1, $badges['admin.reclamos.index']['count']);
        $this->assertSame(1, $badges['admin.messages.index']['count'], 'solo los no leídos');
        $this->assertSame(1, $badges['admin.promociones.index']['count']);

        $this->admin()->get(route('admin.dashboard'))->assertOk()
            ->assertSee('2 pedidos por confirmar el pago')->assertSee('1 reclamos sin responder')
            ->assertSee('1 mensajes sin leer')->assertSee('1 promociones activas ahora');
    }

    public function test_an_overdue_claim_turns_its_badge_red(): void
    {
        $this->claim()->forceFill(['created_at' => now()->subDays(40)])->save();

        $badge = AdminNav::badges()['admin.reclamos.index'];
        $this->assertSame('danger', $badge['tone']);
        $this->assertStringContainsString('1 vencidos', $badge['title']);

        $this->admin()->get(route('admin.dashboard'))->assertSee('admin-nav-badge--danger', false);
    }

    public function test_the_section_stays_highlighted_inside_its_detail_pages(): void
    {
        $cliente = Cliente::query()->create(['name' => 'Ana']);

        $html = $this->admin()->get(route('admin.clientes.show', $cliente))->assertOk()->getContent();

        // Solo «Clientes» queda marcado como página actual.
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('#<a href="[^"]*/admin/clientes"[^>]*is-active[^>]*aria-current="page"#', $html);
        $this->assertTrue(AdminNav::isActive('admin.dashboard') === false);
    }

    public function test_groups_are_collapsible_and_the_active_one_is_flagged_to_stay_open(): void
    {
        $html = $this->admin()->get(route('admin.productos.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<details class="admin-nav-group" data-nav-group="CATÁLOGO"\s+data-nav-active\s+open>#u', $html);
        $this->assertMatchesRegularExpression('#data-nav-group="VENTAS"\s+open>#', $html);
        $this->assertDoesNotMatchRegularExpression('#data-nav-group="VENTAS"\s+data-nav-active#', $html);
        $this->assertStringNotContainsString('data-nav-group="null"', $html);
    }

    public function test_every_menu_entry_has_its_own_icon(): void
    {
        $html = $this->admin()->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('<svg class="size-[18px] shrink-0" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'."\n".'</svg>', $html);

        // Íconos nuevos: Promociones (porcentaje), Sugerencias (foco), Agencias (camión), Nosotros (información).
        foreach (['M14.25 3.75L3.75 14.25', 'M9 1.5a5 5 0 0 0-3 9', 'M1.5 4.5h8.25v7.5H1.5z', 'M9 8.25v3.75'] as $path) {
            $this->assertStringContainsString($path, $html);
        }
    }
}
