<?php

namespace App\Services\Orders\Tasks;

use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use App\Services\Crm\CrmSales;
use App\Services\Payments\PaymentSimulator;

/**
 * Registra el pedido pagado como venta en CRM-LIVEX-V2 (App\Services\Crm\CrmSales) y guarda la nota de venta
 * que asigna el CRM. Desde ahí el CRM se encarga de Zazu, el ERP y la boleta. Si el CRM no responde, la
 * excepción hace que la cola reintente.
 */
class RegisterCrmSale implements OrderTaskHandler
{
    public function __construct(private readonly CrmSales $crm) {}

    public function handle(Order $order): array
    {
        if (CrmSales::isEnabled()) {
            $sale = $this->crm->send($order);
            $order->update(['nota_venta' => $sale['number'], 'crm_sale_id' => $sale['id']]);

            $zazu = $sale['zazu']['number'] ?? null;

            return [
                'status' => OrderTask::DONE,
                'message' => "Venta {$sale['number']} registrada en el CRM ({$sale['status_label']}, pago {$sale['payment_status']})"
                    .($zazu ? " · en Zazu como {$zazu}" : '').'.',
                'data' => $sale,
            ];
        }

        if (PaymentSimulator::enabled()) {
            $number = 'NV-SIM-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
            $order->update(['nota_venta' => $number]);

            return ['status' => OrderTask::DONE, 'message' => "Venta {$number} registrada en el CRM (simulación).", 'data' => ['number' => $number]];
        }

        return ['status' => OrderTask::SKIPPED, 'message' => 'Envío al CRM desactivado: activa CRM_SEND_SALES=true con CRM_BASE_URL y CRM_API_KEY.'];
    }
}
