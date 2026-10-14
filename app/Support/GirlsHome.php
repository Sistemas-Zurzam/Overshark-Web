<?php

namespace App\Support;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use Illuminate\Database\Eloquent\Builder;

/**
 * Datos reales de la home de Overshark Girls: productos de la línea mujer (por
 * marca "Girls" o por el tipo de prenda en el nombre) y sus combos. Si no hay,
 * la vista oculta esas secciones en vez de mostrar datos de ejemplo.
 */
class GirlsHome
{
    private const TERMINOS = ['baby ty', 'top rib', 'buzo', 'girls'];

    /**
     * Limita una consulta de productos a la línea mujer. También lo usa la búsqueda
     * cuando la clienta navega en la colección mujer.
     */
    public static function onlyGirls(Builder $query): Builder
    {
        return $query->where(function ($q): void {
            $q->where('marca', 'like', '%girl%');
            foreach (self::TERMINOS as $termino) {
                $q->orWhere('name', 'like', "%{$termino}%");
            }
        });
    }

    public static function data(): array
    {
        $base = self::onlyGirls(Producto::query()
            ->where('price', '>', 0)
            ->where('stock', '>', 0));

        $products = ProductCards::make(
            (clone $base)
                ->selectRaw('MIN(id) as id, zazu_company_id, MAX(empresa_nombre) as empresa_nombre, MAX(marca) as marca, name, SUM(stock) as total_stock, MIN(price) as min_price, MAX(imagen) as imagen')
                ->groupBy('zazu_company_id', 'name')
                ->orderByDesc('total_stock')
                ->limit(4)
                ->get(),
            asset('images/default-hero-banner.png'),
        );

        $combos = Combo::activeForMenu()
            ->filter(fn ($combo) => str_contains(mb_strtolower($combo->brand.' '.$combo->name), 'girl'))
            ->take(3)
            ->values();

        return [
            'products' => $products,
            'productCount' => (clone $base)->distinct()->count('name'),
            'combos' => $combos,
        ];
    }
}
