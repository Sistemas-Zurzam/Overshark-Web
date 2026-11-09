<?php

namespace App\Http\Controllers\Admin;

use App\Support\ImageOptimizer;
use App\Http\Controllers\Controller;
use App\Models\Admin\BannerPortada;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $brands = Brand::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $selectedBrand = $brands->firstWhere('id', (int) $request->query('marca')) ?? $brands->first();

        $banners = BannerPortada::query()
            ->when($selectedBrand, fn ($query) => $query->where('brand_id', $selectedBrand->id))
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $counts = $brands->mapWithKeys(fn (Brand $brand) => [
            $brand->id => BannerPortada::query()->where('brand_id', $brand->id)->count(),
        ]);

        return view('admin.banners.index', [
            'brands' => $brands,
            'selectedBrand' => $selectedBrand,
            'counts' => $counts,
            'banners' => $banners,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBanner($request);

        $this->guardActiveLimit($validated['brand_id'], $request->boolean('status'));

        $maxSort = (int) BannerPortada::query()->where('brand_id', $validated['brand_id'])->max('sort_order');

        BannerPortada::query()->create([
            'brand_id' => $validated['brand_id'],
            'name' => $validated['name'],
            'image_path' => ImageOptimizer::storeUploaded($request->file('image'), 'banners', ImageOptimizer::BANNER_MAX_SIDE),
            'time' => $validated['time'] ?? 5,
            'modo' => $validated['modo'],
            'status' => $request->boolean('status'),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'sort_order' => $maxSort + 1,
            'buttons' => $this->bannerButtons($request),
            'buttons_position' => $validated['buttons_position'],
        ]);

        return back()->with('status', 'Banner guardado correctamente.');
    }

    public function update(Request $request, BannerPortada $banner): RedirectResponse
    {
        $validated = $this->validateBanner($request, $banner);

        $this->guardActiveLimit($validated['brand_id'], $request->boolean('status'), $banner);

        $data = [
            'brand_id' => $validated['brand_id'],
            'name' => $validated['name'],
            'time' => $validated['time'] ?? 5,
            'modo' => $validated['modo'],
            'status' => $request->boolean('status'),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'buttons' => $this->bannerButtons($request),
            'buttons_position' => $validated['buttons_position'],
        ];

        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }

            $data['image_path'] = ImageOptimizer::storeUploaded($request->file('image'), 'banners', ImageOptimizer::BANNER_MAX_SIDE);
        }

        $banner->update($data);

        return back()->with('status', 'Banner actualizado correctamente.');
    }

    public function toggle(BannerPortada $banner): RedirectResponse
    {
        $this->guardActiveLimit($banner->brand_id, ! $banner->status, $banner);

        $banner->update(['status' => ! $banner->status]);

        return back()->with('status', 'Estado del banner actualizado.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:banners_portada,id'],
        ]);

        foreach (array_values($validated['ids']) as $index => $id) {
            BannerPortada::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('status', 'Orden actualizado.');
    }

    public function destroy(BannerPortada $banner): RedirectResponse
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return back()->with('status', 'Banner eliminado correctamente.');
    }

    private function validateBanner(Request $request, ?BannerPortada $banner = null): array
    {
        return $request->validate([
            'brand_id' => ['required', Rule::exists('brands', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'image' => [$banner ? 'nullable' : 'required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'time' => ['nullable', 'integer', 'min:1', 'max:60'],
            'modo' => ['required', 'in:cover,contain'],
            'status' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
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
    }

    private function guardActiveLimit(int $brandId, bool $willBeActive, ?BannerPortada $ignoring = null): void
    {
        if (! $willBeActive) {
            return;
        }

        $activeCount = BannerPortada::query()
            ->where('brand_id', $brandId)
            ->where('status', true)
            ->when($ignoring, fn ($query) => $query->whereKeyNot($ignoring->id))
            ->count();

        if ($activeCount >= BannerPortada::MAX_ACTIVE_PER_BRAND) {
            throw ValidationException::withMessages([
                'status' => 'Esta marca ya tiene '.BannerPortada::MAX_ACTIVE_PER_BRAND.' banners activos. Desactiva uno antes de activar otro.',
            ]);
        }
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
