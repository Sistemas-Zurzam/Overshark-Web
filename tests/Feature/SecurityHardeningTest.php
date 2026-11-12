<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateJwt;
use App\Models\Admin\Cliente;
use App\Models\Admin\Order;
use App\Models\Admin\StatusOrder;
use App\Models\Brand;
use App\Models\LibroReclamacion;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\JwtService;
use App\Services\Payments\PaymentSimulator;
use App\Support\CsvSafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

/** Endurecimiento de seguridad previo a producción (auditoría). */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function admin(?User $user = null): static
    {
        return $this->withoutMiddleware(AuthenticateJwt::class)->actingAs($user ?? User::factory()->create());
    }

    // ---- Modo simulación: lista permitida -----------------------------------------------------------------------

    public function test_the_payment_simulator_only_works_in_local_and_testing(): void
    {
        config(['services.payments.simulate' => true]);

        foreach (['local', 'testing'] as $env) {
            $this->app['env'] = $env;
            $this->assertTrue(PaymentSimulator::enabled(), "debe funcionar en {$env}");
        }

        foreach (['production', 'prod', 'staging', 'produccion', ''] as $env) {
            $this->app['env'] = $env;
            $this->assertFalse(PaymentSimulator::enabled(), "no debe funcionar con APP_ENV='{$env}'");
        }
    }

    public function test_a_simulate_flag_left_on_outside_local_is_switched_off_and_logged(): void
    {
        Log::spy();
        config(['services.payments.simulate' => true]);
        $this->app['env'] = 'staging';

        (new AppServiceProvider($this->app))->boot();

        $this->assertFalse((bool) config('services.payments.simulate'));
        Log::shouldHaveReceived('critical')->once();
    }

    public function test_the_simulate_payment_route_is_closed_when_the_environment_is_not_local(): void
    {
        config(['services.payments.simulate' => true]);
        $this->app['env'] = 'production';

        $status = StatusOrder::query()->firstOrCreate(['name' => 'Esperando pago']);
        $order = Order::query()->create([
            'number_zazu' => 'OS-1', 'precio_total' => 50, 'cliente_id' => Cliente::query()->create(['name' => 'Ana'])->id,
            'cliente_name' => 'Ana', 'status_order_id' => $status->id,
        ]);

        // Con APP_ENV distinto de "testing" Laravel activa el CSRF; aquí interesa que la ruta esté cerrada, no el token.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->withSession(['checkout.orders' => [$order->id]])
            ->post(route('web.checkout.simulate-payment', $order), ['result' => 'approved'])->assertNotFound();
        $this->assertSame('Esperando pago', $order->fresh()->statusName());
    }

    // ---- JWT -----------------------------------------------------------------------------------------------------

    public function test_the_example_jwt_secret_is_rejected_outside_local_and_testing(): void
    {
        config(['jwt.secret' => 'change-me-to-a-random-32-character-minimum-secret']);

        $user = User::factory()->create();
        $jwt = app(JwtService::class);

        $this->app['env'] = 'testing';
        $this->assertNotEmpty($jwt->create($user), 'en testing el valor de ejemplo se tolera');

        $this->app['env'] = 'production';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('valor de ejemplo');
        $jwt->create($user);
    }

    public function test_a_weak_repetitive_jwt_secret_is_rejected_in_production_but_a_random_one_works(): void
    {
        $this->app['env'] = 'production';
        $user = User::factory()->create();

        config(['jwt.secret' => str_repeat('ab', 20)]);
        try {
            app(JwtService::class)->create($user);
            $this->fail('debía rechazar el secreto simple');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('demasiado simple', $exception->getMessage());
        }

        config(['jwt.secret' => bin2hex(random_bytes(32))]);
        $token = app(JwtService::class)->create($user);
        $this->assertSame((string) $user->id, app(JwtService::class)->decode($token)['sub']);
    }

    public function test_admin_sessions_close_when_the_password_is_reset_or_the_access_changes(): void
    {
        $user = User::factory()->create();
        $before = $user->jwt_version;

        $broker = \Illuminate\Support\Facades\Password::broker('users');
        $token = $broker->createToken($user);

        $this->post(route('admin.invite.update'), [
            'token' => $token, 'email' => $user->email, 'password' => 'NuevaClave123', 'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect(route('admin.login'));

        $this->assertSame($before + 1, $user->fresh()->jwt_version);

        $role = \App\Models\Role::query()->firstOrCreate(['name' => 'Otro']);
        $this->admin()->patch(route('admin.usuarios.access', $user), ['role_id' => $role->id])->assertRedirect();
        $this->assertSame($before + 2, $user->fresh()->jwt_version);
    }

    public function test_the_admin_login_compares_a_password_even_when_the_email_does_not_exist(): void
    {
        Hash::shouldReceive('check')->once()->andReturn(false);

        $this->post(route('admin.login.store'), ['email' => 'nadie@example.com', 'password' => 'lo-que-sea'])->assertSessionHasErrors('email');
    }

    // ---- Encabezados y URLs --------------------------------------------------------------------------------------

    public function test_every_response_carries_the_security_headers_and_the_panel_is_never_cached(): void
    {
        $home = $this->get('/');
        $home->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')->assertHeader('Permissions-Policy', 'camera=(), microphone=()');
        $this->assertFalse($home->headers->has('Strict-Transport-Security'), 'sin HTTPS de producción no hay HSTS');

        $panel = $this->admin()->get(route('admin.dashboard'));
        $panel->assertOk()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringContainsString('no-store', (string) $panel->headers->get('Cache-Control'));
    }

    public function test_hsts_is_sent_only_over_https_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => false]);

        $this->get('https://localhost/contacto')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->assertFalse($this->get('http://localhost/contacto')->headers->has('Strict-Transport-Security'));
    }

    public function test_links_in_production_use_app_url_and_not_the_host_header(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://tienda.example']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('https://tienda.example/contacto', url('/contacto'));
        $this->assertStringStartsWith('https://tienda.example/', route('web.contact'));
        URL::forceRootUrl(null);
    }

    // ---- Subidas, límites y CSV ---------------------------------------------------------------------------------

    public function test_the_claims_form_has_a_honeypot_and_rate_limits(): void
    {
        $this->get(route('web.claims.create'))->assertSee('name="website"', false);

        $this->post(route('web.claims.submit'), ['website' => 'https://spam.example', 'consumer_name' => 'Bot'])
            ->assertRedirect(route('web.claims.create'))->assertSessionMissing('claim_draft');
        $this->assertSame(0, LibroReclamacion::query()->count());

        foreach (range(1, 10) as $i) {
            $this->post(route('web.claims.submit'), []);
        }
        $this->post(route('web.claims.submit'), [])->assertStatus(429);
    }

    public function test_the_receipt_upload_is_rate_limited(): void
    {
        Storage::fake('public');

        foreach (range(1, 6) as $i) {
            $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image("r{$i}.jpg")])->assertOk();
        }

        $this->post(route('web.checkout.receipt'), ['payment_receipt' => UploadedFile::fake()->image('r7.jpg')])->assertStatus(429);
    }

    public function test_the_cleanup_command_removes_old_orphans_and_keeps_what_is_in_use(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        Storage::disk('local')->put('reclamos/borradores/viejo.pdf', 'x');
        Storage::disk('local')->put('reclamos/borradores/nuevo.pdf', 'x');
        Storage::disk('public')->put('payment-receipts/huerfano.jpg', 'x');
        Storage::disk('public')->put('payment-receipts/usado.jpg', 'x');
        Storage::disk('public')->put('payment-receipts/reciente.jpg', 'x');

        $status = StatusOrder::query()->firstOrCreate(['name' => 'Pendiente de confirmación']);
        Order::query()->create([
            'number_zazu' => 'OS-9', 'precio_total' => 10, 'cliente_id' => Cliente::query()->create(['name' => 'Ana'])->id, 'cliente_name' => 'Ana',
            'status_order_id' => $status->id, 'comprobante_pago' => 'payment-receipts/usado.jpg',
        ]);

        $old = now()->subDays(3)->getTimestamp();
        touch(Storage::disk('local')->path('reclamos/borradores/viejo.pdf'), $old);
        touch(Storage::disk('public')->path('payment-receipts/huerfano.jpg'), $old);
        touch(Storage::disk('public')->path('payment-receipts/usado.jpg'), $old);

        $this->artisan('limpieza:archivos-huerfanos')->assertSuccessful();

        Storage::disk('local')->assertMissing('reclamos/borradores/viejo.pdf');
        Storage::disk('local')->assertExists('reclamos/borradores/nuevo.pdf');
        Storage::disk('public')->assertMissing('payment-receipts/huerfano.jpg');
        Storage::disk('public')->assertExists('payment-receipts/usado.jpg');
        Storage::disk('public')->assertExists('payment-receipts/reciente.jpg');
    }

    public function test_csv_cells_that_start_like_a_formula_are_neutralised(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://x\")", CsvSafe::cell('=HYPERLINK("http://x")'));
        $this->assertSame("'+51999111222", CsvSafe::cell('+51999111222'));
        $this->assertSame("'-2+3", CsvSafe::cell('-2+3'));
        $this->assertSame("'@SUM(A1)", CsvSafe::cell('@SUM(A1)'));
        $this->assertSame('Ana Pérez', CsvSafe::cell('Ana Pérez'));
        $this->assertSame('12.00', CsvSafe::cell('12.00'));
        $this->assertSame(15, CsvSafe::cell(15));
        $this->assertSame('', CsvSafe::cell(''));
    }

    public function test_the_orders_and_claims_exports_do_not_carry_formulas(): void
    {
        $status = StatusOrder::query()->firstOrCreate(['name' => 'Pago confirmado']);
        $cliente = Cliente::query()->create(['name' => '=cmd|calc']);
        Order::query()->create([
            'number_zazu' => 'OS-7', 'precio_total' => 40, 'cliente_id' => $cliente->id, 'cliente_name' => '=cmd|calc', 'email' => 'x@example.com',
            'status_order_id' => $status->id,
        ]);
        LibroReclamacion::query()->create([
            'consumer_name' => '@SUM(1+1)', 'document_type' => 'DNI', 'document_number' => '75351525', 'address' => 'Lima', 'email' => 'a@example.com',
            'claim_type' => 'reclamo', 'expected_solution' => 'x', 'claim_description' => 'y',
        ]);

        $orders = $this->admin()->get(route('admin.pedidos.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=cmd|calc", $orders);
        $this->assertStringNotContainsString(',=cmd|calc', $orders);

        $claims = $this->admin()->get(route('admin.reclamos.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'@SUM(1+1)", $claims);
    }

    public function test_banner_buttons_reject_javascript_links_but_accept_normal_ones(): void
    {
        $brand = Brand::query()->where('slug', 'overshark')->firstOrFail();
        $payload = fn (string $url) => [
            'brand_id' => $brand->id, 'name' => 'B', 'image' => UploadedFile::fake()->image('b.jpg', 1920, 640), 'time' => 5, 'modo' => 'cover',
            'buttons_position' => 'center-left', 'status' => 1,
            'buttons' => [['enabled' => 1, 'text' => 'Ir', 'url' => $url, 'bg_color' => '#111111', 'text_color' => '#ffffff', 'shape' => 'pill', 'size' => 'md', 'x' => 20, 'y' => 50]],
        ];

        Storage::fake('public');
        $this->admin()->post(route('admin.banners.store'), $payload('javascript:alert(1)'))->assertSessionHasErrors('buttons.0.url');
        $this->admin()->post(route('admin.banners.store'), $payload('data:text/html,<script>1</script>'))->assertSessionHasErrors('buttons.0.url');

        foreach (['https://overshark.pe/combos', '/combos', '#productos'] as $ok) {
            $this->admin()->post(route('admin.banners.store'), $payload($ok))->assertSessionHasNoErrors();
        }
    }

    public function test_an_svg_with_active_code_is_rejected_for_the_logo_and_a_clean_one_is_accepted(): void
    {
        Storage::fake('public');
        $clean = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>';

        foreach ([
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect/></a></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><iframe src="x"/></foreignObject></svg>',
        ] as $evil) {
            $this->admin()->post(route('admin.banners.images', 'site_logo'), ['image' => UploadedFile::fake()->createWithContent('logo.svg', $evil)])
                ->assertSessionHasErrors('image');
        }

        $this->admin()->post(route('admin.banners.images', 'site_logo'), ['image' => UploadedFile::fake()->createWithContent('logo.svg', $clean)])
            ->assertSessionHasNoErrors();
    }

    public function test_customer_login_has_an_ip_wide_cap_besides_the_per_email_one(): void
    {
        // 30 intentos con correos distintos desde la misma IP; el 31 se frena aunque cada correo vaya solo una vez.
        foreach (range(1, 30) as $i) {
            $this->post(route('customer.login.store'), ['email' => "persona{$i}@example.com", 'password' => 'incorrecta1'])->assertSessionHasErrors('email');
        }

        $this->post(route('customer.login.store'), ['email' => 'persona31@example.com', 'password' => 'incorrecta1'])->assertStatus(429);
    }

    public function test_livewire_is_no_longer_installed(): void
    {
        $this->assertFalse(class_exists(\Livewire\Livewire::class));
        $this->assertSame([], collect(app('router')->getRoutes()->getRoutes())->filter(fn ($route) => str_contains($route->uri(), 'livewire'))->all());
    }
}
