<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Verificación del correo de la cuenta (enlace firmado). No bloquea comprar: solo confirma que el correo es de la clienta. */
class VerifyEmailController extends Controller
{
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $account = $request->user('customer');

        // El enlace debe ser de la cuenta con la sesión abierta y del correo que tiene hoy.
        abort_unless((int) $account->getKey() === $id && hash_equals(sha1($account->getEmailForVerification()), $hash), 403);

        if (! $account->hasVerifiedEmail()) {
            $account->markEmailAsVerified();
        }

        return redirect()->route('customer.account')->with('status', 'Tu correo quedó verificado.');
    }

    public function send(Request $request): RedirectResponse
    {
        $account = $request->user('customer');

        if (! $account->hasVerifiedEmail()) {
            $account->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Te enviamos un nuevo enlace de verificación.');
    }
}
