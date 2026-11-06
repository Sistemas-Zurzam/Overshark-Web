<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Notifications\CustomerConfirmNewEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Perfil y seguridad de la cuenta. Solo cambia los datos de la cuenta: los pedidos ya hechos conservan lo que se
 * escribió en su momento. El correo nuevo no reemplaza al actual hasta que se abre el enlace enviado a él.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('web.account.profile', ['account' => $request->user('customer')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'regex:/^9\d{8}$/'],
        ], [
            'name.required' => 'Ingresa tus nombres.',
            'apellidos.required' => 'Ingresa tus apellidos.',
            'phone.regex' => 'Ingresa un celular de 9 dígitos que empiece con 9.',
        ]);

        $request->user('customer')->update($data);

        return back()->with('status', 'Guardamos tus datos.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'current_password.current_password' => 'Tu contraseña actual no es correcta.',
            'password.required' => 'Crea una contraseña nueva.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.different' => 'La contraseña nueva debe ser distinta a la actual.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $request->user('customer')->update(['password' => $data['password']]);
        $request->session()->regenerate();

        return back()->with('status', 'Cambiamos tu contraseña.');
    }

    public function requestEmailChange(Request $request): RedirectResponse
    {
        $request->merge(['new_email' => mb_strtolower(trim((string) $request->input('new_email')))]);

        $data = $request->validate([
            'new_email' => ['required', 'email', 'max:255', 'unique:customer_accounts,email'],
            'email_password' => ['required', 'current_password:customer'],
        ], [
            'new_email.required' => 'Ingresa el correo nuevo.',
            'new_email.email' => 'Revisa el correo.',
            'new_email.unique' => 'No pudimos usar ese correo. Prueba con otro.',
            'email_password.required' => 'Ingresa tu contraseña para confirmar el cambio.',
            'email_password.current_password' => 'Tu contraseña no es correcta.',
        ]);

        $account = $request->user('customer');
        $account->update(['pending_email' => $data['new_email']]);
        Notification::route('mail', $data['new_email'])->notify(new CustomerConfirmNewEmail($account->id, $data['new_email']));

        return back()->with('status', "Te enviamos un enlace a {$data['new_email']}. Tu correo cambia cuando lo confirmes.");
    }

    public function confirmEmailChange(Request $request, int $id, string $hash): RedirectResponse
    {
        /** @var CustomerAccount $account */
        $account = $request->user('customer');
        $pending = (string) $account->pending_email;

        abort_unless($pending !== '' && (int) $account->getKey() === $id && hash_equals(sha1($pending), $hash), 403);

        // Pudo registrarse otra cuenta con ese correo mientras tanto.
        if (CustomerAccount::query()->where('email', $pending)->whereKeyNot($account->id)->exists()) {
            $account->update(['pending_email' => null]);

            return redirect()->route('customer.profile')->withErrors(['new_email' => 'Ese correo ya no está disponible. Prueba con otro.']);
        }

        $account->forceFill(['email' => $pending, 'pending_email' => null, 'email_verified_at' => now()])->save();

        return redirect()->route('customer.profile')->with('status', 'Tu correo se actualizó.');
    }
}
