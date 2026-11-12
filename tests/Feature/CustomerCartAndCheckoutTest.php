<?php

namespace Tests\Feature;

use App\Models\Admin\Combo;
use App\Models\Admin\Order;
use App\Models\CustomerAccount;
use App\Models\CustomerAddress;
use App\Support\Checkout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\PlacesWebOrders;
use Tests\TestCase;

/** El carrito sobrevive al login y el checkout sirve igual a invitados y a cuentas (el pedido nuevo se liga a la cuenta). */
class CustomerCartAndCheckoutTest extends TestCase
{
    use PlacesWebOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    private function account(array $override = []): CustomerAccount
    {
        return CustomerAccount::query()->create(array_merge(['name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'ana@example.com', 'phone' => '987654321', 'password' => 'clave1234'], $override));
    }

    private function address(CustomerAccount $account, array $override = []): CustomerAddress
    {
        return $account->addresses()->create(array_merge([
            'alias' => 'Casa', 'recipient' => 'Ana Ríos', 'phone' => '987654321', 'distrito_id' => $this->miraflores->id,
            'address' => 'Calle Los Pinos 45', 'reference' => 'Frente al parque', 'lat' => -12.1211, 'lng' => -77.0297, 'is_default' => true,
        ], $override));
    }

    // --- Carrito -----------------------------------------------------------------------------------------

    public function test_the_cart_survives_login(): void
    {
        $this->account();
        $this->addToCart(2);
        $before = session('cart.items');

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234'])->assertRedirect();

        $this->assertSame($before, session('cart.items'));
        $this->assertSame(2, array_values(session('cart.items'))[0]['qty']);
        $this->get(route('web.cart.index'))->assertOk()->assertSee('Clasico');
    }

    public function test_the_cart_survives_registration(): void
    {
        $this->addToCart();
        $before = session('cart.items');

        $this->post(route('customer.register.store'), [
            'name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'nueva@example.com', 'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ])->assertRedirect();

        $this->assertSame($before, session('cart.items'));
    }

    public function test_a_failed_login_keeps_the_cart(): void
    {
        $this->account();
        $this->addToCart();
        $before = session('cart.items');

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'incorrecta1'])->assertSessionHasErrors('email');

        $this->assertSame($before, session('cart.items'));
    }

    public function test_combos_are_kept_through_login(): void
    {
        $this->account();
        $combo = Combo::query()->create(['name' => 'Pack', 'status' => true, 'price' => 99]);
        session(['cart.items' => ['combo-1' => ['kind' => 'combo', 'producto' => 'Pack', 'qty' => 1, 'price' => 99.0, 'selections' => [['producto' => 'CLASICO', 'color' => 'NEGRO', 'talla' => 'M']]]]]);
        $before = session('cart.items');

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234']);

        $this->assertSame($before, session('cart.items'));
        $this->assertNotNull($combo->id);
    }

    public function test_an_empty_cart_works_through_login_and_logout(): void
    {
        $this->account();

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234'])->assertRedirect();
        $this->get(route('web.cart.index'))->assertOk();
        $this->post(route('customer.logout'))->assertRedirect();
        $this->assertSame([], session('cart.items', []));
    }

    public function test_logout_keeps_the_garments_in_the_cart_but_forgets_personal_checkout_data(): void
    {
        $this->account();
        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234']);
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customerData())->assertSessionHasNoErrors();
        session(['checkout.orders' => [99], 'checkout.token' => 'abc']);
        $cart = session('cart.items');

        $this->post(route('customer.logout'));

        $this->assertSame($cart, session('cart.items'));
        $this->assertNull(session('checkout.customer'));
        $this->assertNull(session('checkout.orders'));
        $this->assertNull(session('checkout.token'));
        $this->get(route('web.checkout.personal'))->assertOk()->assertDontSee('ana@example.com')->assertDontSee('Av. Larco 123');
    }

    // --- Checkout ----------------------------------------------------------------------------------------

    public function test_a_guest_can_still_buy_and_the_order_has_no_account(): void
    {
        $order = $this->buy();

        $this->assertNull($order->customer_account_id);
        $this->get(route('web.checkout.done', $order))->assertOk();
    }

    public function test_a_signed_in_customer_buys_and_the_order_is_linked_to_their_account_from_the_server(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $order = $this->buy();

        $this->assertSame($account->id, $order->customer_account_id);
        $this->assertNotNull($order->cliente_id); // la relación comercial con clientes sigue igual
        $this->assertSame($order->id, $account->orders()->first()->id);
    }

