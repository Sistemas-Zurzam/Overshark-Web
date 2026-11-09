<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Admin\Promocion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Precio vigente con las promociones por horas (panel → Promociones). El precio de Zazu (productos.price) no se toca:
 * la promoción se calcula encima, así que al vencer o al sincronizar Zazu el precio normal vuelve solo.
 *
 * Es el único sitio que decide un precio promocional: las tarjetas, la ficha, el carrito, el checkout y la venta que
 * se envía a Livex lo usan, para que lo que se ve sea lo que se cobra. Si varias promociones aplican a una prenda
 * gana la más barata (no se acumulan). Los combos no entran: ya tienen su propio precio.
 */
class Promotions
{
    private const MEMO = 'promotions.live';

    /** Promociones vigentes en este instante (una consulta por segundo y petición). */
    public static function live(): Collection
    {
        $now = now()->timestamp;
        $memo = app()->bound(self::MEMO) ? app(self::MEMO) : null;

        if ($memo && $memo['at'] === $now) {
            return $memo['rows'];
        }

        $rows = Promocion::query()->live()->get();
        app()->instance(self::MEMO, ['at' => $now, 'rows' => $rows]);

        return $rows;
    }

    public static function forget(): void
    {
        app()->forgetInstance(self::MEMO);
    }

    /**
     * Mejor promoción de una prenda sobre su precio regular.
     *
     * @return array{promo: Promocion, price: float, regular: float, percent: int, ends_at: \Illuminate\Support\Carbon, label: ?string}|null
     */
    public static function best(string $name, ?string $marca, ?string $empresa, float $regular): ?array
    {
        if ($regular <= 0) {
            return null;
        }

        $best = null;

        foreach (self::live() as $promo) {
            if (! $promo->appliesTo($name, $marca, $empresa)) {
                continue;
            }

            $price = $promo->priceFor($regular);

            if ($price < $regular && ($best === null || $price < $best['price'])) {
                $best = [
                    'promo' => $promo,
                    'price' => $price,
                    'regular' => $regular,
                    'percent' => (int) round((1 - $price / $regular) * 100),
                    'ends_at' => $promo->ends_at,
                    'label' => $promo->label,
                ];
            }
        }

        return $best;
    }

    /** Promoción de una variante (a su precio) o null. */
    public static function forVariant(Producto $variant): ?array
    {
        return self::best($variant->name, $variant->marca, $variant->empresa_nombre, (float) $variant->price);
    }

    /** Lo que se cobra por esta variante ahora mismo. */
    public static function priceFor(Producto $variant): float
    {
        return self::forVariant($variant)['price'] ?? (float) $variant->price;
    }

    /**
     * Pone en cada tarjeta (fila agrupada con min_price) su promoción en el atributo "promo", o null.
     * Los precios regulares siguen en min_price: la vista decide cómo mostrarlos.
     *
     * @param  Collection<int, Producto>  $products
     */
    public static function decorate(Collection $products): Collection
    {
        $live = self::live();

        if ($live->isEmpty()) {
            $products->each(fn (Producto $product) => $product->setAttribute('promo', null));

            return $products;
        }

        $products->each(function (Producto $product): void {
            $product->setAttribute('promo', self::best(
                (string) $product->name,
                $product->marca,
                $product->empresa_nombre,
                (float) ($product->min_price ?? $product->price ?? 0),
            ));
        });

        return $products;
    }

    /** Limita una consulta de productos a lo que alguna promoción vigente alcanza (sin vigentes, a nada). */
    public static function constrain(Builder $query): Builder
    {
        $live = self::live();

        if ($live->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($live): void {
            foreach ($live as $promo) {
                $values = collect($promo->scope_values)->filter()->values();

                match ($promo->scope_type) {
                    Promocion::SCOPE_PRODUCT => $inner->orWhereIn('name', $values->all()),
                    Promocion::SCOPE_CATEGORY => $values->each(function ($key) use ($inner): void {
                        [$line, $slug] = array_pad(explode(':', (string) $key, 2), 2, '');

                        if (StorefrontCategories::find($slug, $line)) {
                            $inner->orWhere(function (Builder $one) use ($line, $slug): void {
                                StorefrontCategories::apply($one, $slug, $line);
                                $line === 'mujer' ? GirlsHome::onlyGirls($one) : GirlsHome::exceptGirls($one);
                            });
                        }
                    }),
                    Promocion::SCOPE_BRAND => $values->each(fn ($brand) => $inner->orWhere(fn (Builder $one) => $one->ofBrand((string) $brand))),
                    default => null,
                };
            }
        });
    }
}
