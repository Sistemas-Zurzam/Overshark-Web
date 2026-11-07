<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/** Admin → Mensajes: lo que llega por el formulario de Contacto. */
class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'periodo' => in_array($request->query('periodo'), ['7', '30', 'mes'], true) ? $request->query('periodo') : '',
        ];
        $tab = in_array($request->query('tab'), ['pendientes', 'atendidos', 'todos'], true) ? $request->query('tab') : 'pendientes';

        $periodStart = match ($filters['periodo']) {
            '7' => now()->subDays(7),
            '30' => now()->subDays(30),
            'mes' => now()->startOfMonth(),
            default => null,
        };

        $base = ContactMessage::query()
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")
                ->orWhere('message', 'like', "%{$filters['search']}%")))
            ->when($periodStart !== null, fn ($query) => $query->where('created_at', '>=', $periodStart))
            ->latest();

        $all = $base->get();

        $counts = [
            'pendientes' => $all->whereNull('read_at')->count(),
            'atendidos' => $all->whereNotNull('read_at')->count(),
            'todos' => $all->count(),
        ];

        $filtered = match ($tab) {
            'pendientes' => $all->whereNull('read_at')->values(),
            'atendidos' => $all->whereNotNull('read_at')->values(),
            default => $all,
        };

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $messages = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page', 'query' => $request->except('page')],
        );

        return view('admin.mensajes.index', [
            'messages' => $messages,
            'filters' => $filters,
            'tab' => $tab,
            'counts' => $counts,
        ]);
    }

    public function show(ContactMessage $message): View
    {
        return view('admin.mensajes.show', ['message' => $message]);
    }

    public function toggle(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back()->with('status', $message->read_at ? 'Mensaje marcado como atendido.' : 'Mensaje marcado como pendiente.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Mensaje eliminado.');
    }
}
