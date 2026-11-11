<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Pantalla donde un usuario invitado crea su contraseña. Reusa el password broker
 * "users" de Laravel (token de un solo uso en password_reset_tokens, con vencimiento).
 */
class PasswordResetController extends Controller
{
    public function edit(Request $request, string $token): View
    {
        return view('admin.auth.set-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.required' => 'Crea una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $status = Password::broker('users')->reset(
            ['email' => mb_strtolower(trim($data['email'])), 'password' => $data['password'], 'password_confirmation' => $data['password'], 'token' => $data['token']],
            function (User $user, string $password): void {
                // Subir jwt_version cierra las sesiones de administrador que hubieran quedado abiertas con la contraseña anterior.
                $user->forceFill(['password' => $password, 'invited_at' => null, 'jwt_version' => $user->jwt_version + 1])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'El enlace no es válido o ya venció. Pide uno nuevo.']);
        }

        return redirect()->route('admin.login')->with('status', 'Listo, ya puedes iniciar sesión con tu nueva contraseña.');
    }
}
