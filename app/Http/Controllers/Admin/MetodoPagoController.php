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
            'titular' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:8192'],
            'image_qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:8192'],
            'status' => ['nullable', 'boolean'],
        ]);

        MetodoPago::query()->create([
            'name' => $validated['name'],
            'titular' => $validated['titular'] ?? null,
            'numero' => $validated['numero'] ?? null,
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

    public function update(Request $request, MetodoPago $metodoPago): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'titular' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:8192'],
            'image_qr' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:8192'],
            'remove_qr' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'titular' => $validated['titular'] ?? null,
            'numero' => $validated['numero'] ?? null,
            'status' => $request->boolean('status'),
        ];

        if ($request->hasFile('image')) {
            if ($metodoPago->imagen) {
                Storage::disk('public')->delete($metodoPago->imagen);
            }

            $data['imagen'] = $request->file('image')->store('metodos-pago', 'public');
        }

        if ($request->boolean('remove_qr') && $metodoPago->imagen_qr) {
            Storage::disk('public')->delete($metodoPago->imagen_qr);
            $data['imagen_qr'] = null;
        }

        if ($request->hasFile('image_qr')) {
            if ($metodoPago->imagen_qr) {
                Storage::disk('public')->delete($metodoPago->imagen_qr);
            }

            $data['imagen_qr'] = $request->file('image_qr')->store('metodos-pago/qr', 'public');
        }

        $metodoPago->update($data);

        return back()->with('status', 'Medio de pago actualizado correctamente.');
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
