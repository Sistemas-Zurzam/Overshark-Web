<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Bodega;
use App\Models\Admin\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BodegaController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $estado = in_array($request->query('estado'), ['activas', 'inactivas'], true) ? $request->query('estado') : '';

        $bodegas = Bodega::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('responsable', 'like', "%{$search}%")))
            ->when($estado !== '', fn ($query) => $query->where('is_active', $estado === 'activas'))
            ->orderBy('name')
            ->get()
            ->map(fn (Bodega $bodega) => (object) [
                'model' => $bodega,
                'productCount' => $bodega->productCount(),
                'unitCount' => $bodega->unitCount(),
            ]);

        $unassignedQuery = Producto::query()->whereNull('bodega_id');
        $unassignedProductCount = (clone $unassignedQuery)->select('name', 'zazu_company_id')->groupBy('name', 'zazu_company_id')->get()->count();
        $unassignedUnitCount = (int) (clone $unassignedQuery)->sum('stock');

        return view('admin.bodegas.index', [
            'bodegas' => $bodegas,
            'search' => $search,
            'estado' => $estado,
            'unassignedProductCount' => $unassignedProductCount,
            'unassignedUnitCount' => $unassignedUnitCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
        ]);

        Bodega::create($validated + ['is_active' => true]);

        return back()->with('status', 'Bodega creada correctamente.');
    }

    public function update(Request $request, Bodega $bodega): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
        ]);

        $bodega->update($validated);

        return back()->with('status', 'Bodega actualizada correctamente.');
    }

    public function toggle(Bodega $bodega): RedirectResponse
    {
        $bodega->update(['is_active' => ! $bodega->is_active]);

        return back()->with('status', 'Estado de la bodega actualizado.');
    }

    public function destroy(Bodega $bodega): RedirectResponse
    {
        if ($bodega->productos()->exists()) {
            return back()->with('error', 'No puedes eliminar una bodega con productos asignados. Reasígnalos primero.');
        }

        $bodega->delete();

        return back()->with('status', 'Bodega eliminada correctamente.');
    }

    public function assign(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $groups = Producto::query()
            ->whereNull('bodega_id')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->selectRaw('name, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, COUNT(*) as variant_count, SUM(stock) as total_stock')
            ->groupBy('name', 'zazu_company_id')
            ->orderBy('name')
            ->get();

        return view('admin.bodegas.assign', [
            'groups' => $groups,
            'search' => $search,
            'bodegas' => Bodega::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function assignStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bodega_id' => ['required', 'exists:bodegas,id'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*' => ['string'],
        ], [
            'groups.required' => 'Selecciona al menos un producto para asignar.',
        ]);

        $updated = 0;
        foreach ($validated['groups'] as $group) {
            [$name, $companyId] = array_pad(explode('|', $group, 2), 2, null);
            $updated += Producto::query()
                ->where('name', $name)
                ->where(fn ($query) => $companyId === '' || $companyId === null
                    ? $query->whereNull('zazu_company_id')
                    : $query->where('zazu_company_id', $companyId))
                ->update(['bodega_id' => $validated['bodega_id']]);
        }

        return redirect()->route('admin.bodegas.index')->with('status', "{$updated} variantes asignadas a la bodega.");
    }
}
