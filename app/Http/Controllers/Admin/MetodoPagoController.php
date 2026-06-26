<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MetodoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MetodoPagoController extends Controller
{
    public function index(): View
    {
        return view('admin.medios-pago.index', [
            'metodosPago' => MetodoPago::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:8192'],
            'image_qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:8192'],
            'status' => ['nullable', 'boolean'],
        ]);

        MetodoPago::query()->create([
            'name' => $validated['name'],
            'imagen' => $request->file('image')->store('metodos-pago', 'public'),
            'imagen_qr' => $request->file('image_qr')?->store('metodos-pago/qr', 'public'),
            'status' => $request->boolean('status'),
        ]);

        return back()->with('status', 'Medio de pago guardado correctamente.');
    }

    public function toggle(MetodoPago $metodoPago): RedirectResponse
    {
        $metodoPago->update(['status' => ! $metodoPago->status]);

        return back()->with('status', 'Estado del medio de pago actualizado.');
    }

    public function destroy(MetodoPago $metodoPago): RedirectResponse
    {
        if ($metodoPago->imagen) {
            Storage::disk('public')->delete($metodoPago->imagen);
        }

        if ($metodoPago->imagen_qr) {
            Storage::disk('public')->delete($metodoPago->imagen_qr);
        }

        $metodoPago->delete();

        return back()->with('status', 'Medio de pago eliminado correctamente.');
    }
}
