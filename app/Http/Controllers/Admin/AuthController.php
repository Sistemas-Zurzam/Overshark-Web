<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request, JwtService $jwt): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->isAdmin() || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son validas.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->withCookie($jwt->cookie($jwt->create($user)));
    }

    public function refresh(Request $request, JwtService $jwt): RedirectResponse
    {
        return back()->withCookie($jwt->cookie($jwt->create($request->user())));
    }

    public function destroy(Request $request, JwtService $jwt): RedirectResponse
    {
        $request->user()->increment('jwt_version');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->withCookie($jwt->forgetCookie());
    }
}
