<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Support\ReturnUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Acceso de compradores (guard "customer"). Es independiente del acceso del administrador: otra tabla, otra clave de
 * sesión y sin JWT. Iniciar o cerrar sesión no borra el carrito, que vive en la sesión del navegador.
 */
class LoginController extends Controller
{
    /** Datos del checkout que no deben quedar en el navegador cuando alguien cierra sesión (dispositivos compartidos). */
    private const PERSONAL_SESSION_KEYS = ['checkout.customer', 'checkout.orders', 'checkout.token', 'checkout.payment_receipt_path', 'url.intended'];

    public function create(Request $request): View
    {
        ReturnUrl::remember($request);

        return view('web.account.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresa tu correo.',
            'email.email' => 'Revisa tu correo.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $credentials['email'] = mb_strtolower(trim($credentials['email']));

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }

        // Cambia el id de sesión (contra session fixation) conservando su contenido: el carrito sigue ahí.
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        // El carrito (prendas, sin datos personales) se queda; los datos de contacto y los pedidos de la sesión se olvidan.
        $request->session()->forget(self::PERSONAL_SESSION_KEYS);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('web.home');
    }
}
