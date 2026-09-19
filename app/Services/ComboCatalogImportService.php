<?php

namespace App\Services;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Support\OversharkComboCatalog;
use Illuminate\Support\Facades\DB;

class ComboCatalogImportService
{
    public function import(): array
    {
        $created = 0;
        $updated = 0;
        $missing = [];

        DB::transaction(function () use (&$created, &$updated, &$missing): void {
            foreach (OversharkComboCatalog::all() as $definition) {
                $combo = Combo::query()->firstOrNew(['source_key' => $definition['source_key']]);
                $isNew = ! $combo->exists;

                $combo->fill([
                    'name' => $definition['name'],
                    'brand' => $definition['brand'],
                    'modality' => $definition['modality'],
                    'price' => $definition['price'],
                    'imagen' => $combo->imagen ?: 'images/default-hero-banner.png',
                    'items' => $definition['items'],
                    'selection_mode' => $definition['selection_mode'],
                    'selection_limit' => $definition['selection_limit'],
                    'notes' => $definition['notes'],
                    'status' => true,
                ])->save();

                $combo->update(['url' => '/combos/'.$combo->id]);

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }

                $pivot = [];

                foreach ($definition['items'] as $item) {
                    $product = Producto::query()
                        ->whereRaw('UPPER(TRIM(name)) = ?', [mb_strtoupper($item['zazu'])])
                        ->orderByDesc('stock')
                        ->orderBy('id')
                        ->first();

                    if (! $product) {
                        $missing[$item['zazu']] = $item['zazu'];
                        continue;
                    }

                    $pivot[$product->id] = ['cantidad' => $item['cantidad']];
                }

                $combo->productos()->sync($pivot);
            }
        });

        return [
            'total' => count(OversharkComboCatalog::all()),
            'created' => $created,
            'updated' => $updated,
            'missing' => array_values($missing),
        ];
    }
}
