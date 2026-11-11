<?php

namespace App\Http\Controllers\Admin;

use App\Support\ImageOptimizer;
use App\Http\Controllers\Controller;
use App\Models\Admin\BannerPortada;
use App\Models\Admin\Combo;
use App\Models\Admin\MetodoPago;
use App\Models\Admin\ProductoColorImage;
use App\Models\Brand;
use App\Support\AboutPage;
use App\Support\OutfitFlyer;
use App\Support\SiteContent;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BannerController extends Controller
{
    /** Marcas con portada de carrusel y su imagen predeterminada (portada de Hombre, de Mujer y de Bravos). */
    private const DEFAULT_HEROES = ['overshark' => 'hero_hombre', 'overshark-girls' => 'hero_mujer', 'bravos' => 'bravos_hero'];

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
            // Imagen predeterminada que se ve mientras la marca no tenga banners activos (clave de SiteContent::IMAGES).
            'defaultHero' => $selectedBrand ? (self::DEFAULT_HEROES[$selectedBrand->slug] ?? null) : null,
            'inventory' => $this->inventory($selectedBrand),
        ]);
    }

    /** Reemplaza o restaura una de las imágenes fijas de la tienda (App\Support\SiteContent::IMAGES). */
    public function updateSiteImage(Request $request, string $key): RedirectResponse
    {
        abort_unless(array_key_exists($key, SiteContent::IMAGES), 404);

        if ($request->boolean('restore')) {
            SiteContent::restoreImage($key);

            return back()->with('status', 'Se restauró la imagen original.');
        }

        // El ícono y el logo del sitio también pueden ser SVG o ICO (la regla "image" no los acepta).
        $isBrandMark = in_array($key, ['site_icon', 'site_logo'], true);

        $request->validate(['image' => $isBrandMark
            ? ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg,ico', 'max:4096']
            : ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ], [
            'image.required' => 'Elige la imagen que quieres poner.',
            'image.image' => 'La imagen debe ser PNG, JPG o WEBP.',
            'image.mimes' => $isBrandMark ? 'Usa un PNG, JPG, WEBP, SVG o ICO.' : 'La imagen debe ser PNG, JPG o WEBP.',
            'image.max' => $isBrandMark ? 'El archivo puede pesar como máximo 4 MB.' : 'La imagen puede pesar como máximo 8 MB.',
        ]);

        // Un SVG puede llevar scripts que se ejecutan al abrir su dirección: se rechazan los que traen código activo.
        if ($isBrandMark && strtolower($request->file('image')->getClientOriginalExtension()) === 'svg'
            && preg_match('/<\s*(script|iframe|object|embed|foreignObject)\b|\son\w+\s*=|javascript:/i', (string) file_get_contents($request->file('image')->getRealPath()))) {
            return back()->withErrors(['image' => 'El SVG contiene código activo (script o eventos) y no se puede subir. Usa un PNG o un SVG limpio.']);
        }

        SiteContent::replaceImage($key, $request->file('image'));

        return back()->with('status', 'Imagen actualizada.');
    }

    /**
     * Todo lo visual de la tienda: las imágenes fijas (que se reemplazan desde esta pantalla) y lo que ya se administra
     * en otros módulos, con cuántos elementos hay y a dónde ir a cambiarlos.
     */
    private function inventory(?Brand $brand): array
    {
        $saved = SiteContent::saved();

        // Cada imagen pertenece a una marca (o a toda la tienda): la pantalla muestra las de la pestaña elegida y, aparte, las compartidas.
        $groupsFor = fn (?string $slug) => collect(SiteContent::IMAGES)
            ->filter(fn (array $meta) => ($meta[3] ?? null) === $slug)
            ->map(fn (array $meta, string $key) => [
                'key' => $key,
                'label' => $meta[1],
                'group' => $meta[2],
                'url' => SiteContent::imageUrl($key),
                'custom' => (bool) Arr::get($saved, "images.{$key}"),
            ])
            ->groupBy('group')
            ->map(fn ($items, string $group) => ['title' => $group, 'where' => SiteContent::IMAGE_WHERE[$group] ?? '', 'items' => $items])
            ->values();

        $aboutImages = collect(array_keys(AboutPage::IMAGES))->map(fn (string $key) => AboutPage::imageUrl($key, AboutPage::content()))->filter();
        $combos = Combo::query()->where(fn ($query) => $query->whereNull('modality')->orWhere('modality', '!=', 'Outfit'));
        $paymentImages = MetodoPago::query()->get()->flatMap(fn (MetodoPago $method) => [$method->imageUrl(), $method->qrImageUrl()])->filter();

        $elsewhere = [
            ['title' => 'Combos', 'where' => 'Sección y fichas de combos de la tienda.',
                'count' => (clone $combos)->whereNotNull('imagen')->count().' de '.(clone $combos)->count().' con imagen propia',
                'thumbs' => (clone $combos)->whereNotNull('imagen')->latest()->limit(4)->get()->map->imageUrl()->all(),
                'url' => route('admin.combos.index'), 'cta' => 'Administrar combos'],
            ['title' => 'Flyer de «Arma tu outfit»', 'where' => 'Página «¡Arma tu Outfit!».',
                'count' => OutfitFlyer::isCustom() ? 'Imagen subida' : 'Imagen original',
                'thumbs' => [OutfitFlyer::url()],
                'url' => route('admin.outfits.index'), 'cta' => 'Cambiar el flyer'],
            ['title' => 'Página Nosotros', 'where' => 'Portada, historia, proceso y marcas de /nosotros.',
                'count' => $aboutImages->count().' de '.count(AboutPage::IMAGES).' fotos',
                'thumbs' => $aboutImages->take(4)->values()->all(),
                'url' => route('admin.about.edit'), 'cta' => 'Editar Nosotros'],
            ['title' => 'Fotos de productos', 'where' => 'Fichas, tarjetas, buscador y carrito (foto general y fotos por color).',
                'count' => ProductoColorImage::query()->count().' colores con fotos',
                'thumbs' => ProductoColorImage::query()->latest('id')->limit(4)->get()->map(fn (ProductoColorImage $gallery) => $gallery->imageUrls()[0] ?? null)->filter()->values()->all(),
                'url' => route('admin.productos.index'), 'cta' => 'Ir a Productos', 'extra' => ['Carga masiva de imágenes', route('admin.productos.images.bulk')]],
            ['title' => 'Logos de las marcas', 'where' => 'Menú, pantalla de elección y páginas de marca.',
                'count' => Brand::query()->whereNotNull('logo_path')->count().' de '.Brand::query()->count().' marcas con logo',
                'thumbs' => Brand::query()->whereNotNull('logo_path')->limit(4)->get()->map->logoUrl()->all(),
                'url' => route('admin.brands.index'), 'cta' => 'Administrar marcas'],
            ['title' => 'Medios de pago', 'where' => 'Logos y códigos QR del checkout.',
                'count' => $paymentImages->count().' imágenes',
                'thumbs' => $paymentImages->take(4)->values()->all(),
                'url' => route('admin.medios-pago.index'), 'cta' => 'Administrar medios de pago'],
        ];

        return ['fixed' => $brand ? $groupsFor($brand->slug) : collect(), 'shared' => $groupsFor(null), 'elsewhere' => $elsewhere];
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

        BannerPortada::flushActiveCache();

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
            'buttons.*.url' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|#)/i'],
            'buttons.*.bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buttons.*.text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'buttons.*.shape' => ['nullable', 'in:square,rounded,pill'],
            'buttons.*.x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buttons.*.y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buttons.*.size' => ['nullable', 'in:sm,md,lg,xl'],
        ], [
            // Sin esta regla un enlace "javascript:..." se ejecutaría al hacer clic en el botón de la portada.
            'buttons.*.url.regex' => 'El enlace del botón debe empezar con https://, http://, / o #.',
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
