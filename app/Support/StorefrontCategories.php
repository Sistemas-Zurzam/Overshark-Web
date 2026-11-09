<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Categorías de la navegación pública. ZAZU envía todo como "General", así que cada categoría
 * agrupa productos por términos de su nombre (CLASICO, CAMISERO PIKE, WAFFLE, BABY TY...).
 * Un producto pertenece a la primera categoría de la prioridad cuyos términos aparezcan en su nombre:
 * "CAMISA WAFFLE" es camisa aunque contenga "waffle", "POLERA NERU" es polera y "CUELLO CHINO" es un polo.
 * Cada línea (hombre / mujer) tiene sus propias categorías; sin línea explícita se usa la de la sesión.
 */
class StorefrontCategories
{
    private const LINES = [
        'hombre' => [
            'categories' => [
                'polos' => ['label' => 'Polos', 'terms' => ['polo', 'clasico', 'camisero', 'pike', 'pique', 'waffle', 'jersey', 'slim fit', 'cuello', 'baby ty', 'top rib', 'boxyfit', 'neru']],
                'camisas' => ['label' => 'Camisas', 'terms' => ['camisa']],
                'poleras' => ['label' => 'Poleras', 'terms' => ['polera', 'hoodie', 'casaca']],
                'pantalones' => ['label' => 'Pantalones', 'terms' => ['pantal', 'jogger', 'short', 'bermuda', 'cargo']],
                'accesorios' => ['label' => 'Accesorios', 'terms' => ['accesorio', 'gorra', 'media', 'correa', 'cintur', 'mochila']],
            ],
            'priority' => ['camisas', 'poleras', 'pantalones', 'accesorios', 'polos'],
        ],
        // Overshark Girls (diseño "02 · Tienda Mujer"). "Buzo" en Perú es el pantalón deportivo: va en joggers.
        'mujer' => [
            'categories' => [
                'baby-tees' => ['label' => 'Baby tees', 'terms' => ['baby ty', 'baby tee']],
                'tops' => ['label' => 'Tops', 'terms' => ['top']],
                'poleras' => ['label' => 'Poleras', 'terms' => ['polera', 'hoodie', 'sudadera']],
                'joggers' => ['label' => 'Joggers', 'terms' => ['jogger', 'buzo', 'pantal']],
                'sets' => ['label' => 'Sets', 'terms' => ['set ', 'conjunto']],
            ],
            'priority' => ['sets', 'baby-tees', 'tops', 'poleras', 'joggers'],
        ],
    ];

    public static function all(?string $line = null): array
    {
        return self::config($line)['categories'];
    }

    public static function find(?string $slug, ?string $line = null): ?array
    {
        return self::all($line)[$slug] ?? null;
    }

    public static function label(string $slug, ?string $line = null): string
    {
        return self::all($line)[$slug]['label'] ?? $slug;
    }

    /**
     * Primera categoría cuyos términos aparecen en el nombre del producto o de su categoría ZAZU.
     */
    public static function slugFor(string $productName, ?string $categoryName = null, ?string $line = null): ?string
    {
        $config = self::config($line);
        $haystack = mb_strtolower($productName.' '.$categoryName.' ');

        foreach ($config['priority'] as $slug) {
            foreach ($config['categories'][$slug]['terms'] as $term) {
                if (str_contains($haystack, $term)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public static function apply(Builder $query, string $slug, ?string $line = null): Builder
    {
        $config = self::config($line);
        $terms = $config['categories'][$slug]['terms'] ?? [];
        $higherPriority = array_slice($config['priority'], 0, (int) array_search($slug, $config['priority'], true));
        $excludedTerms = collect($higherPriority)->flatMap(fn (string $other) => $config['categories'][$other]['terms'])->all();

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

    /** Categorías de cada línea tal como vienen en el código (el admin edita su nombre y sus términos encima). */
    public static function defaults(): array
    {
        return self::LINES;
    }

    /** «polo, Clásico ,waffle» → ['polo', 'clásico', 'waffle'] (en minúsculas, sin vacíos ni repetidos). */
    public static function parseTerms(string $terms): array
    {
        return collect(explode(',', $terms))->map(fn (string $term) => mb_strtolower(trim($term)))->filter()->unique()->values()->all();
    }

    private static function config(?string $line): array
    {
        $line ??= session('storefront.line');
        $key = $line === 'mujer' ? 'mujer' : 'hombre';
        $config = self::LINES[$key];
        $saved = SiteContent::saved()['categorias'][$key] ?? [];

        // Lo editado en Admin → Contenido de la tienda → Categorías va encima; un campo vacío vuelve al original.
        foreach ($config['categories'] as $slug => $category) {
            if (filled($saved[$slug]['label'] ?? null)) {
                $config['categories'][$slug]['label'] = $saved[$slug]['label'];
            }

            $terms = self::parseTerms((string) ($saved[$slug]['terms'] ?? ''));

            if ($terms !== []) {
                $config['categories'][$slug]['terms'] = $terms;
            }
        }

        return $config;
    }
}
