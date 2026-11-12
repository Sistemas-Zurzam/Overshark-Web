<?php

namespace App\Services\Crm;

use App\Models\Admin\Order;
use App\Models\Admin\Producto;
use App\Support\Shipping;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Venta del pedido web en CRM-LIVEX-V2 (POST /api/v1/sales con la clave API de la empresa, permiso
 * sales.write). El CRM es quien registra las ventas: le asigna la nota de venta NV-00014 (la misma serie
 * que sus otras ventas y su agente IA), descuenta su stock, la envía a Zazu según el tipo de entrega, la
 * registra en el ERP y emite la boleta.
 *
 * La venta va con source = web y external_reference = código del pedido (OS-000123): reenviarla devuelve la
 * misma venta. Los productos van por SKU; un pedido con prendas de otra empresa (Overshark y Bravos) marca
 * esas líneas como Cross-Selling, como en el CRM.
 */
class CrmSales
{
    /** Nombre del método de pago en el CRM para cada medio de la web (el resto se llama igual: Yape, Plin…). */
    private const METHODS = ['Tarjeta de crédito o débito' => 'Tarjeta'];

    public static function isEnabled(): bool
    {
        return (bool) config('services.crm.send_sales')
            && filled(config('services.crm.base_url'))
            && filled(config('services.crm.api_key'));
    }

    /** La venta con el contrato de la API v1 del CRM. */
    public function payload(Order $order): array
    {
        $order->loadMissing('distrito.provincia.departamento', 'tipoDocumento');

        return array_filter([
            'source' => 'web',
            'external_reference' => $order->number_zazu,
            'customer' => array_filter([
                'name' => $order->cliente_name,
                'phone' => $order->cliente_cel,
                'document_type' => $order->tipoDocumento?->code ?? 'DNI',
                'document' => $order->cliente_doc_identidad,
            ]),
            'delivery' => array_filter($this->delivery($order), fn ($value) => $value !== null && $value !== ''),
            'shipping_date' => $order->entrega === 'domicilio' ? $order->fecha_envio?->format('Y-m-d') : null,
            'items' => $this->items($order),
            'shipping_cost' => (float) $order->envio,
            'payments' => $this->payments($order),
            'notes' => trim("Pedido web {$order->number_zazu} · {$order->metodo_pago_nombre}"
                .($order->tipo_comprobante === 'factura' ? " · Factura RUC {$order->ruc} {$order->razon_social}" : '')),
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * Registra la venta en el CRM. Lanza una excepción si el CRM no responde o la rechaza (la cola reintenta
     * y el admin ve el motivo).
     *
     * @return array{id: int, number: string, status: ?string, status_label: ?string, payment_status: ?string, zazu: ?array}
     */
    public function send(Order $order): array
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('services.crm.base_url'), '/'))
                ->withToken((string) config('services.crm.api_key'))
                ->timeout((int) config('services.crm.timeout', 30))
                ->acceptJson()
                ->post('/api/v1/sales', $this->payload($order));
        } catch (ConnectionException $exception) {
            throw new RuntimeException('El CRM no responde: '.$exception->getMessage());
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException('El CRM rechazó la clave API: revisa CRM_API_KEY (necesita el permiso sales.write).');
        }
        if (! $response->successful() || blank($response->json('number'))) {
            throw new RuntimeException('El CRM respondió '.$response->status().': '.($response->json('message') ?? mb_substr($response->body(), 0, 200)));
        }

