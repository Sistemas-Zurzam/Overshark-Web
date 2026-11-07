<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(Request $request): View
    {
        return view('web.contacto', [
            'subjects' => ContactMessage::SUBJECTS,
            'preferences' => ContactMessage::PREFERENCES,
            // Con ?marca=bravos la página se ve con la paleta y el menú de Bravos.
            'brandPage' => $request->query('marca') === 'bravos' ? Brand::query()->active()->where('slug', 'bravos')->first() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Campo trampa: los bots lo llenan, las personas no lo ven. Se responde como si se hubiera enviado.
        $back = $request->input('marca') === 'bravos' ? ['marca' => 'bravos'] : [];

        if (filled($request->input('website'))) {
            return redirect()->route('web.contact', $back)->with('contact_sent', true);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]{6,30}$/'],
            'prefers_contact' => ['required', Rule::in(array_keys(ContactMessage::PREFERENCES))],
            'subject' => ['required', Rule::in(array_keys(ContactMessage::SUBJECTS))],
            'order_number' => ['nullable', 'string', 'max:60'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'privacy' => ['accepted'],
        ], [
            'name.required' => 'Escribe tu nombre.',
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Revisa el correo: no parece válido.',
            'phone.regex' => 'Escribe solo números en el teléfono.',
            'prefers_contact.required' => 'Elige cómo prefieres que te contactemos.',
            'subject.required' => 'Elige el motivo de tu mensaje.',
            'message.required' => 'Escribe tu mensaje.',
            'message.min' => 'Cuéntanos un poco más (al menos 10 caracteres).',
            'privacy.accepted' => 'Necesitamos tu autorización para responderte.',
        ]);

        ContactMessage::query()->create(collect($validated)->except('privacy')->all());

        return redirect()->route('web.contact', $back)->with('contact_sent', true);
    }
}
