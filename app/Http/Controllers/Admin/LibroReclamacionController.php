<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibroReclamacion;
use Illuminate\Http\RedirectResponse;
use App\Support\CsvSafe;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class LibroReclamacionController extends Controller
{
    private const PLAZOS = ['vencido', 'por_vencer', 'vigente'];

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'tipo' => in_array($request->query('tipo'), ['reclamo', 'queja'], true) ? $request->query('tipo') : '',
            'estado' => in_array($request->query('estado'), LibroReclamacion::STATUSES, true) ? $request->query('estado') : '',
            'plazo' => in_array($request->query('plazo'), self::PLAZOS, true) ? $request->query('plazo') : '',
        ];

        $all = LibroReclamacion::query()->latest()->get();

        $counts = [
            'total' => $all->count(),
            'pendientes' => $all->filter(fn (LibroReclamacion $r) => ! $r->isAnswered())->count(),
            'por_vencer' => $all->filter(fn (LibroReclamacion $r) => $r->isDueSoon())->count(),
            'vencidos' => $all->filter(fn (LibroReclamacion $r) => $r->isOverdue())->count(),
        ];

        $mostOverdue = $all->filter(fn (LibroReclamacion $r) => $r->isOverdue())
            ->sortByDesc(fn (LibroReclamacion $r) => $r->businessDaysOverdue())
            ->first();

        $filtered = $this->applyFilters($all, $filters);

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $reclamos = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page', 'query' => $request->except('page')],
        );

        return view('admin.reclamos.index', [
            'reclamos' => $reclamos,
            'filters' => $filters,
            'counts' => $counts,
            'mostOverdue' => $mostOverdue,
            'statusLabels' => LibroReclamacion::STATUS_LABELS,
        ]);
    }

    public function show(LibroReclamacion $reclamo): View
    {
        return view('admin.reclamos.show', [
            'reclamo' => $reclamo,
            'statusLabels' => LibroReclamacion::STATUS_LABELS,
        ]);
    }

    public function updateStatus(Request $request, LibroReclamacion $reclamo): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', LibroReclamacion::STATUSES)],
        ]);

        $respondedAt = $validated['status'] === LibroReclamacion::STATUS_RESOLVED
            ? ($reclamo->responded_at ?? now())
            : null;

        $reclamo->update(['status' => $validated['status'], 'responded_at' => $respondedAt]);

        return back()->with('status', 'Estado del reclamo actualizado.');
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'tipo' => in_array($request->query('tipo'), ['reclamo', 'queja'], true) ? $request->query('tipo') : '',
            'estado' => in_array($request->query('estado'), LibroReclamacion::STATUSES, true) ? $request->query('estado') : '',
            'plazo' => in_array($request->query('plazo'), self::PLAZOS, true) ? $request->query('plazo') : '',
        ];

        $rows = $this->applyFilters(LibroReclamacion::query()->latest()->get(), $filters);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            CsvSafe::put($handle, ['Código', 'Consumidor', 'Documento', 'Email', 'Teléfono', 'Tipo', 'Pedido', 'Motivo', 'Estado', 'Plazo legal', 'Respondido', 'Registrado']);
            foreach ($rows as $reclamo) {
                CsvSafe::put($handle, [
                    '#'.str_pad((string) $reclamo->id, 6, '0', STR_PAD_LEFT),
                    $reclamo->consumer_name,
                    $reclamo->document_type.' '.$reclamo->document_number,
                    $reclamo->email,
                    $reclamo->phone,
                    $reclamo->claim_type,
                    $reclamo->order_number,
                    $reclamo->claim_description,
                    $reclamo->statusLabel(),
                    $reclamo->legalDeadline()->format('d/m/Y'),
                    $reclamo->responded_at?->format('d/m/Y H:i') ?? '',
                    $reclamo->created_at?->format('d/m/Y H:i'),
                ]);
            }
            fclose($handle);
        }, 'reclamos-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyFilters(\Illuminate\Support\Collection $reclamos, array $filters): \Illuminate\Support\Collection
    {
        $search = mb_strtolower($filters['search']);

        return $reclamos
            ->when($search !== '', fn ($items) => $items->filter(function (LibroReclamacion $r) use ($search): bool {
                $code = '#'.str_pad((string) $r->id, 6, '0', STR_PAD_LEFT);

                return str_contains(mb_strtolower($code), $search)
                    || str_contains(mb_strtolower($r->consumer_name), $search)
                    || str_contains(mb_strtolower((string) $r->order_number), $search);
            }))
            ->when($filters['tipo'] !== '', fn ($items) => $items->where('claim_type', $filters['tipo']))
            ->when($filters['estado'] !== '', fn ($items) => $items->where('status', $filters['estado']))
            ->when($filters['plazo'] !== '', fn ($items) => $items->filter(fn (LibroReclamacion $r) => match ($filters['plazo']) {
                'vencido' => $r->isOverdue(),
                'por_vencer' => $r->isDueSoon(),
                'vigente' => ! $r->isAnswered() && ! $r->isOverdue() && ! $r->isDueSoon(),
                default => true,
            }))
            ->values();
    }
}
