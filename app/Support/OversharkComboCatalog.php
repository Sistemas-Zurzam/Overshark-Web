<?php

namespace App\Support;

use Illuminate\Support\Str;

class OversharkComboCatalog
{
    public static function all(): array
    {
        $rows = [
            self::fixed('Overshark', 'Live', '10x99', 99, [['CLASICO', 10]]),
            self::fixed('Overshark', 'Live', '6x99', 99, [['WAFFLE', 6]]),
            self::fixed('Overshark', 'Live', '5x99', 99, [['CAMISERO PIKE', 5]]),
            self::fixed('Overshark', 'Live', '4x99', 99, [['CAMISA WAFFLE', 4]]),
            self::fixed('Overshark', 'Live', '3x99', 99, [['CAMISA WAFFLE', 3]]),
            self::fixed('Overshark', 'Live', '4x99', 99, [['CAMISERO PIKE MANGA', 4]]),
            self::fixed('Overshark', 'Live', '4x99', 99, [['WAFFLE MANGA LARGA', 4]]),
            self::fixed('Overshark', 'Live', '7x99', 99, [['JERSEY MANGA LARGA', 7]]),
            self::fixed('Overshark', 'Live', '4x99', 99, [['CUELLO CHINO WAFFLE', 4]]),
            self::fixed('Overshark', 'Live', '5x99', 99, [['CUELLO CHINO', 5]]),
            self::fixed('Overshark', 'Live', '5x99', 99, [['SLIM FIT', 5]]),

            self::fixed('Overshark', 'Publicidad', '4x50', 50, [['CLASICO', 4]]),
            self::fixed('Overshark', 'Publicidad', '9x99', 99, [['CLASICO', 9]]),
            self::fixed('Overshark', 'Publicidad', '5x99', 99, [['CAMISERO PIKE', 5]]),
            self::fixed('Overshark', 'Publicidad', '2x50', 50, [['CAMISERO PIKE', 2]]),
            self::fixed('Overshark', 'Publicidad', '4x99', 99, [['CAMISERO PIKE MANGA', 4]]),
            self::fixed('Overshark', 'Publicidad', '4x99', 99, [['WAFFLE CAMISERO', 4]]),
            self::fixed('Overshark', 'Publicidad', '5x99', 99, [['WAFFLE', 5]]),
            self::fixed('Overshark', 'Publicidad', '2x50', 50, [['WAFFLE', 2]]),
            self::fixed('Overshark', 'Publicidad', '5x99', 99, [['SLIM FIT', 5]]),
            self::fixed('Overshark', 'Publicidad', '2x50', 50, [['SLIM FIT', 2]]),
            self::fixed('Overshark', 'Publicidad', '4x99', 99, [['WAFFLE MANGA LARGA', 4]]),
            self::fixed('Overshark', 'Publicidad', '7x99', 99, [['JERSEY MANGA LARGA', 7]]),
            self::fixed('Overshark', 'Publicidad', '3x50', 50, [['JERSEY MANGA LARGA', 3]]),
            self::fixed('Overshark', 'Publicidad', '3x99', 99, [['CAMISA WAFFLE', 3]]),
            self::fixed('Overshark', 'Publicidad', '4x99', 99, [['CUELLO CHINO WAFFLE', 4]]),
            self::fixed('Overshark', 'Publicidad', '5x99', 99, [['CUELLO CHINO', 5]]),

            self::fixed('Overshark', 'Promoción', 'Promo Waflera', 99, [['WAFFLE MANGA LARGA', 2], ['WAFFLE', 3]]),
            self::fixed('Overshark', 'Promoción', 'Mixtura', 99, [['CAMISERO PIKE', 2], ['WAFFLE', 2], ['CLASICO', 2]]),
            self::fixed('Overshark', 'Promoción', 'Promo Dúo', 99, [['CLASICO', 4], ['WAFFLE', 3]]),
            self::fixed('Overshark', 'Promoción', 'Promo Flash', 99, [['CAMISERO PIKE', 2], ['CAMISERO PIKE MANGA', 2]]),
            self::fixed('Overshark', 'Promoción', 'Promo Bandido', 99, [['WAFFLE', 3], ['SLIM FIT', 2]]),
            self::fixed('Overshark', 'Posventa', 'Old Money', 69, [['WAFFLE CAMISERO', 1], ['WAFFLE MANGA LARGA', 1], ['WAFFLE', 1]]),
            self::fixed('Overshark', 'Posventa', 'Pack 5 Waflero', 95, [['WAFFLE', 1], ['WAFFLE MANGA LARGA', 2], ['WAFFLE CAMISERO', 2]]),
            self::fixed('Overshark', 'Posventa', 'Combo Tiburón', 89, [['CAMISA WAFFLE', 2], ['WAFFLE', 1]]),
            self::fixed('Overshark', 'Cross-Selling', '2x44.9', 44.90, [['WAFFLE CAMISERO', 1], ['WAFFLE', 1]]),
            self::fixed('Overshark', 'Cross-Selling', '2x39.9', 39.90, [['CAMISERO PIKE', 1], ['WAFFLE', 1]]),

            self::choice('Girls', 'Live', '5x58', 58, 5, [
                'BABY TY', 'BABY TY ESCOTE', 'BABY TY MANGA', 'BABY TY ESCOTADO MANGA',
                'TOP RIB', 'TOP RIB MANGA', 'TOP RIB CERO',
            ]),
            self::choice('Girls', 'Promoción', 'Combo Diva', 99, 8, [
                'BABY TY', 'BABY TY ESCOTE', 'BABY TY MANGA', 'BABY TY ESCOTADO MANGA',
                'TOP RIB', 'TOP RIB MANGA', 'TOP RIB CERO',
            ]),

            self::fixed('Bravos', 'Live / Publicidad', '1x40', 40, [['POLERA NERU', 1]]),
            self::fixed('Bravos', 'Live / Publicidad', '2x70', 70, [['POLERA NERU', 2]]),
            self::fixed('Bravos', 'Live / Publicidad', '3x99', 99, [['POLERA NERU', 3]]),
            self::fixed('Bravos', 'Live / Publicidad', '4x120', 120, [['POLERA NERU', 4]]),
            self::fixed('Bravos', 'Live / Publicidad', '1x40', 40, [['POLERA BOXYFIT', 1]]),
            self::fixed('Bravos', 'Live / Publicidad', '2x60', 60, [['POLERA BOXYFIT', 2]]),
            self::fixed('Bravos', 'Live / Publicidad', '4x99', 99, [['POLERA BOXYFIT', 4]]),
            self::fixed('Bravos', 'Live / Publicidad', '1x35', 35, [['PANTALON BRATZ', 1]]),
            self::fixed('Bravos', 'Live / Publicidad', '3x99', 99, [['PANTALON BRATZ', 3]]),
            self::fixed('Bravos', 'Promoción', 'Combo Combinada', 99, [['POLERA NERU', 2], ['POLERA BOXYFIT', 1]]),
            self::empty('Bravos', 'Promoción', 'Combo Bravaza', 99, 2, 'Composición por confirmar.'),
            self::fixed('Bravos', 'Cross-Selling', '2x39.9', 39.90, [['POLERA NERU', 1]], 'Incluye una media; falta confirmar si es corta o larga.'),
            self::empty('Bravos', 'Cross-Selling', '2x69.9', 69.90, 2, 'Conjunto Neru; composición por confirmar.'),
        ];

        return array_map(function (array $row): array {
            $keyParts = [$row['brand'], $row['modality'], $row['name']];
            foreach ($row['items'] as $item) {
                $keyParts[] = $item['zazu'].'-'.$item['cantidad'];
            }

            $row['source_key'] = Str::slug(implode('-', $keyParts));

            return $row;
        }, $rows);
    }

