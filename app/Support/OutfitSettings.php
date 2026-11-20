<?php

namespace App\Support;

use App\Models\PageContent;

/**
 * Ajustes del constructor libre de «Arma tu outfit»: de qué marcas se ofrecen prendas.
 * Se editan en Admin → Arma tu outfit; sin nada guardado se ofrecen todas.
 */
class OutfitSettings
{
    public const SLUG = 'arma-tu-outfit';

    /** Marcas que el constructor puede ofrecer (las mismas etiquetas que pone OutfitCatalog). */
    public const BRANDS = ['Overshark', 'Overshark Girls', 'Bravos'];

    /** @return array<int, string> */
    public static function brands(): array
    {
        $saved = PageContent::contentFor(self::SLUG)['brands'] ?? null;

        return is_array($saved) ? array_values(array_intersect(self::BRANDS, $saved)) : self::BRANDS;
    }

    /** @param  array<int, string>  $brands */
    public static function saveBrands(array $brands): void
    {
        $page = PageContent::query()->firstOrNew(['slug' => self::SLUG]);
        $page->fill(['content' => [...($page->content ?? []), 'brands' => array_values(array_intersect(self::BRANDS, $brands))]])->save();
    }
}
