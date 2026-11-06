{{-- Fila del historial: código, fecha, estado, total y prendas. $order = pedido de la cuenta. --}}
@php($units = collect($order->productos_json ?? [])->sum(fn ($line) => (int) ($line['cantidad'] ?? 1)))
<article class="mt-4 flex flex-col gap-4 border border-[#dae0e5] p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
    <div class="flex min-w-0 flex-col gap-1.5">
        <div class="flex flex-wrap items-center gap-3">
            <p class="text-[16px] font-extrabold">Pedido #{{ $order->displayNumber() }}</p>
            @include('web.account.partials.order-status', ['order' => $order])
        </div>
        <p class="text-[13px] text-[#65717c]">{{ $order->created_at?->format('d/m/Y H:i') }} · {{ $units }} {{ $units === 1 ? 'prenda' : 'prendas' }}</p>
    </div>
    <div class="flex items-center justify-between gap-5 sm:justify-end">
        <p class="text-[18px] font-extrabold">S/ {{ number_format((float) $order->precio_total, 2) }}</p>
        <a href="{{ route('customer.orders.show', $order->id) }}" class="os-checkout-btn os-checkout-btn--ghost os-focus !min-h-10 !px-4" aria-label="Ver detalle del pedido {{ $order->displayNumber() }}">VER DETALLE</a>
    </div>
</article>