    private static function fixed(string $brand, string $modality, string $name, float $price, array $items, ?string $notes = null): array
    {
        return [
            'brand' => $brand,
            'modality' => $modality,
            'name' => $name,
            'price' => $price,
            'items' => array_map(fn (array $item): array => ['zazu' => $item[0], 'cantidad' => $item[1]], $items),
            'selection_mode' => 'fixed',
            'selection_limit' => null,
            'notes' => $notes,
        ];
    }

    private static function choice(string $brand, string $modality, string $name, float $price, int $limit, array $zazuNames): array
    {
        return [
            'brand' => $brand,
            'modality' => $modality,
            'name' => $name,
            'price' => $price,
            'items' => array_map(fn (string $zazu): array => ['zazu' => $zazu, 'cantidad' => 1], $zazuNames),
            'selection_mode' => 'choice',
            'selection_limit' => $limit,
            'notes' => "El cliente elige {$limit} unidades entre los estilos disponibles.",
        ];
    }

    private static function empty(string $brand, string $modality, string $name, float $price, int $quantity, string $notes): array
    {
        return [
            'brand' => $brand,
            'modality' => $modality,
            'name' => $name,
            'price' => $price,
            'items' => [],
            'selection_mode' => 'fixed',
            'selection_limit' => $quantity,
            'notes' => $notes,
        ];
    }
}
