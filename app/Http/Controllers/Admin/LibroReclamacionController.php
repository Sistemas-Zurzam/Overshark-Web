<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibroReclamacion;
use Illuminate\View\View;

class LibroReclamacionController extends Controller
{
    public function index(): View
    {
        $reclamos = LibroReclamacion::query()
            ->latest()
            ->paginate(15);

        return view('admin.reclamos.index', [
            'reclamos' => $reclamos,
            'totalReclamos' => LibroReclamacion::query()->count(),
            'reclamosPendientes' => LibroReclamacion::query()->where('status', 'pendiente')->count(),
        ]);
    }
}
