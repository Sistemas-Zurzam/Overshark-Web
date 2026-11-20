<?php

namespace App\Support;

use App\Models\Admin\Producto;
use App\Models\Brand;

/**
 * Datos de "Arma tu outfit" de Bravos (web/bravos-outfit.blade.php): los modelos de la marca con stock,
 * separados en tops y pantalones, cada uno con sus colores (foto y tallas con stock).
 * El look se compra a precio individual de cada prenda (web.cart.look), sin combo.
 */
class BravosOutfit
{
    /** @return array{tops: array<int, array>, bottoms: array<int, array>} */
    public static function steps(Brand $brand): array
    {
        $models = collect(OutfitCatalog::models(Producto::query()->ofBrand($brand->name), 'hombre'));

        return [
            'tops' => $models->filter(fn (array $model) => in_array($model['category'], ['poleras', 'polos', 'camisas'], true))->values()->all(),
            'bottoms' => $models->filter(fn (array $model) => $model['category'] === 'pantalones')->values()->all(),
        ];
    }
}
