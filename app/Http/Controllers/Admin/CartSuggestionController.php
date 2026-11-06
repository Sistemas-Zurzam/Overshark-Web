<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\CartSuggestion;
use App\Models\Admin\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reglas de "Te puede interesar" (App\Support\CartSuggestions): qué prendas se sugieren al agregar una prenda
 * concreta o cualquier prenda de una marca al carrito.
 */
class CartSuggestionController extends Controller
{
    public function index(): View
    {
        return view('admin.sugerencias.index', ['rules' => CartSuggestion::query()->orderBy('sort')->orderBy('id')->get()] + $this->options());
    }

    public function edit(CartSuggestion $suggestion): View
    {
        return view('admin.sugerencias.edit', ['rule' => $suggestion] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        CartSuggestion::query()->create($this->validated($request));

        return redirect()->route('admin.sugerencias.index')->with('status', 'Sugerencia guardada.');
    }

    public function update(Request $request, CartSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update($this->validated($request));

        return redirect()->route('admin.sugerencias.index')->with('status', 'Sugerencia actualizada.');
    }

    public function toggle(CartSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update(['active' => ! $suggestion->active]);

        return back()->with('status', $suggestion->active ? 'Sugerencia activada.' : 'Sugerencia desactivada.');
    }

    public function destroy(CartSuggestion $suggestion): RedirectResponse
    {
        $suggestion->delete();

        return back()->with('status', 'Sugerencia eliminada.');
    }

    /** Prendas y marcas entre las que se elige en el formulario. */
    private function options(): array
    {
        $sellable = Producto::query()->whereNotNull('color')->where('price', '>', 0);

        return [
            'productNames' => (clone $sellable)->orderBy('name')->pluck('name')->unique()->values(),
            'brandNames' => (clone $sellable)->get(['marca', 'empresa_nombre'])
                ->map(fn (Producto $product) => trim((string) ($product->marca ?: $product->empresa_nombre)))
                ->filter()->unique()->sort()->values(),
        ];
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'in:'.CartSuggestion::TRIGGER_PRODUCT.','.CartSuggestion::TRIGGER_BRAND],
            'trigger_product' => ['nullable', 'string', 'max:255', 'required_if:trigger_type,'.CartSuggestion::TRIGGER_PRODUCT],
            'trigger_brand' => ['nullable', 'string', 'max:255', 'required_if:trigger_type,'.CartSuggestion::TRIGGER_BRAND],
            'products' => ['required', 'array', 'min:1', 'max:12'],
            'products.*' => ['string', 'max:255'],
            'title' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'products.required' => 'Elige al menos una prenda para sugerir.',
            'products.min' => 'Elige al menos una prenda para sugerir.',
            'trigger_product.required_if' => 'Elige la prenda que activa la sugerencia.',
            'trigger_brand.required_if' => 'Elige la marca que activa la sugerencia.',
            'ends_at.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        return [
            'name' => $validated['name'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_value' => $validated['trigger_type'] === CartSuggestion::TRIGGER_PRODUCT ? $validated['trigger_product'] : $validated['trigger_brand'],
            'products' => array_values(array_unique($validated['products'])),
            'title' => filled($validated['title'] ?? null) ? $validated['title'] : null,
            'sort' => (int) ($validated['sort'] ?? 0),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'active' => $request->boolean('active', true),
        ];
    }
}
