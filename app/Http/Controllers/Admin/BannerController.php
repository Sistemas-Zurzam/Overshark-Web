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
            'buttons_position' => ['required', 'in:center-left,center,bottom-left,bottom-center,bottom-right'],
            'buttons' => ['nullable', 'array', 'max:2'],
            'buttons.*.enabled' => ['nullable', 'boolean'],
            'buttons.*.text' => ['nullable', 'string', 'max:40'],
            'buttons.*.url' => ['nullable', 'string', 'max:255'],
            'buttons.*.bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buttons.*.text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buttons.*.shape' => ['nullable', 'in:square,rounded,pill'],
            'buttons.*.x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buttons.*.y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buttons.*.size' => ['nullable', 'in:sm,md,lg,xl'],
        ]);

        BannerPortada::query()->create([
            'name' => $validated['name'],
            'image_path' => $request->file('image')->store('banners', 'public'),
            'time' => $validated['time'] ?? 5,
            'modo' => $validated['modo'],
            'status' => $request->boolean('status'),
            'buttons' => $this->bannerButtons($request),
            'buttons_position' => $validated['buttons_position'],
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
            'buttons_position' => ['required', 'in:center-left,center,bottom-left,bottom-center,bottom-right'],
            'buttons' => ['nullable', 'array', 'max:2'],
            'buttons.*.enabled' => ['nullable', 'boolean'],
            'buttons.*.text' => ['nullable', 'string', 'max:40'],
            'buttons.*.url' => ['nullable', 'string', 'max:255'],
            'buttons.*.bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buttons.*.text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buttons.*.shape' => ['nullable', 'in:square,rounded,pill'],
            'buttons.*.x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buttons.*.y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buttons.*.size' => ['nullable', 'in:sm,md,lg,xl'],
        ]);

        $data = [
            'name' => $validated['name'],
            'time' => $validated['time'] ?? 5,
            'modo' => $validated['modo'],
            'status' => $request->boolean('status'),
            'buttons' => $this->bannerButtons($request),
            'buttons_position' => $validated['buttons_position'],
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

    private function bannerButtons(Request $request): array
    {
        return collect($request->input('buttons', []))
            ->take(2)
            ->filter(fn (array $button) => isset($button['enabled']) && filled($button['text'] ?? null))
            ->map(fn (array $button) => [
                'text' => $button['text'],
                'url' => $button['url'] ?: '#productos',
                'bg_color' => $button['bg_color'] ?: '#111111',
                'text_color' => $button['text_color'] ?: '#ffffff',
                'shape' => $button['shape'] ?: 'rounded',
                'x' => (float) ($button['x'] ?? 20),
                'y' => (float) ($button['y'] ?? 50),
                'size' => $button['size'] ?: 'md',
            ])
            ->values()
            ->all();
    }
}
