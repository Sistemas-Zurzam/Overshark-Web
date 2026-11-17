<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\CustomerAccount;
use App\Models\User;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminResendVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_resends_the_verification_link_only_to_unverified_accounts(): void
    {
        Notification::fake();
        $pending = CustomerAccount::query()->create(['name' => 'Rider', 'apellidos' => 'Yactayo', 'email' => 'rider@example.com', 'password' => 'clave1234']);
        $verified = CustomerAccount::query()->create(['name' => 'Ana', 'apellidos' => 'Ríos', 'email' => 'ana@example.com', 'password' => 'clave1234']);
        $verified->markEmailAsVerified();

        $this->withoutMiddleware(AuthenticateJwt::class)->actingAs(User::factory()->create());
        $this->get(route('admin.clientes.index', ['tab' => 'cuentas']))->assertOk()
            ->assertSee(route('admin.clientes.verification.send', $pending))
            ->assertDontSee(route('admin.clientes.verification.send', $verified));

        $this->from(route('admin.clientes.index', ['tab' => 'cuentas']))
            ->post(route('admin.clientes.verification.send', $pending))
            ->assertRedirect(route('admin.clientes.index', ['tab' => 'cuentas']))
            ->assertSessionHas('status', 'Enviamos un nuevo enlace de verificación a rider@example.com.');
        Notification::assertSentTo($pending, CustomerVerifyEmail::class);

        $this->post(route('admin.clientes.verification.send', $verified))->assertSessionHas('status', 'El correo ana@example.com ya está verificado.');
        Notification::assertNotSentTo($verified, CustomerVerifyEmail::class);
    }
}
