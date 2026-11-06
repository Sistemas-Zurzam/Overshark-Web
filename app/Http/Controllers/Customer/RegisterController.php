<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Support\ReturnUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Registro opcional de compradores. No exige DNI, teléfono ni dirección (eso se pide en el checkout). Crear la cuenta
 * inicia la sesión pero NO toca el carrito: session()->regenerate() conserva los datos de la sesión.
 */
class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        ReturnUrl::remember($request);

        return view('web.account.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:customer_accounts,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Ingresa tus nombres.',
            'apellidos.required' => 'Ingresa tus apellidos.',
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Revisa tu correo.',
            // Sin confirmar si la cuenta existe: el mensaje sirve tanto para un correo ya registrado como para otro problema.
            'email.unique' => 'No pudimos crear la cuenta con ese correo. Si ya tienes una, inicia sesión o recupera tu contraseña.',
            'password.required' => 'Crea una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $account = CustomerAccount::query()->create($data);
        $account->sendEmailVerificationNotification();

        Auth::guard('customer')->login($account);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'))->with('status', 'Tu cuenta está lista. Te enviamos un correo para verificarla.');
    }
}
