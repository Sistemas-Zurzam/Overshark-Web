<?php

namespace App\Support;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Models\Admin\ProductoColorImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Combos para la página pública /combos.
 * El catálogo ZAZU trae el mismo combo repetido por canal de venta (Live, Publicidad, Posventa…)
 * y sin foto propia; aquí se dejan solo los de la colección actual, sin duplicados, con productos
 * que realmente se pueden comprar y con la foto de las prendas incluidas. Un combo con flyer propio
 * (imagen subida en Admin → Combos) se muestra con ese flyer.
 */
class ComboCatalog
{
    private const PLACEHOLDER = 'images/default-hero-banner.png';

    /** @return Collection<int, array> */
    public static function forStore(string $brand): Collection
    {
        $combos = Combo::activeForMenu()
            ->filter(fn (Combo $combo) => mb_strtoupper(trim((string) $combo->brand)) === mb_strtoupper($brand))
            ->filter(fn (Combo $combo) => $combo->displayItems()->contains(fn (array $item) => trim((string) ($item['zazu'] ?? '')) !== ''));

        $names = $combos
            ->flatMap(fn (Combo $combo) => $combo->displayItems()->pluck('zazu'))
            ->map(fn ($name) => mb_strtoupper(trim((string) $name)))
            ->unique()
            ->values();

        $available = Producto::query()
            ->whereIn(DB::raw('UPPER(TRIM(name))'), $names)
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->whereNotNull('talla')
            ->whereNotNull('color')
            ->pluck('name')
            ->map(fn ($name) => mb_strtoupper(trim($name)))
            ->unique()
            ->flip();

        $images = self::productImages($names);

        return $combos
            ->map(fn (Combo $combo) => self::card($combo, $available, $images))
            ->filter()
            // Mismo contenido y precio en otro canal de venta = el mismo combo; si uno trae flyer, es el que se muestra.
            ->sortByDesc(fn (array $card) => $card['flyer'] ? 1 : 0)
            ->unique('signature')
            ->sortBy([['kind', 'desc'], ['unit_price', 'asc']])
            ->values();
    }

    private static function card(Combo $combo, Collection $available, Collection $images): ?array
    {
        $items = $combo->displayItems()
            ->map(fn (array $item) => [
                'key' => mb_strtoupper(trim((string) ($item['zazu'] ?? ''))),
                'qty' => max(1, (int) ($item['cantidad'] ?? 1)),
            ])
            ->filter(fn (array $item) => $item['key'] !== '')
            ->groupBy('key')
            ->map(fn (Collection $rows, string $key) => ['key' => $key, 'qty' => $rows->sum('qty')])
            ->values();

        $isChoice = $combo->selection_mode === 'choice';
        $buyable = $isChoice
            ? $items->contains(fn (array $item) => $available->has($item['key']))
            : $items->every(fn (array $item) => $available->has($item['key']));

        if (! $buyable || $combo->price === null || (float) $combo->price <= 0) {
            return null;
        }

        $quantity = $isChoice ? max(1, (int) $combo->selection_limit) : $items->sum('qty');
        $label = fn (string $key) => ProductName::title($key);
        $single = ! $isChoice && $items->count() === 1;

        // Los nombres tipo "10x99" no dicen qué prenda es: se nombra por el producto.
        $title = preg_match('/^\s*\d+\s*x\s*[\d.,]+\s*$/i', $combo->name)
            ? ($single ? $label($items[0]['key']).' x'.$quantity : $items->pluck('key')->map($label)->implode(' + '))
            : $combo->name;

        $custom = $combo->imagen && $combo->imagen !== self::PLACEHOLDER ? $combo->imageUrl() : null;

        return [
            'id' => $combo->id,
            // Flyer propio del combo (Admin → Combos): la tarjeta lo muestra completo, como el diseño "Elige tu combo".
            'flyer' => $custom,
            'brand' => mb_strtoupper(trim((string) $combo->brand)),
            'url' => $combo->url ?: route('web.combos.show', $combo),
            'title' => $title,
            'kind' => $single ? 'pack' : 'mix',
            'quantity' => $quantity,
            'price' => (float) $combo->price,
            'unit_price' => (float) $combo->price / $quantity,
            'detail' => match (true) {
                $isChoice => 'Elige '.$quantity.' entre '.$items->pluck('key')->map($label)->implode(', '),
                $single => 'Mezcla colores y tallas',
                default => $items->map(fn (array $item) => $item['qty'].' '.$label($item['key']))->implode(' + '),
            },
            'images' => $custom
                ? [$custom]
                : $items->pluck('key')->map(fn (string $key) => $images->get($key))->filter()->unique()->take(3)->values()->all(),
            'signature' => mb_strtoupper(trim((string) $combo->brand)).'|'.$combo->price.'|'.($isChoice ? 'choice'.$quantity : '').'|'
                .$items->sortBy('key')->map(fn (array $item) => $item['key'].':'.$item['qty'])->implode(','),
        ];
    }

    /** Primera foto de galería de cada producto (miniatura). */
    private static function productImages(Collection $names): Collection
    {
        return ProductoColorImage::query()
            ->whereIn(DB::raw('UPPER(TRIM(product_name))'), $names)
            ->get()
            ->groupBy(fn (ProductoColorImage $gallery) => mb_strtoupper(trim($gallery->product_name)))
            ->map(fn (Collection $galleries) => $galleries
                ->map(fn (ProductoColorImage $gallery) => $gallery->imageUrls()[0] ?? null)
                ->filter()
                ->first())
            ->filter()
            ->map(fn (string $url) => Thumb::url($url));
    }
}
