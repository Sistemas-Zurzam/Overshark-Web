<?php

namespace App\Support;

use App\Models\Admin\CartSuggestion;
use App\Models\Admin\Producto;
use Illuminate\Support\Collection;

/**
 * "Te puede interesar": prendas que se sugieren al agregar algo al carrito. Salen de las reglas del panel
 * (Sugerencias): primero las de la prenda agregada, luego las de su marca y, si faltan, las prendas con
 * stock de esa misma marca. Nunca se sugiere lo que ya está en el carrito ni algo sin stock o sin precio.
 */
class CartSuggestions
{
    public const LIMIT = 4;

    private const SIZE_ORDER = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];

    /** Sugerencias para la prenda que acaba de agregarse. */
    public static function forProduct(Producto $added, int $limit = self::LIMIT): Collection
    {
        return self::suggest(collect([$added]), $limit);
    }

    /** Sugerencias para todo el carrito (página del carrito). */
    public static function forCart(int $limit = self::LIMIT): Collection
    {
        $ids = collect(session('cart.items', []))
            ->filter(fn (array $item) => ($item['kind'] ?? null) !== 'combo')
            ->pluck('producto_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return self::suggest(Producto::query()->whereIn('id', $ids)->get(), $limit);
    }

    public static function brandOf(Producto $product): string
    {
        return mb_strtoupper(trim((string) ($product->marca ?: $product->empresa_nombre)));
    }

    /** @param  Collection<int, Producto>  $anchors */
    private static function suggest(Collection $anchors, int $limit): Collection
    {
        $excluded = self::cartNames()->merge($anchors->pluck('name'))->map(fn ($name) => mb_strtolower(trim((string) $name)))->all();
        $rules = CartSuggestion::query()->running()->orderBy('sort')->orderBy('id')->get();

        $names = collect();
        foreach ([CartSuggestion::TRIGGER_PRODUCT, CartSuggestion::TRIGGER_BRAND] as $type) {
            foreach ($anchors as $anchor) {
                $value = $type === CartSuggestion::TRIGGER_PRODUCT ? mb_strtolower(trim($anchor->name)) : self::brandOf($anchor);

                foreach ($rules->where('trigger_type', $type) as $rule) {
                    $matches = $type === CartSuggestion::TRIGGER_PRODUCT
                        ? mb_strtolower(trim($rule->trigger_value)) === $value
                        : mb_strtoupper(trim($rule->trigger_value)) === $value;

                    if ($matches) {
                        $names = $names->merge($rule->products ?? []);
                    }
                }
            }
        }

        $wanted = $names->filter()->unique(fn ($name) => mb_strtolower(trim($name)))
            ->reject(fn ($name) => in_array(mb_strtolower(trim($name)), $excluded, true))
            ->values();

        $cards = self::cards($wanted->all());

        if ($cards->count() < $limit) {
            $brands = $anchors->map(fn (Producto $anchor) => self::brandOf($anchor))->filter()->unique();
            $known = $cards->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)))->merge($excluded)->all();
            $fill = collect();

            foreach ($brands as $brand) {
                $fill = $fill->merge(Producto::query()->ofBrand($brand)->inStore()
                    ->whereNotNull('color')->whereNotNull('talla')->where('price', '>', 0)->where('stock', '>', 0)
                    ->orderByDesc('id')->limit(60)->pluck('name'));
            }

            $fill = $fill->unique(fn ($name) => mb_strtolower(trim($name)))
                ->reject(fn ($name) => in_array(mb_strtolower(trim($name)), $known, true))
                ->take(($limit - $cards->count()) * 3)
                ->values();

            $cards = $cards->concat(self::cards($fill->all()));
        }

        return $cards->take($limit)->values();
    }

    private static function cartNames(): Collection
    {
        return collect(session('cart.items', []))
            ->filter(fn (array $item) => ($item['kind'] ?? null) !== 'combo')
            ->pluck('producto');
    }

    /**
     * Tarjetas para sumar con un clic: la foto, el precio "desde" y los colores con sus tallas disponibles.
     * Se conserva el orden en que se pidieron los nombres y se omiten los que ya no tienen stock.
     *
     * @param  array<int, string>  $names
     */
    private static function cards(array $names): Collection
    {
        if ($names === []) {
            return collect();
        }

        $byName = Producto::query()
            ->whereIn('name', $names)
            ->inStore()
            ->whereNotNull('color')->whereNotNull('talla')
            ->where('price', '>', 0)->where('stock', '>', 0)
            ->get()
            ->groupBy(fn (Producto $product) => mb_strtolower(trim($product->name)));

        $cards = collect($names)->map(function (string $name) use ($byName) {
            $rows = $byName->get(mb_strtolower(trim($name)));
            if (! $rows) {
                return null;
            }

            // Un nombre puede repetirse entre empresas de Zazu: se queda con la de la primera variante.
            $first = $rows->first();
            $rows = $rows->filter(fn (Producto $row) => $row->zazu_company_id === $first->zazu_company_id);

            return ['product' => $first, 'rows' => $rows];
        })->filter()->values();

        // Pone la foto de la tarjeta (display_image) en cada producto; sin foto propia no se sugiere.
        $fallback = asset('images/default-hero-banner.png');
        ProductCards::make($cards->pluck('product'), $fallback);

        return $cards->reject(fn (array $card) => $card['product']->display_image === $fallback)->map(function (array $card) {
            /** @var Producto $product */
            $product = $card['product'];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->empresa_nombre ?: $product->marca,
                'price' => (float) $card['rows']->map(fn (Producto $row) => Promotions::priceFor($row))->min(),
                'image' => $product->display_image,
                'colors' => $card['rows']->groupBy('color')->map(
                    fn (Collection $variants) => $variants->pluck('talla')->unique()
                        ->sortBy(fn ($size) => array_search(mb_strtoupper((string) $size), self::SIZE_ORDER, true) === false ? 99 : array_search(mb_strtoupper((string) $size), self::SIZE_ORDER, true))
                        ->values()->all(),
                )->all(),
            ];
        });
    }
}
