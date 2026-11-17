<?php

namespace App\Services\Integrations;

use App\Models\Admin\Order;

/** Un pedido tal como lo ven los sistemas externos (API /api/v1/orders y webhooks). */
class OrderPayload
{
    public static function from(Order $order): array
    {
        $order->loadMissing(['statusOrder', 'distrito.provincia.departamento', 'cliente.tipoDocumento']);
        $money = fn ($value) => $value === null ? null : round((float) $value, 2);

        return [
            'id' => $order->id,
            'number' => $order->displayNumber(),
            'web_number' => $order->number_zazu,
            'code' => $order->code(),
            'sales_note' => $order->nota_venta,
            'status' => $order->statusName(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'customer' => [
                'name' => $order->cliente_name,
                'email' => $order->email,
                'phone' => $order->cliente_cel,
                'document_type' => $order->cliente?->tipoDocumento?->code,
                'document_number' => $order->cliente_doc_identidad ?: $order->cliente?->documento_identidad,
            ],
            'delivery' => [
                'type' => $order->entrega,
                'address' => $order->direccion,
                'reference' => $order->referencia,
                'district' => $order->distrito?->name,
                'province' => $order->distrito?->provincia?->name,
                'department' => $order->distrito?->provincia?->departamento?->name,
                'agency' => $order->agencia,
                'shipping_date' => $order->fecha_envio?->toDateString(),
            ],
            'invoice' => [
                'type' => $order->tipo_comprobante,
                'ruc' => $order->ruc,
                'business_name' => $order->razon_social,
            ],
            'payment' => [
                'method' => $order->metodo_pago_nombre,
                'gateway' => $order->payment_gateway,
                'reference' => $order->pago_referencia,
                'amount_paid' => $money($order->pago_monto),
                'cash_on_delivery_deposit' => $money($order->separo),
            ],
            'totals' => [
                'currency' => 'PEN',
                'subtotal' => $money($order->subtotal),
                'shipping' => $money($order->envio),
                'total' => $money($order->precio_total),
            ],
            'items' => collect($order->productos_json ?? [])->map(fn (array $line) => [
                'type' => $line['tipo'] ?? 'producto',
                'name' => $line['producto'] ?? null,
                'variant_id' => $line['variant_id'] ?? null,
                'combo_id' => $line['combo_id'] ?? null,
                'color' => $line['color'] ?? null,
                'size' => $line['talla'] ?? null,
                'quantity' => (int) ($line['cantidad'] ?? 1),
                'unit_price' => $money($line['precio'] ?? 0),
                'selections' => collect($line['selecciones'] ?? [])->map(fn (array $item) => [
                    'name' => $item['producto'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'color' => $item['color'] ?? null,
                    'size' => $item['talla'] ?? null,
                ])->values()->all(),
            ])->values()->all(),
            'fraud_alert' => $order->hasOpenFraudAlert(),
        ];
    }
}
