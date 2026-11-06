<?php

namespace App\Support;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use Illuminate\Support\Facades\DB;

/**
 * Combos con flyer de la página "Elige tu combo" (diseño Figma "01 · Selecciona tu combo"): 12 de Overshark,
 * 4 de Bravos y 1 de Girls. Se crean como cualquier combo del panel (Admin → Combos), con el flyer en
 * public/images/combos, y desde ahí se editan: nombre, precio, prendas, flyer y si están activos.
 * Cada uno lleva el source_key "promo-web:{clave}" para reconocerlo; si ya existe no se vuelve a tocar.
 */
final class PromoCombos
{
    /** Prendas de Girls entre las que se eligen las 9 del combo (las mismas del combo "Diva"). */
    private const GIRLS_ITEMS = ['BABY TY', 'BABY TY ESCOTE', 'BABY TY MANGA', 'BABY TY ESCOTADO MANGA', 'TOP RIB', 'TOP RIB MANGA', 'TOP RIB CERO'];

    /**
     * Combos en el orden del diseño. `items` es [nombre del producto en Zazu => unidades]; con `choice`, la
     * clienta elige `limit` unidades entre esas prendas.
     *
     * @return array<string, array{name: string, brand: string, price: float, items: array<string, int>, choice?: int}>
     */
    public static function definitions(): array
    {
        $over = fn (string $name, string $product, int $units, float $price = 99) => ['name' => $name, 'brand' => 'Overshark', 'price' => $price, 'items' => [$product => $units]];
        $bravos = fn (string $name, string $product, int $units) => ['name' => $name, 'brand' => 'Bravos', 'price' => 99, 'items' => [$product => $units]];

        return [
            'clasicos-jersey' => $over('Clásicos jersey', 'CLASICO', 9),
            'clasicos-jersey-manga-larga' => $over('Clásicos jersey manga larga', 'JERSEY MANGA LARGA', 7),
            'clasicos-slim-fit' => $over('Clásicos Slim fit', 'SLIM FIT', 5),
            'waffle-clasico' => $over('Waffle clásico', 'WAFFLE', 5),
            'waffle-camisero' => $over('Waffle camisero', 'WAFFLE CAMISERO', 4),
            'waffle-manga-larga' => $over('Waffle manga larga', 'WAFFLE MANGA LARGA', 4),
            'camisa-waffle' => $over('Camisa waffle', 'CAMISA WAFFLE', 3),
            'camisero-pique' => $over('Camisero piqué', 'CAMISERO PIKE', 5),
            'camisero-manga-larga' => $over('Camisero manga larga', 'CAMISERO PIKE MANGA', 4),
            'notch-pique' => $over('Notch piqué', 'CUELLO CHINO', 5),
            'notch-waffle' => $over('Notch waffle', 'CUELLO CHINO WAFFLE', 4),
            'mom-jeans' => $over('Mom jeans', 'JEANS', 2, 119),
            'bravos-pantalones-bratz' => $bravos('Pantalones Bratz', 'PANTALON BRATZ', 3),
            'bravos-comfort-pants' => $bravos('Comfort pants', 'PANTALONES CATANIA', 3),
            'bravos-poleras-neru' => $bravos('Poleras neru', 'POLERA NERU', 3),
            'bravos-poleras-boxy-fit' => $bravos('Poleras boxy fit', 'POLERA BOXYFIT', 3),
            'girls-9-prendas' => ['name' => '9 prendas Girls', 'brand' => 'Girls', 'price' => 99, 'items' => array_fill_keys(self::GIRLS_ITEMS, 1), 'choice' => 9],
        ];
    }

    /**
     * Crea los combos que faltan. Devuelve cuáles se crearon, cuáles ya existían y qué productos no se
     * encontraron en el catálogo (el combo se crea igual, y se puede completar en el panel).
     *
     * @return array{created: list<string>, existing: list<string>, missing: list<string>}
     */
    public static function create(): array
    {
        $result = ['created' => [], 'existing' => [], 'missing' => []];

        DB::transaction(function () use (&$result): void {
            foreach (self::definitions() as $key => $definition) {
                $combo = Combo::query()->firstOrNew(['source_key' => "promo-web:{$key}"]);
                if ($combo->exists) {
                    $result['existing'][] = $definition['name'];

                    continue;
                }

                $combo->fill([
                    'name' => $definition['name'],
                    'brand' => $definition['brand'],
                    'modality' => 'Promoción',
                    'price' => $definition['price'],
                    'imagen' => "images/combos/promo-{$key}.webp",
                    'items' => collect($definition['items'])->map(fn (int $units, string $product) => ['zazu' => $product, 'cantidad' => $units])->values()->all(),
                    'selection_mode' => isset($definition['choice']) ? 'choice' : 'fixed',
                    'selection_limit' => $definition['choice'] ?? null,
                    'status' => true,
                ])->save();
                $combo->update(['url' => '/combos/'.$combo->id]);

                $pivot = [];
                foreach ($definition['items'] as $name => $units) {
                    $product = Producto::query()
                        ->whereRaw('UPPER(TRIM(name)) = ?', [mb_strtoupper($name)])
                        ->orderByDesc('stock')
                        ->orderBy('id')
                        ->first();

                    if (! $product) {
                        $result['missing'][$name] = $name;

                        continue;
                    }

                    $pivot[$product->id] = ['cantidad' => $units];
                }
                $combo->productos()->sync($pivot);

                $result['created'][] = $definition['name'];
            }
        });

        $result['missing'] = array_values($result['missing']);

        return $result;
    }
}
