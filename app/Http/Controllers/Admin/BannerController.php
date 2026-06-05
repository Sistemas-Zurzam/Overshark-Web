<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BannerPortada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => BannerPortada::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'time' => ['nullable', 'integer', 'min:1', 'max:60'],
            'modo' => ['required', 'in:cover,contain'],
            'status' => ['nullable', 'boolean'],
        ]);

        BannerPortada::query()->create([
            'name' => $validated['name'],
            'image_path' => $request->file('image')->store('banners', 'public'),
            'time' => $validated['time'] ?? 5,
            'modo' => $validated['modo'],
            'status' => $request->boolean('status'),
        ]);

        return back()->with('status', 'Banner guardado correctamente.');
    }

    public function update(Request $request, BannerPortada $banner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'time' => ['nullable', 'integer', 'min:1', 'max:60'],
            'modo' => ['required', 'in:cover,contain'],
            'status' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'time' => $validated['time'] ?? 5,
            'modo' => $validated['modo'],
            'status' => $request->boolean('status'),
        ];

        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }

            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return back()->with('status', 'Banner actualizado correctamente.');
    }

    public function toggle(BannerPortada $banner): RedirectResponse
    {
        $banner->update(['status' => ! $banner->status]);

        return back()->with('status', 'Estado del banner actualizado.');
    }

    public function destroy(BannerPortada $banner): RedirectResponse
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return back()->with('status', 'Banner eliminado correctamente.');
    }
}
