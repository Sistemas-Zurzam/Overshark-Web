{{--
    Pago con Yape por QR de Ligo Pay. Al realizar el pedido se genera un QR por $amount (LigoPayGateway::createQr)
    y se muestra en el recuadro; la confirmación llega sola. Pendiente de la API de Ligo: hoy solo se ve el diseño.
--}}
<div class="flex flex-col gap-5 sm:flex-row sm:items-center" data-ligo-yape>
    <div class="grid h-[168px] w-[168px] shrink-0 place-items-center self-center border-2 border-dashed border-[#c9b3cf] bg-[#faf6fb] sm:self-auto" aria-hidden="true" data-ligo-qr>
        <div class="flex flex-col items-center gap-2 text-[#742384]">
            <svg class="h-14 w-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                <path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3z"/><path d="M6 6h1v1H6zM17 6h1v1h-1zM6 17h1v1H6z" fill="currentColor"/>
                <path d="M14 14h3v3h-3zM18 18h3v3h-3zM14 20h2M20 14v2"/>
            </svg>
            <span class="px-3 text-center text-[11px] font-bold leading-tight">Tu QR aparecerá aquí</span>
        </div>
    </div>
    <ol class="flex flex-col gap-2 text-[14px] leading-[1.5] text-[#2b2030]">
        <li><strong>1.</strong> Pulsa <strong>Pagar con Yape</strong>: generamos un QR por <strong>S/ {{ number_format($amount, 2) }}</strong>.</li>
        <li><strong>2.</strong> Abre Yape, toca <strong>Escanear QR</strong> y apunta a la pantalla. También puedes pagar con Plin u otra billetera.</li>
        <li><strong>3.</strong> Confirma el pago en tu app: tu pedido se confirma solo, sin enviar capturas.</li>
        <li class="text-[12px] text-[#65717c]">Pago procesado por Ligo Pay.</li>
    </ol>
</div>
