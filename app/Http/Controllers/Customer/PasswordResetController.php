<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Recuperación de contraseña con el password broker "customers" de Laravel: token con hash en
 * customer_password_reset_tokens, de un solo uso y con vencimiento. La respuesta es siempre la misma, exista o no la cuenta.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('web.account.auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Revisa tu correo.',
        ]);

        Password::broker('customers')->sendResetLink(['email' => mb_strtolower(trim($request->input('email')))]);

        return back()->with('status', 'Si ese correo tiene una cuenta, te enviamos un enlace para restablecer la contraseña.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('web.account.auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
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

        $status = Password::broker('customers')->reset(
            ['email' => mb_strtolower(trim($data['email'])), 'password' => $data['password'], 'password_confirmation' => $data['password'], 'token' => $data['token']],
            function (CustomerAccount $account, string $password): void {
                $account->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($account));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'El enlace no es válido o ya venció. Pide uno nuevo.']);
        }

        return redirect()->route('login')->with('status', 'Listo, ya puedes iniciar sesión con tu nueva contraseña.');
    }
}
