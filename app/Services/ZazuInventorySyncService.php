<?php

namespace App\Services;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ZazuInventorySyncService
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.zazu.base_url'), '/');
        $token = trim((string) config('services.zazu.token'));

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Faltan ZAZU_BASE_URL o ZAZU_INVENTORY_TOKEN en el archivo .env.');
        }

        $items = $this->fetchAll($this->client($baseUrl, $token));
        $seenKeys = [];
        $synced = 0;
        $staleZeroed = 0;
        $foreignRemoved = 0;

        DB::transaction(function () use ($items, &$seenKeys, &$synced, &$staleZeroed, &$foreignRemoved): void {
            foreach ($items as $item) {
                $mapped = $this->mapItem($item);
                if ($mapped === null) {
                    continue;
                }

                $seenKeys[] = $mapped['zazu_source_key'];
                $this->upsertProduct($mapped);
                $synced++;
            }

            // Lo de otras empresas (BOX PRIME) no se vende ni se muestra: se borra en vez de dejarlo con stock 0.
            $foreignRemoved = Producto::removeForeignCompanies();
            $staleZeroed = $this->zeroStaleProducts($seenKeys);
        });

        return [
            'received' => count($items),
            'synced' => $synced,
            'stale_zeroed' => $staleZeroed,
            'foreign_removed' => $foreignRemoved,
        ];
    }

    private function client(string $baseUrl, string $token): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withToken($token)
            ->connectTimeout(10)
            ->timeout((int) config('services.zazu.timeout', 30));
    }

    private function fetchAll(PendingRequest $client): array
    {
        $items = [];
        $page = 1;

        do {
            $response = $client->get((string) config('services.zazu.inventory_path'), [
                'page' => $page,
                'per_page' => 500,
            ])->throw()->json();

            $pageItems = $response['data'] ?? [];
            if (! is_array($pageItems)) {
                throw new RuntimeException('Zazu devolvio un formato de inventario invalido.');
            }

            array_push($items, ...$pageItems);
            $lastPage = (int) ($response['last_page'] ?? $page);
            $page++;
        } while ($page <= $lastPage);

        return $items;
    }

    private function mapItem(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $productId = (int) ($item['zazu_product_id'] ?? data_get($item, 'producto.id', 0));
        if ($productId < 1) {
            return null;
        }

        $variantId = isset($item['zazu_variant_id']) && $item['zazu_variant_id'] !== null
            ? (int) $item['zazu_variant_id']
            : null;
        $sku = trim((string) ($item['sku'] ?? $item['sku_variante'] ?? $item['sku_general'] ?? ''));
        if ($sku === '') {
            return null;
        }

        $company = is_array($item['empresa'] ?? null) ? $item['empresa'] : [];
        $companyName = trim((string) ($company['nombre'] ?? $item['company_name'] ?? ''));
        if (! $this->sellsCompany($companyName)) {
            return null;
        }

        $sourceKey = $variantId !== null
            ? "variante:{$variantId}"
            : "producto:{$productId}";

        return [
            'zazu_source_key' => $sourceKey,
            'zazu_product_id' => $productId,
            'zazu_variant_id' => $variantId,
            'zazu_company_id' => isset($company['id']) ? (int) $company['id'] : null,
            'empresa_nombre' => $companyName ?: null,
            'name' => trim((string) ($item['nombre'] ?? data_get($item, 'producto.nombre', ''))),
            'default_code' => $sku,
            'color' => $this->nullableString($item['color'] ?? null),
            'talla' => $this->nullableString($item['talla'] ?? null),
            'stock' => max(0, (int) round((float) ($item['disponible_venta'] ?? $item['stock_disponible'] ?? $item['stock'] ?? 0))),
            'price' => $this->price($item),
            'standard_price' => isset($item['costo']) ? (float) $item['costo'] : null,
            'imagen' => $this->imageUrl($item['imagen'] ?? null),
            'categoria' => $this->nullableString($item['categoria'] ?? null),
            'zazu_synced_at' => now(),
        ];
    }

    /**
     * Solo las empresas de ZAZU_COMPANIES; sin lista configurada se aceptan todas. Un ítem sin empresa
     * se acepta, para que un cambio de formato en Zazu no deje todo el catálogo con stock 0.
     */
    private function sellsCompany(string $name): bool
    {
        $allowed = (array) config('services.zazu.companies', []);

        return $allowed === [] || $name === ''
            || collect($allowed)->contains(fn (string $part): bool => Str::contains(mb_strtoupper($name), mb_strtoupper($part)));
    }

    private function upsertProduct(array $mapped): void
    {
        $categoryId = null;
        if ($mapped['categoria']) {
            $categoryId = Categoria::query()->firstOrCreate(['name' => $mapped['categoria']])->id;
        }

        $product = Producto::query()->firstOrNew(['zazu_source_key' => $mapped['zazu_source_key']]);
        $product->fill([
            'categoria_id' => $categoryId,
            'default_code' => $mapped['default_code'],
            'name' => $mapped['name'] ?: $mapped['default_code'],
            'color' => $mapped['color'],
            'talla' => $mapped['talla'],
            'stock' => $mapped['stock'],
            'price' => $mapped['price'],
            'standard_price' => $mapped['standard_price'],
            'imagen' => $mapped['imagen'] ?: $product->imagen,
            'zazu_product_id' => $mapped['zazu_product_id'],
            'zazu_variant_id' => $mapped['zazu_variant_id'],
            'zazu_company_id' => $mapped['zazu_company_id'],
            'empresa_nombre' => $mapped['empresa_nombre'],
            'zazu_synced_at' => $mapped['zazu_synced_at'],
        ])->save();
    }

    private function zeroStaleProducts(array $seenKeys): int
    {
        return Producto::query()
            ->whereNotNull('zazu_source_key')
            ->when($seenKeys !== [], fn ($query) => $query->whereNotIn('zazu_source_key', $seenKeys))
            ->update([
                'stock' => 0,
                'zazu_synced_at' => now(),
            ]);
    }

    /**
     * Precio de venta de la variante; si la variante no tiene uno propio (vacío o 0), el del producto
     * (en Zazu el precio suele estar solo en la ficha del producto, p. ej. BABY TY a S/ 17).
     */
    private function price(array $item): float
    {
        foreach ([$item['precio_venta'] ?? null, $item['precio_variante'] ?? null, data_get($item, 'producto.precio_venta'), $item['precio_producto'] ?? null, $item['precio'] ?? null] as $price) {
            if (is_numeric($price) && (float) $price > 0) {
                return (float) $price;
            }
        }

        return 0.0;
    }

    private function imageUrl(mixed $value): ?string
    {
        $image = trim((string) $value);
        if ($image === '') {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        return rtrim((string) config('services.zazu.base_url'), '/').'/'.ltrim($image, '/');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
