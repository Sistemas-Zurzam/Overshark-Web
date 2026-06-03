<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparatedViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_is_available(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_admin_views_require_a_valid_jwt(): void
    {
        foreach ([
            '/admin',
            '/admin/pedidos',
            '/admin/clientes',
            '/admin/usuarios',
            '/admin/bodegas',
            '/admin/productos',
            '/admin/medios-pago',
            '/admin/banners',
        ] as $uri) {
            $this->get($uri)->assertRedirect(route('admin.login'));
        }
    }

    public function test_valid_jwt_allows_admin_access(): void
    {
        $user = $this->createAdmin();
        $token = app(JwtService::class)->create($user);

        $this->withUnencryptedCookie(config('jwt.cookie'), $token)
            ->get('/admin')
            ->assertOk();
    }

    public function test_login_creates_jwt_cookie(): void
    {
        $user = $this->createAdmin();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'))
            ->assertCookie(config('jwt.cookie'));
    }

    public function test_logout_revokes_existing_jwt(): void
    {
        $user = $this->createAdmin();
        $jwt = app(JwtService::class);
        $token = $jwt->create($user);

        $this->withUnencryptedCookie(config('jwt.cookie'), $token)
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->withUnencryptedCookie(config('jwt.cookie'), $token)
            ->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    private function createAdmin(): User
    {
        $role = Role::query()->create(['name' => 'admin']);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