        return [
            'id' => (int) $response->json('id'),
            'number' => (string) $response->json('number'),
            'status' => $response->json('status'),
            'status_label' => $response->json('status_label'),
            'payment_status' => $response->json('payment_status'),
            'zazu' => $response->json('zazu'),
        ];
    }

    private function delivery(Order $order): array
    {
        $distrito = $order->distrito;
        $place = [
            'district' => $distrito?->name ? mb_strtoupper($distrito->name) : null,
            'province' => $distrito?->provincia?->name ? mb_strtoupper($distrito->provincia->name) : null,
            'department' => $distrito?->provincia?->departamento?->name ? mb_strtoupper($distrito->provincia->departamento->name) : null,
        ];

        return match ($order->entrega) {
            'agencia' => ['type' => 'agencia', 'shalom_agency_id' => $order->agencia_shalom_id, 'agency_name' => preg_replace('/^Shalom\s+/u', '', (string) $order->agencia)] + $place,
            Shipping::PICKUP => ['type' => 'recojo'],
            default => ['type' => 'delivery', 'address' => $order->direccion, 'reference' => $order->referencia] + $place,
        };
    }

    /**
     * Prendas por SKU (los combos se abren en sus prendas con el precio repartido). Las de una empresa distinta
     * a la principal (la de mayor importe) van como Cross-Selling.
     */
    private function items(Order $order): array
    {
        $units = [];
        foreach ($order->productos_json ?? [] as $line) {
            $qty = max(1, (int) ($line['cantidad'] ?? 1));
            if (($line['tipo'] ?? null) === 'combo') {
                $selections = array_values($line['selecciones'] ?? []);
                $count = max(1, count($selections));
                $unitPrice = round((float) $line['precio'] / $count, 2);
                foreach ($selections as $i => $selection) {
                    // El último ajusta los céntimos para que el combo sume exacto.
                    $price = $i === $count - 1 ? round((float) $line['precio'] - $unitPrice * ($count - 1), 2) : $unitPrice;
                    $units[] = ['variant_id' => $selection['variant_id'] ?? null, 'price' => $price, 'quantity' => $qty];
                }
            } else {
                $units[] = ['variant_id' => $line['variant_id'] ?? null, 'price' => (float) ($line['precio'] ?? 0), 'quantity' => $qty];
            }
        }

        $variants = Producto::query()->whereIn('id', array_filter(array_column($units, 'variant_id')))->get()->keyBy('id');
        $lines = collect($units)
            ->map(function (array $unit) use ($variants) {
                $variant = $variants->get($unit['variant_id']);
                if (! $variant || blank($variant->default_code)) {
                    throw new RuntimeException("La prenda {$unit['variant_id']} del pedido ya no existe o no tiene SKU.");
                }

                return [
                    'sku' => $variant->default_code,
                    'quantity' => $unit['quantity'],
                    'unit_price' => $unit['price'],
                    'empresa' => mb_strtoupper(trim((string) ($variant->empresa_nombre ?: 'OVERSHARK PERU S.A.C'))),
                ];
            })
            // La misma prenda al mismo precio va en una sola línea.
            ->groupBy(fn (array $line) => $line['sku'].'|'.$line['unit_price'])
            ->map(fn (Collection $same) => [...$same->first(), 'quantity' => $same->sum('quantity')])
            ->values();

        $main = $lines->groupBy('empresa')->map(fn (Collection $group) => $group->sum(fn ($line) => $line['unit_price'] * $line['quantity']))->sortDesc()->keys()->first();

        return $lines->map(fn (array $line) => [
            'sku' => $line['sku'],
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'cross' => $line['empresa'] !== $main,
        ])->all();
    }

    /** Lo pagado en línea (el total o el separo) con el nombre del método en el CRM; contraentrega sin separo: nada. */
    private function payments(Order $order): array
    {
        $amount = (float) $order->pago_monto;
        if ($amount <= 0) {
            return [];
        }

        $name = $order->isCashOnDelivery() ? trim((string) str($order->metodo_pago_nombre)->after('separo con ')) : (string) $order->metodo_pago_nombre;

        return [[
            'method' => self::METHODS[$name] ?? $name,
            'amount' => $amount,
            // Nº de operación: el de la transacción de Izipay o, en una captura validada a mano, el código del pedido.
            'reference' => $order->pago_referencia ?: "Web {$order->number_zazu}",
            'paid_at' => $order->updated_at?->toDateString(),
        ]];
    }
}
