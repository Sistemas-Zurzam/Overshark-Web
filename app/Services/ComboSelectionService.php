<?php

namespace App\Services;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ComboSelectionService
{
    public function slots(Combo $combo): Collection
    {
        $relatedProducts = $combo->productos()
            ->get()
            ->keyBy(fn (Producto $product): string => $this->normalize($product->name));

        $groups = $combo->displayItems()->map(function (array $item) use ($relatedProducts): array {
            $name = trim((string) ($item['zazu'] ?? ''));
            $baseProduct = $relatedProducts->get($this->normalize($name));

            return [
                'name' => $name,
                'quantity' => max(1, (int) ($item['cantidad'] ?? 1)),
                'variants' => $this->availableVariants($name, $baseProduct),
            ];
        })->filter(fn (array $group): bool => $group['name'] !== '');

        $groups = $groups
            ->groupBy(fn (array $group): string => $this->normalize($group['name']))
            ->map(fn (Collection $sameNameGroups): array => [
                'name' => $sameNameGroups->first()['name'],
                'quantity' => $sameNameGroups->sum('quantity'),
                'variants' => $sameNameGroups
                    ->flatMap(fn (array $group): Collection => $group['variants'])
                    ->unique('id')
                    ->values(),
            ])
            ->values();

        if ($combo->selection_mode === 'choice') {
            $variants = $groups
                ->flatMap(fn (array $group): Collection => $group['variants'])
                ->unique('id')
                ->values();
            $quantity = max(1, (int) ($combo->selection_limit ?: $groups->count()));

            return collect(range(1, $quantity))->map(fn (int $unit): array => [
                'label' => "Unidad {$unit}",
                'product' => 'Elige un producto',
                'allow_product_choice' => true,
                'quantity' => 1,
                'variants' => $variants,
            ]);
        }

        return $groups->map(fn (array $group): array => [
            'label' => $group['name'],
            'product' => $group['name'],
            'allow_product_choice' => false,
            'quantity' => $group['quantity'],
            'variants' => $group['variants'],
        ])->values();
    }

    public function resolveSelections(Combo $combo, array $selectedIds): Collection
    {
        $slots = $this->slots($combo);

        if ($slots->isEmpty()) {
            throw ValidationException::withMessages([
                'selections' => 'Este combo todavía no tiene productos disponibles para elegir.',
            ]);
        }

        $selectionSlots = $slots->flatMap(fn (array $slot, int $slotIndex): Collection => collect(range(1, max(1, (int) ($slot['quantity'] ?? 1))))
            ->map(fn (): int => $slotIndex))
            ->values();

        if (count($selectedIds) !== $selectionSlots->count()) {
            throw ValidationException::withMessages([
                'selections' => 'Selecciona talla y color para cada unidad del combo.',
            ]);
        }

        $selections = collect($selectedIds)->values()->map(function ($selectedId, int $index) use ($slots, $selectionSlots): Producto {
            $slotIndex = $selectionSlots[$index];
            $variant = $slots[$slotIndex]['variants']->firstWhere('id', (int) $selectedId);

            if (! $variant) {
                throw ValidationException::withMessages([
                    "selections.{$index}" => 'La variante elegida no está disponible para este combo.',
                ]);
            }

            return $variant;
        })->values();

        foreach ($selections->countBy(fn (Producto $variant): int => $variant->id) as $variantId => $quantity) {
            $variant = $selections->firstWhere('id', (int) $variantId);

            if ($quantity > (int) $variant->stock) {
                throw ValidationException::withMessages([
                    'selections' => "No hay stock suficiente para {$variant->name} en la combinación seleccionada.",
                ]);
            }
        }

        return $selections;
    }

    private function availableVariants(string $name, ?Producto $baseProduct): Collection
    {
        return Producto::query()
            ->whereRaw('UPPER(TRIM(name)) = ?', [$this->normalize($name)])
            ->when(
                $baseProduct?->zazu_company_id !== null,
                fn ($query) => $query->where('zazu_company_id', $baseProduct->zazu_company_id),
            )
            ->where('price', '>', 0)
            ->where('stock', '>', 0)
            ->whereNotNull('talla')
            ->whereNotNull('color')
            ->orderBy('talla')
            ->orderBy('color')
            ->get();
    }

    private function normalize(?string $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }
}
