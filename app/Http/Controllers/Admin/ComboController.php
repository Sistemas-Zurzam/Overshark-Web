<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Combo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(): View
    {
        return view('admin.combos.index', [
            'combos' => Combo::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'url' => ['required', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/i'],
            'status' => ['nullable', 'boolean'],
        ], [
            'url.regex' => 'La URL debe comenzar con http://, https:// o /.',
        ]);

        Combo::query()->create([
            'name' => $validated['name'],
            'imagen' => $request->file('image')->store('combos', 'public'),
            'url' => $validated['url'],
            'status' => $request->boolean('status'),
        ]);

        return back()->with('status', 'Combo guardado correctamente.');
    }

    public function toggle(Combo $combo): RedirectResponse
    {
        $combo->update(['status' => ! $combo->status]);

        return back()->with('status', 'Estado del combo actualizado.');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        if ($combo->imagen) {
            Storage::disk('public')->delete($combo->imagen);
        }

        $combo->delete();

        return back()->with('status', 'Combo eliminado correctamente.');
    }
}
