<!DOCTYPE html>
{{-- Correo de confirmación del pedido (App\Mail\OrderPaidMail). HTML de correo: tablas y estilos en línea. --}}
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $number }} confirmado</title>
</head>
<body style="margin:0;padding:0;background:#f5f7f9;font-family:Arial,Helvetica,sans-serif;color:#0b1015;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7f9;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;">
                <tr>
                    <td style="background:#061c2d;padding:22px 28px;color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:1px;">OVERSHARK</td>
                </tr>
                <tr>
                    <td style="padding:28px 28px 8px;">
                        <p style="margin:0 0 6px;font-size:13px;font-weight:bold;color:#147d5b;letter-spacing:.5px;">PEDIDO {{ $number }}</p>
                        <h1 style="margin:0 0 10px;font-size:26px;line-height:1.25;">{{ $state['title'] }}</h1>
                        <p style="margin:0;font-size:15px;line-height:1.5;color:#3c4650;">Hola {{ \Illuminate\Support\Str::of($order->cliente_name)->before(' ') }}. {{ $state['text'] }}</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 28px 0;">
                        <p style="margin:0 0 10px;font-size:13px;font-weight:bold;letter-spacing:.5px;color:#65717c;">TU PEDIDO</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            @foreach ($lines as $line)
                                <tr>
                                    <td width="72" valign="top" style="padding:10px 0;border-top:1px solid #e3e7eb;">
                                        @if ($line['image'])
                                            <img src="{{ $line['image'] }}" alt="" width="60" height="75" style="display:block;width:60px;height:75px;object-fit:cover;background:#f5f7f9;">
                                        @endif
                                    </td>
                                    <td valign="top" style="padding:10px 8px;border-top:1px solid #e3e7eb;font-size:14px;line-height:1.4;">
                                        <strong>{{ $line['name'] }}</strong>@if ($line['qty'] > 1) <span style="color:#65717c;">× {{ $line['qty'] }}</span>@endif<br>
                                        <span style="color:#65717c;font-size:13px;">{{ $line['detail'] }}</span>
                                    </td>
                                    <td valign="top" align="right" style="padding:10px 0;border-top:1px solid #e3e7eb;font-size:14px;font-weight:bold;white-space:nowrap;">{{ $line['total'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:8px 28px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.7;border-top:1px solid #e3e7eb;">
                            <tr><td style="padding-top:10px;color:#3c4650;">Subtotal</td><td align="right" style="padding-top:10px;">{{ $subtotal }}</td></tr>
                            <tr><td style="color:#3c4650;">Envío</td><td align="right">{{ $shipping }}</td></tr>
                            <tr><td style="padding-top:6px;font-size:16px;font-weight:bold;">Total</td><td align="right" style="padding-top:6px;font-size:18px;font-weight:bold;">{{ $totalText }}</td></tr>
                            @if ($paidText)
                                <tr><td style="color:#147d5b;">Pagado en línea</td><td align="right" style="color:#147d5b;font-weight:bold;">{{ $paidText }}</td></tr>
                            @endif
                            @if ($balanceText)
                                <tr><td style="font-weight:bold;">Saldo por pagar al recibir</td><td align="right" style="font-weight:bold;">{{ $balanceText }}</td></tr>
                            @endif
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:22px 28px 0;">
                        <p style="margin:0 0 6px;font-size:13px;font-weight:bold;letter-spacing:.5px;color:#65717c;">ENTREGA</p>
                        <p style="margin:0;font-size:14px;line-height:1.5;">{{ $delivery }}</p>
                        <p style="margin:6px 0 0;font-size:13px;color:#65717c;">{{ $order->entrega === \App\Support\Shipping::PICKUP ? 'Te avisaremos por WhatsApp cuando tu pedido esté listo para recoger.' : 'Te escribiremos por WhatsApp para coordinar la entrega.' }}</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:22px 28px 0;">
                        <p style="margin:0;font-size:13px;line-height:1.5;color:#65717c;">Tu boleta o factura electrónica te llegará por separado cuando se emita. Precios en soles, impuestos incluidos.</p>
                    </td>
                </tr>

                @if ($whatsapp)
                    <tr>
                        <td style="padding:22px 28px 0;">
                            <a href="{{ $whatsapp }}" style="display:inline-block;background:#061c2d;color:#ffffff;text-decoration:none;font-size:13px;font-weight:bold;letter-spacing:.5px;padding:13px 22px;">¿DUDAS? ESCRÍBENOS POR WHATSAPP</a>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td style="padding:28px;font-size:12px;color:#8a939c;border-top:0;">© {{ date('Y') }} Overshark. Recibes este correo porque hiciste un pedido en nuestra tienda.</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