    public function test_the_account_id_cannot_be_forged_from_the_browser(): void
    {
        $mine = $this->account();
        $other = $this->account(['email' => 'otra@example.com']);
        $this->actingAs($mine, 'customer');

        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customerData());
        $total = $this->get(route('web.checkout.payment'))->viewData('total');
        $this->post(route('web.checkout.place'), [
            'method' => Checkout::CARD, 'token' => session('checkout.token'), 'terms' => 1,
            'expected_total' => number_format($total, 2, '.', ''), 'customer_account_id' => $other->id, 'user_id' => $other->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($mine->id, Order::query()->latest('id')->first()->customer_account_id);
    }

    public function test_a_guest_order_is_not_linked_to_an_account_with_the_same_email(): void
    {
        $guestOrder = $this->buy(['email' => 'ana@example.com']);
        $account = $this->account(['email' => 'ana@example.com']);

        $this->actingAs($account, 'customer');

        $this->assertNull($guestOrder->refresh()->customer_account_id);
        $this->assertSame(0, $account->orders()->count());
        $this->get(route('customer.orders'))->assertOk()->assertDontSee($guestOrder->displayNumber());
    }

    public function test_the_checkout_token_still_prevents_duplicate_orders_for_accounts(): void
    {
        $this->actingAs($this->account(), 'customer');
        $order = $this->buy();

        $this->post(route('web.checkout.place'), [
            'method' => Checkout::CARD, 'token' => $order->checkout_token, 'terms' => 1, 'expected_total' => number_format((float) $order->precio_total, 2, '.', ''),
        ]);

        $this->assertSame(1, Order::query()->count());
    }

    public function test_the_checkout_prefills_the_account_contact_and_default_address(): void
    {
        $account = $this->account();
        $this->address($account);
        $this->actingAs($account, 'customer');
        $this->addToCart();

        $this->get(route('web.checkout.personal'))->assertOk()
            ->assertSee('value="ana@example.com"', false)
            ->assertSee('value="Ana"', false)
            ->assertSee('value="987654321"', false)
            ->assertSee('value="Calle Los Pinos 45"', false)
            ->assertSee('value="Frente al parque"', false)
            ->assertSee('Tus direcciones guardadas');
    }

    public function test_a_guest_sees_the_checkout_unchanged_with_a_login_hint_and_no_saved_addresses(): void
    {
        $this->addToCart();

        $this->get(route('web.checkout.personal'))->assertOk()
            ->assertSee('Compra como invitado')
            ->assertSee('Inicia sesión')
            ->assertDontSee('Tus direcciones guardadas')
            ->assertDontSee('Guardar esta dirección en mi cuenta');
    }

    public function test_choosing_a_saved_address_prefills_it_and_other_accounts_addresses_are_ignored(): void
    {
        $account = $this->account();
        $this->address($account);
        $second = $this->address($account, ['alias' => 'Trabajo', 'address' => 'Av. Pardo 800', 'is_default' => false]);
        $stranger = $this->address($this->account(['email' => 'otra@example.com']), ['address' => 'Calle Ajena 999']);
        $this->actingAs($account, 'customer');
        $this->addToCart();

        $this->get(route('web.checkout.personal', ['direccion' => $second->id]))->assertSee('value="Av. Pardo 800"', false)->assertDontSee('value="Calle Los Pinos 45"', false);
        $this->get(route('web.checkout.personal', ['direccion' => $stranger->id]))->assertDontSee('Calle Ajena 999');
    }

    public function test_editing_the_order_data_does_not_change_the_profile_and_a_new_address_is_saved_only_when_asked(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->buy(['nombres' => 'Otra Persona', 'address' => 'Av. Nueva 1']);
        $this->assertSame('Ana', $account->refresh()->name);
        $this->assertSame(0, $account->addresses()->count());

        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customerData(['address' => 'Av. Guardada 77', 'save_address' => 1, 'address_alias' => 'Trabajo']))->assertSessionHasNoErrors();
        $saved = $account->addresses()->first();
        $this->assertSame(['Trabajo', 'Av. Guardada 77', true], [$saved->alias, $saved->address, $saved->is_default]);
        $this->assertEquals(-12.1211, $saved->lat);

        // La misma dirección no se duplica.
        $this->post(route('web.checkout.personal.store'), $this->customerData(['address' => ' av. guardada 77 ', 'save_address' => 1]));
        $this->assertSame(1, $account->addresses()->count());
    }

    public function test_the_order_keeps_its_own_copy_of_the_address_after_the_saved_one_is_deleted(): void
    {
        $account = $this->account();
        $address = $this->address($account);
        $this->actingAs($account, 'customer');
        $order = $this->buy(['address' => 'Calle Los Pinos 45']);

        $this->delete(route('customer.addresses.destroy', $address->id))->assertRedirect();

        $this->assertSame('Calle Los Pinos 45', $order->refresh()->direccion);
        $this->assertSame($this->miraflores->id, $order->distrito_id);
    }

    public function test_agency_and_warehouse_pickup_are_not_treated_as_home_addresses(): void
    {
        $account = $this->account();
        $this->actingAs($account, 'customer');

        $this->buy(['delivery' => 'almacen', 'distrito_id' => null, 'address' => '', 'save_address' => 1]);

        $this->assertSame(0, $account->addresses()->count());
    }

    public function test_stock_and_price_validations_still_apply_to_accounts(): void
    {
        $this->actingAs($this->account(), 'customer');
        $this->addToCart();
        $this->post(route('web.checkout.personal.store'), $this->customerData());
        $total = $this->get(route('web.checkout.payment'))->viewData('total');
        $this->polo->update(['price' => 55]);

        $this->post(route('web.checkout.place'), [
            'method' => Checkout::CARD, 'token' => session('checkout.token'), 'terms' => 1, 'expected_total' => number_format($total, 2, '.', ''),
        ])->assertSessionHasErrors('method');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_the_confirmation_page_of_an_order_opens_for_its_account_but_not_for_another(): void
    {
        $owner = $this->account();
        $this->actingAs($owner, 'customer');
        $order = $this->buy();

        // Otra sesión (sin el pedido en su sesión): la dueña entra por su cuenta; otra cuenta o un invitado reciben 404.
        Auth::guard('customer')->logout();
        $this->flushSession();
        $this->get(route('web.checkout.done', $order))->assertNotFound();
        $this->actingAs($this->account(['email' => 'otra@example.com']), 'customer');
        $this->get(route('web.checkout.done', $order))->assertNotFound();
        $this->actingAs($owner, 'customer');
        $this->get(route('web.checkout.done', $order))->assertOk();
    }
}
