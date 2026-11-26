<?php

namespace App\Support;

use App\Models\Admin\Combo;
use App\Models\Admin\Producto;
use App\Services\ComboSelectionService;
use Illuminate\Support\Collection;

class ComboOffers
{
    public function __construct(private ComboSelectionService $selector) {}

    public function forProduct(Producto $product): Collection
    {
        return $this->candidates(collect([$product]))
            ->filter(fn (Combo $combo) => $this->plan($combo, []) !== null)
            ->take(3)
            ->values();
    }

    public function forCart(?Producto $product = null): Collection
    {
        $items = session('cart.items', []);
        $ids = collect($items)->filter(fn (array $line) => ($line['kind'] ?? null) !== 'combo')
            ->map(fn (array $line, $key) => (int) ($line['variant_id'] ?? $key))->filter()->unique();
        $anchors = $product ? collect([$product]) : Producto::query()->whereIn('id', $ids)->get();

        return $this->candidates($anchors)->map(fn (Combo $combo) => $this->plan($combo, $items))
            ->filter(fn (?array $offer) => $offer && $offer['have'] > 0 && $offer['savings'] > 0)
            ->sortBy([['missing', 'asc'], ['savings', 'desc']])
            ->unique(fn (array $offer) => $this->brand($offer['combo']->brand))
            ->values();
    }

    private function candidates(Collection $products): Collection
    {
        if ($products->isEmpty()) {
            return collect();
        }

        $namesByBrand = $products->groupBy(fn (Producto $product) => GirlsHome::isGirls($product->name, $product->marca)
            ? 'GIRLS' : $this->brand(CartSuggestions::brandOf($product)))
            ->map(fn (Collection $group) => $group->map(fn (Producto $product) => $this->normalize($product->name))->all());

        return Combo::query()->where('status', true)->where('is_draft', false)->where('price', '>', 0)
            ->where(fn ($query) => $query->whereNull('modality')->orWhere('modality', 'Publicidad'))
            ->orderBy('id')->get()
            ->filter(function (Combo $combo) use ($namesByBrand): bool {
                $names = $namesByBrand->get($this->brand($combo->brand), []);

                return $combo->displayItems()->contains(fn (array $item) =>
                    in_array($this->normalize($item['zazu'] ?? ''), $names, true));
            });
    }

    private function normalize(string $value): string
    {
        return mb_strtoupper(trim($value));
    }

    private function brand(?string $value): string
    {
        $name = $this->normalize($value ?? '');

        return match (true) {
            str_contains($name, 'GIRL') => 'GIRLS',
            str_contains($name, 'BRAVOS') => 'BRAVOS',
            str_contains($name, 'OVERSHARK') => 'OVERSHARK',
            default => $name,
        };
    }

    public function plan(Combo $combo, array $items, ?Collection $slots = null): ?array
    {
        if (! $combo->status || $combo->is_draft || (float) $combo->price <= 0) {
            return null;
        }

        $slots ??= $this->selector->slots($combo);
        $units = $slots->flatMap(fn (array $slot) =>
            array_fill(0, $slot['quantity'], $slot['variants']->pluck('id')->all()))->values()->all();

        if ($units === [] || in_array([], $units, true)) {
            return null;
        }

        $variants = $slots->flatMap(fn (array $slot) => $slot['variants'])->keyBy('id');
        $held = collect($items)->filter(fn (array $line) => ($line['kind'] ?? null) !== 'combo')
            ->keyBy(fn (array $line, $key) => (int) ($line['variant_id'] ?? $key));
        $reserved = self::quantities($items);
        $heldOptions = [];
        $allOptions = [];
        $prices = [];

        foreach ($variants as $id => $variant) {
            $existing = (int) ($held->get($id)['qty'] ?? 0);
            $capacity = max(0, min(count($units), (int) $variant->stock - ($reserved[$id] ?? 0) + $existing));
            if ($capacity === 0) {
                continue;
            }

            $prices[$id] = (float) Promotions::priceFor($variant);
            for ($i = 0; $i < $capacity; $i++) {
                $seat = $id.':'.$i;
                $allOptions[$id][] = $seat;
                if ($i < $existing) {
                    $heldOptions[$id][] = $seat;
                }
            }
        }

        $owners = [];
        foreach ([$heldOptions, $allOptions] as $options) {
            $filled = array_flip($owners);
            foreach ($units as $index => $eligible) {
                if (isset($filled[$index])) {
                    continue;
                }

                $seen = [];
                $this->assign($index, $units, $options, $owners, $seen);
            }
        }
        if (count($owners) !== count($units)) {
            return null;
        }

        $prefill = $consume = $included = [];
        $before = $extra = 0.0;
        foreach ($owners as $seat => $unit) {
            [$id, $number] = array_map('intval', explode(':', $seat));
            $prefill[$unit] = $id;
            if ($number < (int) ($held->get($id)['qty'] ?? 0)) {
                $consume[$id] = ($consume[$id] ?? 0) + 1;
                $included[$unit] = true;
                $before += $prices[$id];
            } else {
                $extra += $prices[$id];
            }
        }

        $have = array_sum($consume);
        $delta = round((float) $combo->price - $before, 2);
        ksort($consume);

        return [
            'combo' => $combo,
            'prefill' => $prefill,
            'included' => $included,
            'consume' => $consume,
            'have' => $have,
            'missing' => count($units) - $have,
            'delta' => $delta,
            'quote' => hash('sha256', json_encode([$combo->id, $consume, (float) $combo->price, $delta], JSON_THROW_ON_ERROR)),
            'savings' => round($before + $extra - (float) $combo->price, 2),
        ];
    }

    private function assign(int $unit, array $units, array $options, array &$owners, array &$seen): bool
    {
        foreach ($units[$unit] as $id) {
            foreach ($options[$id] ?? [] as $seat) {
                if (isset($seen[$seat])) {
                    continue;
                }
                $seen[$seat] = true;
                if (! isset($owners[$seat]) || $this->assign($owners[$seat], $units, $options, $owners, $seen)) {
                    $owners[$seat] = $unit;

                    return true;
                }
            }
        }

        return false;
    }

    public static function quantities(array $items): array
    {
        $quantities = [];
        foreach ($items as $key => $item) {
            $quantity = (int) ($item['qty'] ?? 0);
            $variants = ($item['kind'] ?? null) === 'combo'
                ? array_column($item['selections'] ?? [], 'variant_id')
                : [$item['variant_id'] ?? $key];
            foreach ($variants as $variantId) {
                $quantities[$variantId] = ($quantities[$variantId] ?? 0) + $quantity;
            }
        }

        return $quantities;
    }
}
