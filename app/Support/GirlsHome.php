<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Productos de la línea mujer (Overshark Girls): por marca "Girls" o por el tipo de prenda
 * en el nombre. Lo usan el inicio, la tienda y el catálogo según la línea elegida.
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

    /** Lo contrario de onlyGirls(); con marca vacía (NULL) el producto no es de la línea mujer. */
    public static function exceptGirls(Builder $query): Builder
    {
        $query->whereRaw("LOWER(COALESCE(marca, '')) NOT LIKE ?", ['%girl%']);
        foreach (self::TERMINOS as $termino) {
            $query->where('name', 'not like', "%{$termino}%");
        }

        return $query;
    }
}
