<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'roles' ? 'roles' : 'usuarios';
        $search = trim((string) $request->query('search', ''));
        $roleId = $request->query('role');
        $estado = in_array($request->query('estado'), ['activos', 'invitados', 'inactivos'], true) ? $request->query('estado') : '';

        $users = User::query()
            ->with(['role', 'brands'])
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($roleId, fn ($query) => $query->where('role_id', $roleId))
            ->when($estado === 'activos', fn ($query) => $query->where('status', true)->whereNull('invited_at'))
            ->when($estado === 'invitados', fn ($query) => $query->whereNotNull('invited_at'))
            ->when($estado === 'inactivos', fn ($query) => $query->where('status', false)->whereNull('invited_at'))
            ->orderByDesc('id')
            ->get();

        $roles = Role::query()->withCount('users')->orderBy('name')->get();

        return view('admin.usuarios.index', [
            'tab' => $tab,
            'users' => $users,
            'roles' => $roles,
            'brands' => Brand::query()->active()->orderBy('sort_order')->get(),
            'search' => $search,
            'roleId' => $roleId,
            'estado' => $estado,
            'inviteLink' => session('invite_link'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'all_brands_access' => ['nullable', 'boolean'],
            'brand_ids' => ['nullable', 'array'],
            'brand_ids.*' => ['exists:brands,id'],
        ]);

        $allBrands = $request->boolean('all_brands_access');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'password' => Str::random(40),
            'status' => true,
            'invited_at' => now(),
            'all_brands_access' => $allBrands,
        ]);

        if (! $allBrands) {
            $user->brands()->sync($validated['brand_ids'] ?? []);
        }

        return back()->with('invite_link', $this->buildInviteLink($user))
            ->with('status', "Invitación creada para {$user->name}.");
    }

    public function resend(User $user): RedirectResponse
    {
        $user->forceFill(['invited_at' => now()])->save();

        return back()->with('invite_link', $this->buildInviteLink($user))
            ->with('status', "Enlace de invitación regenerado para {$user->name}.");
    }

    public function updateAccess(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'all_brands_access' => ['nullable', 'boolean'],
            'brand_ids' => ['nullable', 'array'],
            'brand_ids.*' => ['exists:brands,id'],
        ]);

        $allBrands = $request->boolean('all_brands_access');

        // Al cambiar el acceso se cierran sus sesiones abiertas (jwt_version): vuelve a entrar con los permisos nuevos.
        $user->forceFill(['role_id' => $validated['role_id'], 'all_brands_access' => $allBrands, 'jwt_version' => $user->jwt_version + 1])->save();
        $user->brands()->sync($allBrands ? [] : ($validated['brand_ids'] ?? []));

        return back()->with('status', 'Acceso actualizado.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['status' => ! $user->status]);

        return back()->with('status', 'Estado del usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return back()->with('status', 'Usuario eliminado.');
    }

    private function buildInviteLink(User $user): string
    {
        $token = Password::broker('users')->createToken($user);

        return route('admin.invite.edit', ['token' => $token, 'email' => $user->email]);
    }
}
