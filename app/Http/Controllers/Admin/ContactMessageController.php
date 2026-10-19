<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Mensajes: lo que llega por el formulario de Contacto. */
class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filtro') === 'pendientes' ? 'pendientes' : 'todos';

        return view('admin.mensajes.index', [
            'messages' => ContactMessage::query()
                ->when($filter === 'pendientes', fn ($q) => $q->whereNull('read_at'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filter' => $filter,
            'pending' => ContactMessage::query()->whereNull('read_at')->count(),
        ]);
    }

    public function toggle(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back()->with('status', $message->read_at ? 'Mensaje marcado como atendido.' : 'Mensaje marcado como pendiente.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('status', 'Mensaje eliminado.');
    }
}
