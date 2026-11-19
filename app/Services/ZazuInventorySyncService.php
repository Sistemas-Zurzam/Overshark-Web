<?php

namespace App\Services;

use App\Models\Admin\Categoria;
use App\Models\Admin\Producto;
use App\Support\Integrations;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ZazuInventorySyncService
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.zazu.base_url'), '/');
        ['secret' => $secret, 'keys' => $keys] = Integrations::zazuCredentials();

        if ($baseUrl === '' || ($secret === null && $keys === [])) {
            throw new RuntimeException('Faltan la URL o el token de Zazu (Admin → Integraciones o ZAZU_BASE_URL / ZAZU_INVENTORY_TOKEN en el .env).');
        }

        // Con API keys cada una trae solo el stock de su empresa; sin ellas, el token del servidor trae todas.
        [$items, $keyCompanies] = $keys !== []
            ? $this->fetchWithApiKeys($baseUrl, $keys)
            : [$this->fetchAll($this->client($baseUrl)->withToken($secret)), null];

        $mappedItems = array_values(array_filter(array_map(fn ($item) => $this->mapItem($item), $items)));

        // Sin productos no se toca nada: poner en 0 «lo que Zazu ya no envía» dejaría agotada toda la tienda por una
        // URL o un token equivocados.
        if ($items === []) {
            throw new RuntimeException('Zazu no devolvió productos; no se tocó el stock de la tienda. Revisa la URL y el token.');
        }
        if ($mappedItems === []) {
            throw new RuntimeException('Ninguno de los '.count($items).' productos recibidos es de las empresas que vende la tienda ('
                .implode(', ', (array) config('services.zazu.companies')).'); no se tocó el stock.'
                .($keyCompanies ? ' Las API keys son de: '.implode(', ', $keyCompanies).'.' : ''));
        }

        $seenKeys = [];
        $synced = 0;
        $staleZeroed = 0;
        $foreignRemoved = 0;

        DB::transaction(function () use ($mappedItems, $keyCompanies, &$seenKeys, &$synced, &$staleZeroed, &$foreignRemoved): void {
            foreach ($mappedItems as $mapped) {
                $seenKeys[] = $mapped['zazu_source_key'];
                $this->upsertProduct($mapped);
                $synced++;
            }

            // Lo de otras empresas (BOX PRIME) no se vende ni se muestra: se borra en vez de dejarlo con stock 0.
            $foreignRemoved = Producto::removeForeignCompanies();
            $staleZeroed = $this->zeroStaleProducts($seenKeys, $keyCompanies);
        });

        return [
            'received' => count($items),
            'synced' => $synced,
            'stale_zeroed' => $staleZeroed,
            'foreign_removed' => $foreignRemoved,
            'companies' => $keyCompanies,
        ];
    }

    private function client(string $baseUrl): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout((int) config('services.zazu.timeout', 30));
    }

    private function fetchAll(PendingRequest $client): array
    {
        $items = [];
        $page = 1;

        do {
            $response = $this->json($client->get((string) config('services.zazu.inventory_path'), [
                'page' => $page,
                'per_page' => 500,
            ]), 'el token');

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

    /**
     * Una API key por empresa (Zazu → API Keys): /api/v1/ping dice de qué empresa es y /api/v1/existencias trae su stock
     * por SKU (producto o variante) con lo reservado ya descontado. Se pasa al mismo formato del inventario del servidor.
     *
     * @return array{0: list<array>, 1: list<string>} ítems y nombres de las empresas de las claves
     */
    private function fetchWithApiKeys(string $baseUrl, array $keys): array
    {
        $items = [];
        $companies = [];

        foreach ($keys as $key) {
            $client = $this->client($baseUrl)->withHeaders(['X-Api-Key' => $key]);
            $label = 'la API key '.Integrations::maskZazuApiKey($key);
            $ping = $this->json($client->get('/api/v1/ping'), $label);
            $company = ['id' => (int) data_get($ping, 'empresa.id'), 'nombre' => trim((string) data_get($ping, 'empresa.nombre'))];
            $companies[] = $company['nombre'];

            $page = 1;
            do {
                $response = $this->json($client->get('/api/v1/existencias', ['page' => $page, 'per_page' => 200]), $label);
                $rows = $response['data'] ?? null;
                if (! is_array($rows)) {
                    throw new RuntimeException('Zazu devolvio un formato de existencias invalido.');
                }

                foreach ($rows as $row) {
                    $items[] = is_array($row) ? [
                        'zazu_product_id' => $row['producto_id'] ?? null,
                        'zazu_variant_id' => $row['variante_id'] ?? null,
                        'sku' => $row['sku'] ?? null,
                        'nombre' => $row['nombre'] ?? null,
                        'categoria' => $row['categoria'] ?? null,
                        'talla' => $row['talla'] ?? null,
                        'color' => $row['color'] ?? null,
                        'precio_venta' => $row['precio_venta'] ?? null,
                        'imagen' => $row['imagen_url'] ?? null,
                        'disponible_venta' => $row['stock_disponible'] ?? 0,
                        'empresa' => $company,
                    ] : $row;
                }

                $lastPage = (int) data_get($response, 'meta.last_page', $page);
                $page++;
            } while ($page <= $lastPage);
        }

        return [$items, array_values(array_unique($companies))];
    }

    /** JSON de Zazu, o un error claro: credencial rechazada o URL que responde la página web en vez de la API. */
    private function json(Response $response, string $credential): array
    {
        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException("Zazu rechazó {$credential} (HTTP {$response->status()}).");
        }
        $response->throw();

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException(Integrations::ZAZU_NOT_API);
        }

        return $json;
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
        return Integrations::storeSellsCompany($name);
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
            // Un precio 0 o un costo ausente nunca pisan los guardados: la tienda no debe vender a S/ 0.
            'price' => $mapped['price'] > 0 ? $mapped['price'] : ($product->price ?: 0),
            'standard_price' => $mapped['standard_price'] ?? $product->standard_price,
            'imagen' => $mapped['imagen'] ?: $product->imagen,
            'zazu_product_id' => $mapped['zazu_product_id'],
            'zazu_variant_id' => $mapped['zazu_variant_id'],
            'zazu_company_id' => $mapped['zazu_company_id'],
            'empresa_nombre' => $mapped['empresa_nombre'],
            'zazu_synced_at' => $mapped['zazu_synced_at'],
        ])->save();
    }

    /**
     * Lo que Zazu ya no envía queda con stock 0. Con API keys solo se revisan las empresas de esas claves: sin la clave de
     * Bravos no se sabe nada de su stock, y no por eso se agota.
     */
    private function zeroStaleProducts(array $seenKeys, ?array $companies): int
    {
        return Producto::query()
            ->whereNotNull('zazu_source_key')
            ->whereNotIn('zazu_source_key', $seenKeys)
            ->when($companies !== null, fn ($query) => $query->whereIn(
                DB::raw('UPPER(empresa_nombre)'),
                array_map(fn (string $name): string => mb_strtoupper($name), $companies ?: ['']),
            ))
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
