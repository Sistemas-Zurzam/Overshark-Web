<?php

namespace App\Services\Orders\Tasks;

use App\Mail\OrderPaidMail;
use App\Models\Admin\Order;
use App\Models\Admin\OrderTask;
use Illuminate\Support\Facades\Mail;

/**
 * Envía a la clienta el correo de confirmación del pedido (App\Mail\OrderPaidMail). Si el servidor de correo
 * falla, la excepción hace que la cola reintente.
 */
class SendOrderEmail implements OrderTaskHandler
{
    public function handle(Order $order): array
    {
        if (blank($order->email)) {
            return ['status' => OrderTask::SKIPPED, 'message' => 'El pedido no tiene correo.'];
        }

        Mail::to($order->email, $order->cliente_name)->send(new OrderPaidMail($order));

        // Con MAIL_MAILER=log o array el correo no sale: queda solo en el registro (pruebas).
        $testing = in_array(config('mail.default'), ['log', 'array'], true);

        return [
            'status' => OrderTask::DONE,
            'message' => $testing
                ? "Correo para {$order->email} generado, pero MAIL_MAILER=".config('mail.default').': no se envió (modo prueba).'
                : "Correo de confirmación enviado a {$order->email}.",
        ];
    }
}
