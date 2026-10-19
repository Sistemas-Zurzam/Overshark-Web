<?php

namespace App\Support;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use App\Services\ComboSelectionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Datos de "Arma tu outfit": por cada combo de outfit, sus prendas (pasos) y, en cada una, las
 * opciones a elegir como tarjetas producto + color (foto, precio y tallas con stock → id de variante).
 * El carrito lo resuelve web.combos.cart.store con ComboSelectionService, en el mismo orden de prendas.
 */
class OutfitBuilder
{
    private const SIZE_ORDER = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    public function __construct(private readonly ComboSelectionService $selector) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function combos(): Collection
    {
        return Combo::query()
            ->where('status', true)
            ->where('modality', 'Outfit')
            ->where('selection_mode', 'fixed')
            ->whereNotNull('price')
            ->orderBy('price')
            ->get()
            ->map(fn (Combo $combo): array => $this->build($combo))
            ->filter(fn (array $combo): bool => $combo['steps'] !== [])
            ->values();
    }

    private function build(Combo $combo): array
    {
        $slots = $this->selector->slots($combo);
        $names = $slots->flatMap(fn (array $slot) => $slot['variants']->pluck('name'))->unique();
        $galleries = ProductoColorImage::query()->whereIn('product_name', $names)->get()
            ->groupBy('product_name')->map(fn (Collection $rows) => $rows->keyBy('color'));
        $fallback = asset('images/default-hero-banner.png');

        $steps = [];

        foreach ($slots as $slot) {
            $options = $slot['variants']
                ->groupBy(fn (Producto $v) => $v->name.'|'.$v->color)
                ->sortByDesc(fn (Collection $variants) => $variants->sum('stock'))
                ->map(function (Collection $variants) use ($galleries, $fallback): array {
                    $first = $variants->first();

                    return [
                        'product' => Str::title(mb_strtolower($first->name)),
                        'color' => $first->color,
                        'image' => $galleries->get($first->name)?->get($first->color)?->imageUrls()[0] ?? $first->imageUrl() ?? $fallback,
                        'price' => (float) $variants->min('price'),
                        'sizes' => $variants
                            ->sortBy(fn (Producto $v) => array_search(mb_strtoupper((string) $v->talla), self::SIZE_ORDER, true) ?? 99)
                            ->map(fn (Producto $v) => ['size' => $v->talla, 'id' => $v->id])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();

            for ($i = 1; $i <= max(1, (int) $slot['quantity']); $i++) {
                $steps[] = [
                    'label' => Str::title(mb_strtolower($slot['product'])).($slot['quantity'] > 1 ? " {$i}" : ''),
                    'options' => $options,
                ];
            }
        }

        // Si alguna prenda no tiene nada disponible, el combo se muestra como "Muy pronto" y no se puede armar.
        $missing = collect($steps)->filter(fn (array $step): bool => $step['options'] === [])->pluck('label')->values()->all();

        return [
            'id' => $combo->id,
            'available' => $missing === [],
            'missing' => $missing,
            'name' => $combo->name,
            'tagline' => $combo->notes ?: collect($steps)->pluck('label')->implode(' + '),
            'detail' => collect($steps)->pluck('label')->implode(' + '),
            'price' => (float) $combo->price,
            'cart_url' => route('web.combos.cart.store', $combo),
            'steps' => $steps,
        ];
    }
}
