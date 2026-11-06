<?php

namespace App\Mail;

use App\Models\Admin\Order;
use App\Models\BrandSetting;
use App\Support\Checkout;
use App\Support\ProductName;
use App\Support\Shipping;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo de confirmación del pedido (se envía al pagar, como tarea de App\Services\Orders\OrderFulfillment):
 * nota de venta, prendas, totales, lo pagado y el saldo, y la entrega. El comprobante (boleta o factura) lo
 * emite el CRM; este correo no lo adjunta.
 */
class OrderPaidMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pedido {$this->order->displayNumber()} confirmado | Overshark");
    }

    public function content(): Content
    {
        $order = $this->order;
        $money = fn (float $amount) => 'S/ '.number_format($amount, 2);
        $total = (float) $order->precio_total;
        $paid = (float) $order->pago_monto;
        $balance = $order->isCashOnDelivery() ? round($total - $paid, 2) : 0.0;

        $state = match ($order->statusName()) {
            Checkout::STATUS_PAID => ['title' => '¡Tu pago fue aprobado!', 'text' => 'Gracias por comprar en Overshark. Ya estamos preparando tu pedido.'],
            Checkout::STATUS_DEPOSIT_PAID => ['title' => '¡Recibimos tu separo!', 'text' => "Gracias por comprar en Overshark. El saldo de {$money($balance)} lo pagas al recibir tu pedido."],
            default => ['title' => '¡Recibimos tu pedido!', 'text' => $balance > 0 ? "Pagas {$money($balance)} al recibirlo." : 'Gracias por comprar en Overshark.'],
        };

        return new Content(
            view: 'mail.order-paid',
            with: [
                'order' => $order,
                'number' => $order->displayNumber(),
                'state' => $state,
                'lines' => collect($order->productos_json ?? [])->map(fn (array $line) => [
                    'name' => ($line['tipo'] ?? '') === 'combo' ? (string) ($line['producto'] ?? 'Combo') : ProductName::title((string) ($line['producto'] ?? 'Producto')),
                    'detail' => ($line['tipo'] ?? '') === 'combo'
                        ? collect($line['selecciones'] ?? [])->map(fn (array $item) => ProductName::title((string) ($item['producto'] ?? '')).' '.ProductName::title((string) ($item['color'] ?? '')).' · '.($item['talla'] ?? ''))->implode(' / ')
                        : ProductName::title((string) ($line['color'] ?? '')).' · Talla '.($line['talla'] ?? '-'),
                    'qty' => (int) ($line['cantidad'] ?? 1),
                    'total' => $money((float) ($line['precio'] ?? 0) * (int) ($line['cantidad'] ?? 1)),
                    'image' => $this->absolute($line['imagen'] ?? null),
                ])->all(),
                'subtotal' => $money((float) $order->subtotal),
                'shipping' => (float) $order->envio > 0 ? $money((float) $order->envio) : 'Gratis',
                'totalText' => $money($total),
                'paidText' => $paid > 0 ? $money($paid) : null,
                'balanceText' => $balance > 0 ? $money($balance) : null,
                'delivery' => Shipping::deliveryLabel($order->entrega, $order->agencia, $order->direccion),
                'whatsapp' => BrandSetting::current()->whatsappUrl(),
            ],
        );
    }

    /** Las imágenes de un correo se piden desde fuera de la tienda: necesitan su dirección completa. */
    private function absolute(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : url($path);
    }
}
