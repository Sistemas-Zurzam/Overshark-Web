<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Producto;
use App\Support\StorefrontCategories;
use App\Models\Admin\Promocion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Promociones por horas (App\Support\Promotions): un descuento sobre prendas, una marca o una categoría que solo vale
 * entre dos instantes (hora de Lima). Se programan aquí y se activan y terminan solas.
 */
class PromocionController extends Controller
{
    public function index(): View
    {
        $promos = Promocion::query()->orderByDesc('starts_at')->get();

        return view('admin.promociones.index', [
            'promos' => $promos,
            'counts' => $promos->countBy(fn (Promocion $promo) => $promo->status()),
            'reach' => $promos->mapWithKeys(fn (Promocion $promo) => [$promo->id => $this->reach($promo)]),
        ]);
    }

    /**
     * Qué alcanza una promoción hoy, para avisar cuando no va a cambiar ningún precio (p. ej. un precio fijo mayor al
     * que ya cuestan las prendas). Mira las prendas que vende la tienda, con su precio más alto.
     *
     * @return array{matching: int, discounted: int, highest: float}
     */
    private function reach(Promocion $promo): array
    {
        $garments = Producto::query()->soldByStore()->where('price', '>', 0)
            ->selectRaw('name, marca, empresa_nombre, MAX(price) as price')
            ->groupBy('name', 'marca', 'empresa_nombre')
            ->get()
            ->filter(fn (Producto $garment) => $promo->appliesTo($garment->name, $garment->marca, $garment->empresa_nombre));

        return [
            'matching' => $garments->count(),
            'discounted' => $garments->filter(fn (Producto $garment) => $promo->priceFor((float) $garment->price) < (float) $garment->price)->count(),
            'highest' => (float) $garments->max('price'),
        ];
    }

    public function create(): View
    {
        return view('admin.promociones.form', ['promo' => null] + $this->options());
    }

    public function edit(Promocion $promocion): View
    {
        return view('admin.promociones.form', ['promo' => $promocion] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        Promocion::query()->create($this->validated($request));

        return redirect()->route('admin.promociones.index')->with('status', 'Promoción guardada.');
    }

    public function update(Request $request, Promocion $promocion): RedirectResponse
    {
        $promocion->update($this->validated($request));

        return redirect()->route('admin.promociones.index')->with('status', 'Promoción actualizada.');
    }

    public function toggle(Promocion $promocion): RedirectResponse
    {
        $promocion->update(['active' => ! $promocion->active]);

        return back()->with('status', $promocion->active ? 'Promoción reanudada.' : 'Promoción pausada: el precio normal vuelve de inmediato.');
    }

    public function destroy(Promocion $promocion): RedirectResponse
    {
        $promocion->delete();

        return back()->with('status', 'Promoción eliminada.');
    }

    /** Prendas, marcas y categorías entre las que se elige el alcance. */
    private function options(): array
    {
        $sellable = Producto::query()->soldByStore()->where('price', '>', 0);

        return [
            'productNames' => (clone $sellable)->orderBy('name')->pluck('name')->unique()->values(),
            'brandNames' => (clone $sellable)->get(['marca', 'empresa_nombre'])
                ->map(fn (Producto $product) => trim((string) ($product->marca ?: $product->empresa_nombre)))
                ->filter()->unique()->sort()->values(),
            // Categorías de la tienda (Contenido de la tienda → Categorías), por línea: «hombre:polos» => «Hombre · Polos».
            'categorias' => collect(['hombre' => 'Hombre', 'mujer' => 'Mujer'])->flatMap(fn (string $lineLabel, string $line) => collect(StorefrontCategories::all($line))
                ->mapWithKeys(fn (array $category, string $slug) => ["{$line}:{$slug}" => $lineLabel.' · '.$category['label']]))->all(),
        ];
    }

    private function validated(Request $request): array
    {
        $scope = (string) $request->input('scope_type');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:40'],
            'scope_type' => ['required', Rule::in([Promocion::SCOPE_PRODUCT, Promocion::SCOPE_BRAND, Promocion::SCOPE_CATEGORY])],
            'scope_products' => [Rule::requiredIf($scope === Promocion::SCOPE_PRODUCT), 'nullable', 'array'],
            'scope_products.*' => ['string', 'max:255'],
            'scope_brands' => [Rule::requiredIf($scope === Promocion::SCOPE_BRAND), 'nullable', 'array'],
            'scope_brands.*' => ['string', 'max:255'],
            'scope_categories' => [Rule::requiredIf($scope === Promocion::SCOPE_CATEGORY), 'nullable', 'array'],
            'scope_categories.*' => ['string', 'regex:/^(hombre|mujer):[a-z-]+$/'],
            'discount_type' => ['required', Rule::in([Promocion::DISCOUNT_PERCENT, Promocion::DISCOUNT_FIXED])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:99999', Rule::when($request->input('discount_type') === Promocion::DISCOUNT_PERCENT, ['max:90'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ], [
            'scope_products.required' => 'Elige al menos una prenda.',
            'scope_brands.required' => 'Elige al menos una marca.',
            'scope_categories.required' => 'Elige al menos una categoría.',
            'discount_value.max' => 'Un descuento en porcentaje no puede pasar de 90%.',
            'discount_value.gt' => 'El descuento debe ser mayor que cero.',
            'ends_at.after' => 'El fin debe ser posterior al inicio.',
            'starts_at.required' => 'Indica cuándo empieza.',
            'ends_at.required' => 'Indica cuándo termina: una oferta relámpago siempre tiene fin.',
        ]);

        $values = match ($validated['scope_type']) {
            Promocion::SCOPE_PRODUCT => $validated['scope_products'],
            Promocion::SCOPE_BRAND => $validated['scope_brands'],
            default => $validated['scope_categories'],
        };

        return [
            'name' => $validated['name'],
            'label' => filled($validated['label'] ?? null) ? $validated['label'] : null,
            'scope_type' => $validated['scope_type'],
            'scope_values' => array_values(array_unique($values)),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'starts_at' => Promocion::parseLocal($validated['starts_at']),
            'ends_at' => Promocion::parseLocal($validated['ends_at']),
            'active' => $request->boolean('active', true),
        ];
    }
}
