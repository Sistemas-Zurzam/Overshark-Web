<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\User;
use App\Notifications\CustomerResetPassword;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Registro, acceso, recuperación y verificación de cuentas de compradores (guard "customer"). */
class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $override = [])
    {
        return $this->post(route('customer.register.store'), array_merge([
            'name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'ana@example.com',
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
        ], $override));
    }

    private function account(array $override = []): CustomerAccount
    {
        return CustomerAccount::query()->create(array_merge(['name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'ana@example.com', 'password' => 'clave1234'], $override));
    }

    public function test_a_visitor_can_register_is_signed_in_and_gets_a_verification_email(): void
    {
        Notification::fake();

        $this->register()->assertRedirect(route('customer.account'));

        $account = CustomerAccount::query()->firstOrFail();
        $this->assertAuthenticatedAs($account, 'customer');
        $this->assertNotSame('clave1234', $account->password);
        $this->assertFalse($account->hasVerifiedEmail());
        Notification::assertSentTo($account, CustomerVerifyEmail::class);
    }

    public function test_registration_validates_input_and_does_not_reveal_whether_an_email_exists(): void
    {
        $this->account();

        $this->register()->assertSessionHasErrors(['email' => 'No pudimos crear la cuenta con ese correo. Si ya tienes una, inicia sesión o recupera tu contraseña.']);
        $this->register(['email' => 'otra@example.com', 'password' => 'corta1', 'password_confirmation' => 'corta1'])->assertSessionHasErrors('password');
        $this->register(['email' => 'otra@example.com', 'password' => 'sinnumeros', 'password_confirmation' => 'sinnumeros'])->assertSessionHasErrors('password');
        $this->register(['email' => 'otra@example.com', 'password_confirmation' => 'distinta123'])->assertSessionHasErrors('password');
        $this->register(['email' => 'no-es-correo'])->assertSessionHasErrors('email');
        $this->assertSame(1, CustomerAccount::query()->count());
    }

    public function test_the_email_is_case_insensitive_so_it_cannot_be_duplicated(): void
    {
        $this->account();

        $this->register(['email' => 'ANA@Example.com'])->assertSessionHasErrors('email');
        $this->assertSame(1, CustomerAccount::query()->count());
    }

    public function test_login_logout_and_wrong_credentials(): void
    {
        $this->account();

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'incorrecta1'])
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
        $this->assertGuest('customer');

        $this->post(route('customer.login.store'), ['email' => 'ANA@example.com', 'password' => 'clave1234'])->assertRedirect(route('customer.account'));
        $this->assertAuthenticated('customer');

        $this->post(route('customer.logout'))->assertRedirect(route('web.home'));
        $this->assertGuest('customer');
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $this->account();
        $this->get(route('login'));
        $before = session()->getId();

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_private_pages_require_a_customer_session(): void
    {
        foreach (['customer.account', 'customer.orders', 'customer.profile', 'customer.addresses'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
        $this->post(route('customer.logout'))->assertRedirect(route('login'));
    }

    public function test_signed_in_customers_are_sent_away_from_the_login_and_register_screens(): void
    {
        $this->actingAs($this->account(), 'customer');

        $this->get(route('login'))->assertRedirect(route('customer.account'));
        $this->get(route('customer.register'))->assertRedirect(route('customer.account'));
    }

    public function test_after_login_the_customer_goes_back_to_the_page_they_came_from(): void
    {
        $this->account();

        $this->get(route('login'), ['referer' => route('web.cart.index')]);
        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234'])->assertRedirect(route('web.cart.index'));
    }

    public function test_an_external_referer_is_never_used_as_the_return_url(): void
    {
        $this->account();

        $this->get(route('login'), ['referer' => 'https://sitio-malo.example/robar']);
        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234'])->assertRedirect(route('customer.account'));
    }

    public function test_login_is_rate_limited_per_email_and_ip(): void
    {
        $this->account();
        RateLimiter::clear(mb_strtolower('ana@example.com').'|127.0.0.1');

        foreach (range(1, 5) as $attempt) {
            $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'mala'.$attempt])->assertSessionHasErrors('email');
        }

        $this->post(route('customer.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234'])->assertStatus(429);
        $this->assertGuest('customer');
    }

    public function test_password_recovery_sends_a_link_and_gives_the_same_answer_for_unknown_emails(): void
    {
        Notification::fake();
        $account = $this->account();

        $known = $this->post(route('password.email'), ['email' => 'ana@example.com']);
        $unknown = $this->post(route('password.email'), ['email' => 'nadie@example.com']);

        $this->assertSame(session('status'), 'Si ese correo tiene una cuenta, te enviamos un enlace para restablecer la contraseña.');
        $known->assertSessionHas('status');
        $unknown->assertSessionHas('status', session('status'));
        Notification::assertSentTo($account, CustomerResetPassword::class);
        Notification::assertSentToTimes($account, CustomerResetPassword::class, 1);
        Notification::assertCount(1);
    }

    public function test_a_valid_token_resets_the_password_once_and_is_then_invalid(): void
    {
        $account = $this->account();
        $token = Password::broker('customers')->createToken($account);

        $this->get(route('password.reset', ['token' => $token, 'email' => $account->email]))->assertOk()->assertSee($account->email);

        $payload = ['token' => $token, 'email' => $account->email, 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'];
        $this->post(route('password.update'), $payload)->assertRedirect(route('login'));

        $this->post(route('customer.login.store'), ['email' => $account->email, 'password' => 'nueva12345'])->assertRedirect(route('customer.account'));
        $this->post(route('customer.logout'));

        // El token ya se usó.
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
    }

    public function test_an_expired_or_forged_token_is_rejected(): void
    {
        $account = $this->account();
        $token = Password::broker('customers')->createToken($account);
        DB::table('customer_password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);

        $this->post(route('password.update'), ['token' => $token, 'email' => $account->email, 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])
            ->assertSessionHasErrors('email');
        $this->post(route('password.update'), ['token' => 'falso', 'email' => $account->email, 'password' => 'nueva12345', 'password_confirmation' => 'nueva12345'])
            ->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('clave1234', $account->refresh()->password));
    }

    public function test_reset_tokens_are_stored_hashed_in_their_own_table(): void
    {
        $account = $this->account();
        $token = Password::broker('customers')->createToken($account);

        $stored = DB::table('customer_password_reset_tokens')->value('token');

        $this->assertNotSame($token, $stored);
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_the_signed_verification_link_verifies_the_email_and_rejects_other_accounts(): void
    {
        $account = $this->account();
        $other = $this->account(['email' => 'otra@example.com']);
        $this->actingAs($account, 'customer');

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $account->id, 'hash' => sha1($account->email)]);
        $this->get($url)->assertRedirect(route('customer.account'));
        $this->assertTrue($account->refresh()->hasVerifiedEmail());

        $foreign = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->get($foreign)->assertForbidden();
        $this->assertFalse($other->refresh()->hasVerifiedEmail());

        $this->get(route('verification.verify', ['id' => $account->id, 'hash' => sha1($account->email)]))->assertForbidden(); // sin firma
    }

    public function test_verification_can_be_resent_from_the_account(): void
    {
        Notification::fake();
        $account = $this->account();

        $this->actingAs($account, 'customer')->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($account, CustomerVerifyEmail::class);
    }

    public function test_a_customer_session_never_opens_the_admin_and_the_admin_jwt_never_opens_a_customer_account(): void
    {
        $this->actingAs($this->account(), 'customer');
        $this->get('/admin')->assertRedirect(route('admin.login'));

        // Un admin con el guard "web" tampoco es una cuenta de comprador.
        Auth::guard('customer')->logout();
        $this->actingAs(User::factory()->create(), 'web');
        $this->get(route('customer.account'))->assertRedirect(route('login'));
    }

    public function test_customer_accounts_have_no_admin_role_and_the_admin_login_rejects_them(): void
    {
        $this->account();

        $this->post(route('admin.login.store'), ['email' => 'ana@example.com', 'password' => 'clave1234'])->assertSessionHasErrors('email');
        $this->assertFalse(in_array('role_id', (new CustomerAccount)->getFillable(), true));
    }
}
