<?php

namespace Tests\Feature;

use App\Models\Admin\Order;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Notifications\CustomerConfirmNewEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\PlacesWebOrders;
use Tests\TestCase;

/** Mi cuenta: resumen, historial de pedidos, perfil y libreta de direcciones, siempre limitados a la cuenta dueña. */
class CustomerAccountAreaTest extends TestCase
{
    use PlacesWebOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    private function account(array $override = []): CustomerAccount
    {
        return CustomerAccount::query()->create(array_merge(['name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'ana@example.com', 'password' => 'clave1234'], $override));
    }

    private function addressData(array $override = []): array
    {
        return array_merge([
            'alias' => 'Casa', 'recipient' => 'Ana Ríos', 'phone' => '987654321', 'distrito_id' => $this->miraflores->id,
            'address' => 'Calle Los Pinos 45', 'reference' => 'Frente al parque',
        ], $override);
    }

    private function orderFor(CustomerAccount $account): Order
    {
        $this->actingAs($account, 'customer');
        $order = $this->buy(['email' => $account->email]);
        $this->flushSession();

        return $order;
    }

    // --- Resumen y pedidos -------------------------------------------------------------------------------

    public function test_the_dashboard_greets_the_customer_and_shows_the_latest_order(): void
    {
        $account = $this->account();
        $order = $this->orderFor($account);

        $this->actingAs($account, 'customer')->get(route('customer.account'))->assertOk()
            ->assertSee('Hola, Ana')
            ->assertSee('Tu último pedido')
            ->assertSee('#'.$order->displayNumber());
    }

    public function test_an_account_without_orders_gets_an_honest_empty_state(): void
    {
        $this->actingAs($this->account(), 'customer')->get(route('customer.orders'))->assertOk()->assertSee('Aún no tienes pedidos en tu cuenta');
    }

    public function test_the_customer_sees_only_their_orders_with_real_data(): void
    {
        $mine = $this->account();
        $theirs = $this->account(['email' => 'otra@example.com']);
        $myOrder = $this->orderFor($mine);
        $theirOrder = $this->orderFor($theirs);

        $this->actingAs($mine, 'customer')->get(route('customer.orders'))->assertOk()
            ->assertSee('#'.$myOrder->displayNumber())
            ->assertDontSee('#'.$theirOrder->displayNumber())
            ->assertSee('Pago confirmado')
            ->assertSee('1 prenda');
    }

    public function test_the_order_detail_shows_historic_prices_and_totals(): void
    {
        $account = $this->account();
        $order = $this->orderFor($account);
        $this->polo->update(['price' => 99]); // el precio actual cambia; el pedido conserva el suyo

        $this->actingAs($account, 'customer')->get(route('customer.orders.show', $order->id))->assertOk()
            ->assertSee('Clasico')
            ->assertSee('Talla M')
            ->assertSee('1 × S/ 40.00', false)
            ->assertSee('S/ 40.00')
            ->assertSee('Av. Larco 123')
            ->assertSee('Pagado en línea')
            ->assertDontSee('99.00');
    }

    public function test_a_customer_cannot_open_another_customers_order_by_changing_the_url(): void
    {
        $mine = $this->account();
        $theirs = $this->account(['email' => 'otra@example.com']);
        $theirOrder = $this->orderFor($theirs);
        $guestOrder = $this->orderFor($mine); // propio, pero sirve para comprobar que el id ajeno no responde

        $this->actingAs($mine, 'customer');
        $this->get(route('customer.orders.show', $theirOrder->id))->assertNotFound();
        $this->get(route('customer.orders.show', 999999))->assertNotFound();
        $this->get(route('customer.orders.show', $guestOrder->id))->assertOk();
    }

    public function test_guest_orders_are_not_visible_from_any_account(): void
    {
        $guestOrder = $this->buy(['email' => 'ana@example.com']);
        $this->flushSession();
        $account = $this->account(['email' => 'ana@example.com']);

        $this->actingAs($account, 'customer')->get(route('customer.orders.show', $guestOrder->id))->assertNotFound();
    }

    public function test_the_orders_list_is_paginated(): void
    {
        $account = $this->account();
        $first = $this->orderFor($account);
        Order::query()->whereKey($first->id)->update(['customer_account_id' => $account->id]);
        foreach (range(1, 11) as $i) {
            $copy = $first->replicate();
            $copy->number_zazu = 'WEB-'.$i;
            $copy->checkout_token = 'tok-'.$i;
            $copy->customer_account_id = $account->id;
            $copy->save();
        }

        $this->actingAs($account, 'customer')->get(route('customer.orders'))->assertOk()->assertSee('page=2', false);
    }

    // --- Perfil ------------------------------------------------------------------------------------------

    public function test_the_customer_edits_their_profile_without_touching_past_orders(): void
    {
        $account = $this->account();
        $order = $this->orderFor($account);

        $this->actingAs($account, 'customer')->put(route('customer.profile.update'), ['name' => 'Ana María', 'apellidos' => 'Torres', 'phone' => '999888777'])->assertRedirect();

        $this->assertSame(['Ana María', 'Torres', '999888777'], [$account->refresh()->name, $account->apellidos, $account->phone]);
        $this->assertSame('Ana Ríos', $order->refresh()->cliente_name);
    }

    public function test_profile_validation(): void
    {
        $this->actingAs($this->account(), 'customer')->put(route('customer.profile.update'), ['name' => '', 'apellidos' => 'X', 'phone' => '12'])
            ->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->put(route('customer.password.update'), ['current_password' => 'mala', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])->assertSessionHasErrors('current_password');
        $this->put(route('customer.password.update'), ['current_password' => 'clave1234', 'password' => 'clave1234', 'password_confirmation' => 'clave1234'])->assertSessionHasErrors('password');
        $this->put(route('customer.password.update'), ['current_password' => 'clave1234', 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva12345', $account->refresh()->password));
    }

    public function test_a_new_email_only_replaces_the_current_one_after_confirming_the_link(): void
    {
        Notification::fake();
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->post(route('customer.email.request'), ['new_email' => 'nuevo@example.com', 'email_password' => 'mala'])->assertSessionHasErrors('email_password');
        $this->post(route('customer.email.request'), ['new_email' => 'nuevo@example.com', 'email_password' => 'clave1234'])->assertSessionHasNoErrors();

        $this->assertSame(['ana@example.com', 'nuevo@example.com'], [$account->refresh()->email, $account->pending_email]);
        Notification::assertSentOnDemand(CustomerConfirmNewEmail::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'nuevo@example.com');

        $url = URL::temporarySignedRoute('customer.email.confirm', now()->addMinutes(60), ['id' => $account->id, 'hash' => sha1('nuevo@example.com')]);
        $this->get($url)->assertRedirect(route('customer.profile'));

        $account->refresh();
        $this->assertSame(['nuevo@example.com', null], [$account->email, $account->pending_email]);
        $this->assertTrue($account->hasVerifiedEmail());
    }

    public function test_an_email_already_in_use_or_a_forged_confirmation_does_not_change_anything(): void
    {
        $account = $this->account();
        $this->account(['email' => 'ocupado@example.com']);
        $this->actingAs($account, 'customer');

        $this->post(route('customer.email.request'), ['new_email' => 'ocupado@example.com', 'email_password' => 'clave1234'])->assertSessionHasErrors('new_email');

        $forged = URL::temporarySignedRoute('customer.email.confirm', now()->addMinutes(60), ['id' => $account->id, 'hash' => sha1('ocupado@example.com')]);
        $this->get($forged)->assertForbidden(); // no hay un cambio pendiente
        $this->assertSame('ana@example.com', $account->refresh()->email);
    }

    public function test_profile_cannot_change_roles_or_foreign_attributes(): void
    {
        $account = $this->account();

        $this->actingAs($account, 'customer')->put(route('customer.profile.update'), [
            'name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'hacker@example.com', 'role_id' => 1, 'password' => 'otra12345',
        ]);

        $this->assertSame('ana@example.com', $account->refresh()->email);
        $this->assertTrue(Hash::check('clave1234', $account->password));
    }

    // --- Direcciones -------------------------------------------------------------------------------------

    public function test_a_customer_creates_edits_and_deletes_addresses(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->get(route('customer.addresses'))->assertOk()->assertSee('Aún no guardaste direcciones');
        $this->post(route('customer.addresses.store'), $this->addressData())->assertRedirect(route('customer.addresses'));
        $address = $account->addresses()->first();
        $this->assertTrue($address->is_default); // la primera queda como principal

        $this->get(route('customer.addresses'))->assertSee('Calle Los Pinos 45')->assertSee('Miraflores, Lima, Lima');
        $this->get(route('customer.addresses.edit', $address->id))->assertOk()->assertSee('Editar dirección');

        $this->put(route('customer.addresses.update', $address->id), $this->addressData(['alias' => 'Trabajo', 'address' => 'Av. Pardo 800']))->assertRedirect();
        $this->assertSame(['Trabajo', 'Av. Pardo 800'], [$address->refresh()->alias, $address->address]);

        $this->delete(route('customer.addresses.destroy', $address->id))->assertRedirect();
        $this->assertSame(0, $account->addresses()->count());
    }

    public function test_only_one_address_is_the_default_and_deleting_it_promotes_another(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');
        $this->post(route('customer.addresses.store'), $this->addressData(['address' => 'Primera 1']));
        $this->post(route('customer.addresses.store'), $this->addressData(['address' => 'Segunda 2', 'is_default' => 1]));

        $first = $account->addresses()->where('address', 'Primera 1')->first();
        $second = $account->addresses()->where('address', 'Segunda 2')->first();
        $this->assertSame([false, true], [$first->is_default, $second->is_default]);

        $this->patch(route('customer.addresses.default', $first->id));
        $this->assertSame([true, false], [$first->refresh()->is_default, $second->refresh()->is_default]);

        $this->delete(route('customer.addresses.destroy', $first->id));
        $this->assertTrue($second->refresh()->is_default);
        $this->assertSame(1, $account->addresses()->where('is_default', true)->count());
    }

    public function test_address_validation(): void
    {
        $this->actingAs($this->account(), 'customer')
            ->post(route('customer.addresses.store'), ['alias' => 'Playa', 'recipient' => '', 'phone' => '12', 'distrito_id' => 9999, 'address' => ''])
            ->assertSessionHasErrors(['alias', 'recipient', 'phone', 'distrito_id', 'address']);
    }

    public function test_a_point_outside_the_delivery_coverage_is_rejected(): void
    {
        $account = $this->account();

        $this->actingAs($account, 'customer')
            ->post(route('customer.addresses.store'), $this->addressData(['lat' => -13.5319, 'lng' => -71.9675])) // Cusco
            ->assertSessionHasErrors('address');
        $this->assertSame(0, $account->addresses()->count());
    }

    public function test_customers_cannot_see_edit_or_delete_each_others_addresses(): void
    {
        $mine = $this->account();
        $theirs = $this->account(['email' => 'otra@example.com']);
        $theirAddress = $theirs->addresses()->create($this->addressData(['address' => 'Calle Ajena 999', 'is_default' => true]));

        $this->actingAs($mine, 'customer');
        $this->get(route('customer.addresses'))->assertOk()->assertDontSee('Calle Ajena 999');
        $this->get(route('customer.addresses.edit', $theirAddress->id))->assertNotFound();
        $this->put(route('customer.addresses.update', $theirAddress->id), $this->addressData(['address' => 'Hackeada']))->assertNotFound();
        $this->patch(route('customer.addresses.default', $theirAddress->id))->assertNotFound();
        $this->delete(route('customer.addresses.destroy', $theirAddress->id))->assertNotFound();

        $this->assertSame('Calle Ajena 999', $theirAddress->refresh()->address);
        $this->assertInstanceOf(CustomerAddress::class, CustomerAddress::query()->find($theirAddress->id));
    }

    public function test_deleting_an_account_removes_its_addresses_but_keeps_its_orders(): void
    {
        $account = $this->account();
        $account->addresses()->create($this->addressData());
        $order = $this->orderFor($account);

        $account->delete();

        $this->assertSame(0, CustomerAddress::query()->count());
        $this->assertNull($order->refresh()->customer_account_id);
        $this->assertSame('Av. Larco 123', $order->direccion);
    }
}
