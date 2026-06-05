<?php

namespace App\Services;

use App\Models\Admin\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OdooProductSyncService
{
    private ?int $uid = null;

    public function syncProducts(): array
    {
        $products = $this->executeKw(
            'product.product',
            'search_read',
            [$this->productDomain()],
            [
                'fields' => [
                    'id',
                    'product_tmpl_id',
                    'default_code',
                    'name',
                    'product_template_variant_value_ids',
                    'lst_price',
                    'standard_price',
                    'qty_available',
                    'write_date',
                ],
                'limit' => config('odoo.products_limit'),
                'context' => $this->productContext(),
                'order' => 'write_date desc',
            ],
        );

        $variantNames = $this->variantNames($products);
        $syncedAt = Carbon::now();
        $created = 0;
        $updated = 0;

        foreach ($products as $product) {
            $variantValues = $this->valuesForProduct($product, $variantNames);
            $attributes = $this->extractColorAndSize($variantValues);

            $local = Producto::query()->updateOrCreate(
                ['odoo_product_id' => $product['id']],
                [
                    'default_code' => $product['default_code'] ?: null,
                    'odoo_template_id' => $this->manyToOneId($product['product_tmpl_id'] ?? null),
                    'name' => $product['name'] ?: 'Producto Odoo '.$product['id'],
                    'variant_values' => $variantValues,
                    'color' => $attributes['color'],
                    'talla' => $attributes['talla'],
                    'stock' => max(0, (int) floor((float) ($product['qty_available'] ?? 0))),
                    'price' => (float) ($product['lst_price'] ?? 0),
                    'standard_price' => (float) ($product['standard_price'] ?? 0),
                    'qty_available' => (float) ($product['qty_available'] ?? 0),
                    'odoo_synced_at' => $syncedAt,
                ],
            );

            $local->wasRecentlyCreated ? $created++ : $updated++;
        }

        return [
            'total' => count($products),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    private function authenticate(): int
    {
        if ($this->uid !== null) {
            return $this->uid;
        }

        $uid = $this->jsonRpc('common', 'login', [
            config('odoo.database'),
            config('odoo.user'),
            config('odoo.api_key'),
        ]);

        if (! is_int($uid)) {
            throw new RuntimeException('No se pudo autenticar con Odoo.');
        }

        return $this->uid = $uid;
    }

    private function executeKw(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        return $this->jsonRpc('object', 'execute_kw', [
            config('odoo.database'),
            $this->authenticate(),
            config('odoo.api_key'),
            $model,
            $method,
            $args,
            $kwargs,
        ]);
    }

    private function jsonRpc(string $service, string $method, array $args): mixed
    {
        $baseUrl = config('odoo.base_url');

        if (! $baseUrl || ! config('odoo.database') || ! config('odoo.user') || ! config('odoo.api_key')) {
            throw new RuntimeException('Faltan credenciales Odoo en el archivo .env.');
        }

        $response = Http::timeout(config('odoo.timeout'))
            ->acceptJson()
            ->post($baseUrl.'/jsonrpc', [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => $service,
                    'method' => $method,
                    'args' => $args,
                ],
                'id' => time(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Odoo respondio con HTTP '.$response->status().'.');
        }

        $payload = $response->json();

        if (isset($payload['error'])) {
            $message = $payload['error']['data']['message'] ?? $payload['error']['message'] ?? 'Error desconocido de Odoo.';
            throw new RuntimeException($message);
        }

        return $payload['result'] ?? null;
    }

    private function productContext(): array
    {
        $context = [];

        if ($companyId = config('odoo.products_company_id')) {
            $context['company_id'] = $companyId;
            $context['allowed_company_ids'] = [$companyId];
        }

        if ($warehouseId = $this->warehouseId()) {
            $context['warehouse'] = $warehouseId;
        }

        return $context;
    }

    private function productDomain(): array
    {
        $domain = [];

        if ($companyId = config('odoo.products_company_id')) {
            $domain[] = ['company_id', '=', $companyId];
        }

        return $domain;
    }

    private function warehouseId(): ?int
    {
        $domain = [];

        if ($code = config('odoo.products_warehouse_code')) {
            $domain[] = ['code', '=', $code];
        } elseif ($name = config('odoo.products_warehouse_name')) {
            $domain[] = ['name', '=', $name];
        }

        if ($domain === []) {
            return null;
        }

        $warehouses = $this->executeKw('stock.warehouse', 'search_read', [$domain], [
            'fields' => ['id'],
            'limit' => 1,
        ]);

        return $warehouses[0]['id'] ?? null;
    }

    private function variantNames(array $products): array
    {
        $ids = collect($products)
            ->flatMap(fn (array $product) => $product['product_template_variant_value_ids'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $values = $this->executeKw('product.template.attribute.value', 'read', [$ids], [
            'fields' => ['id', 'display_name', 'name', 'attribute_id'],
        ]);

        return collect($values)->keyBy('id')->all();
    }

    private function valuesForProduct(array $product, array $variantNames): array
    {
        return collect($product['product_template_variant_value_ids'] ?? [])
            ->map(fn (int $id) => $variantNames[$id] ?? null)
            ->filter()
            ->map(function (array $value) {
                $attribute = is_array($value['attribute_id'] ?? null)
                    ? ($value['attribute_id'][1] ?? null)
                    : null;

                return [
                    'attribute' => $attribute,
                    'value' => $value['name'] ?? $value['display_name'] ?? null,
                    'display_name' => $value['display_name'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function extractColorAndSize(array $variantValues): array
    {
        $color = null;
        $talla = null;

        foreach ($variantValues as $value) {
            $attribute = mb_strtolower((string) ($value['attribute'] ?? ''));
            $name = $value['value'] ?? $value['display_name'] ?? null;

            if ($name === null) {
                continue;
            }

            if ($color === null && str_contains($attribute, 'color')) {
                $color = $name;
            }

            if ($talla === null && (str_contains($attribute, 'talla') || str_contains($attribute, 'size'))) {
                $talla = $name;
            }
        }

        return ['color' => $color, 'talla' => $talla];
    }

    private function manyToOneId(mixed $value): ?int
    {
        if (is_array($value)) {
            return isset($value[0]) ? (int) $value[0] : null;
        }

        return $value ? (int) $value : null;
    }
}
