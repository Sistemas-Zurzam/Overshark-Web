<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin → Agencias Shalom: las agencias que la clienta puede elegir en el checkout. Solo lectura: la lista la trae
 * Zazu (App\Services\ZazuAgencySyncService) y cada sincronización reemplaza lo que haya, así que se corrige allá.
 */
class CourierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $department = trim((string) $request->query('departamento', ''));
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

        $agencies = DB::table('courier')
            ->when($department !== '', fn ($query) => $query->where('departamento', $department))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('lugar_over', 'like', $like)->orWhere('direccion', 'like', $like)->orWhere('zona', 'like', $like)
                ->orWhere('provincia', 'like', $like)->orWhere('ter_id', 'like', $like)))
            ->orderBy('departamento')->orderBy('provincia')->orderBy('lugar_over')
            ->paginate(30)
            ->withQueryString();

        return view('admin.agencias.index', [
            'agencies' => $agencies,
            'search' => $search,
            'department' => $department,
            'departments' => DB::table('courier')->whereNotNull('departamento')->distinct()->orderBy('departamento')->pluck('departamento'),
            'total' => DB::table('courier')->count(),
            'lastSync' => DB::table('courier')->max('last_synced_at'),
        ]);
    }
}
