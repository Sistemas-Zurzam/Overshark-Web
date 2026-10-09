<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Categorías de la navegación pública. ZAZU envía todo como "General", así que cada categoría
 * agrupa productos por términos de su nombre (CLASICO, CAMISERO PIKE, WAFFLE, BABY TY...).
 * Un producto pertenece a la primera categoría de PRIORITY cuyos términos aparezcan en su nombre:
 * "CAMISA WAFFLE" es camisa aunque contenga "waffle", y "CUELLO CHINO" es un polo.
 */
class StorefrontCategories
{
    private const CATEGORIES = [
        'polos' => ['label' => 'Polos', 'terms' => ['polo', 'clasico', 'camisero', 'pike', 'pique', 'waffle', 'jersey', 'slim fit', 'cuello', 'baby ty', 'top rib', 'boxyfit', 'neru']],
        'camisas' => ['label' => 'Camisas', 'terms' => ['camisa']],
        'pantalones' => ['label' => 'Pantalones', 'terms' => ['pantal', 'jogger', 'short', 'bermuda', 'cargo']],
        'accesorios' => ['label' => 'Accesorios', 'terms' => ['accesorio', 'gorra', 'media', 'correa', 'cintur', 'mochila']],
    ];

    private const PRIORITY = ['camisas', 'pantalones', 'accesorios', 'polos'];

    public static function all(): array
    {
        return self::CATEGORIES;
    }

    public static function find(?string $slug): ?array
    {
        return self::CATEGORIES[$slug] ?? null;
    }

    public static function label(string $slug): string
    {
        return self::CATEGORIES[$slug]['label'] ?? $slug;
    }

    /**
     * Primera categoría cuyos términos aparecen en el nombre del producto o de su categoría ZAZU.
     */
    public static function slugFor(string $productName, ?string $categoryName = null): ?string
    {
        $haystack = mb_strtolower($productName.' '.$categoryName);

        foreach (self::PRIORITY as $slug) {
            foreach (self::CATEGORIES[$slug]['terms'] as $term) {
                if (str_contains($haystack, $term)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /**
     * Tipo de prenda para armar un outfit: una superior, una inferior y complementos.
     */
    public static function slotFor(?string $slug): string
    {
        return match ($slug) {
            'polos', 'camisas' => 'top',
            'pantalones' => 'bottom',
            default => 'extra',
        };
    }

    public static function apply(Builder $query, string $slug): Builder
    {
        $terms = self::CATEGORIES[$slug]['terms'] ?? [];
        $higherPriority = array_slice(self::PRIORITY, 0, (int) array_search($slug, self::PRIORITY, true));
        $excludedTerms = collect($higherPriority)->flatMap(fn (string $other) => self::CATEGORIES[$other]['terms'])->all();

        return $query
            ->where(function (Builder $categoryQuery) use ($terms): void {
                foreach ($terms as $term) {
                    $categoryQuery->orWhere('name', 'like', "%{$term}%")
                        ->orWhereHas('categoria', fn (Builder $categoria) => $categoria->where('name', 'like', "%{$term}%"));
                }
            })
            ->where(function (Builder $exclusion) use ($excludedTerms): void {
                foreach ($excludedTerms as $term) {
                    $exclusion->where('name', 'not like', "%{$term}%");
                }
            });
    }
}
