@extends('layouts.web')

@section('title', 'Pago | Overshark')

@section('content')
    @php
        $initialPaymentMethod = $paymentMethods->first();
    @endphp

    <section class="bg-[#fbfbfb] px-4 py-7 text-slate-950 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-[980px] gap-10 lg:grid-cols-[minmax(0,620px)_270px] lg:items-start lg:justify-center">
            <div>
                <div class="mx-auto grid max-w-md grid-cols-4 items-start gap-0 text-center text-[11px] font-medium text-slate-500">
                    @foreach ([['1', 'Datos personales', false], ['2', 'Metodo de entrega', false], ['3', 'Pago', true], ['4', 'Confirmacion', false]] as [$step, $label, $active])
                        <div class="relative flex flex-col items-center gap-3 pb-4">
                            @if (! $loop->first)
                                <span class="absolute right-1/2 top-3.5 h-px w-full -translate-x-4 bg-slate-200"></span>
                            @endif
                            <span @class([
                                'relative z-10 grid h-7 w-7 place-items-center rounded-full border text-sm font-bold',
                                'border-[#2f6fbd] bg-[#2f6fbd] text-white' => $active || in_array($step, ['1', '2'], true),
                                'border-slate-200 bg-white text-slate-400' => ! $active && ! in_array($step, ['1', '2'], true),
                            ])>{{ $step }}</span>
                            <span @class(['text-slate-950' => $active])>{{ $label }}</span>
                            @if ($active)
                                <span class="absolute bottom-0 h-px w-28 bg-[#2f6fbd]"></span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <form class="mt-8 rounded-2xl bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
                    <h1 class="text-base font-black">Realiza tu pago</h1>
                    <p class="mt-1 text-xs text-slate-500">Selecciona tu metodo de pago, y adjunta tu comprobante.</p>

                    <section class="mt-5">
                        <h2 class="text-sm font-black">1. Selecciona tu metodo de pago</h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            @forelse ($paymentMethods->take(3) as $index => $method)
                                <label @class([
                                    'grid min-h-24 cursor-pointer place-items-center rounded-lg border bg-white px-4 py-4 text-center transition hover:border-[#2f6fbd]',
                                    'border-blue-200 ring-1 ring-blue-100' => $index === 0,
                                    'border-slate-100' => $index !== 0,
                                ])
                                    data-payment-method
                                    data-payment-name="{{ $method->name }}"
                                    data-payment-owner="{{ $method->titular ?: 'Import Textil Maso E.I.R.L' }}"
                                    data-payment-number="{{ $method->numero ?: '987 654 321' }}"
                                    data-payment-logo="{{ $method->imageUrl() }}"
                                    data-payment-qr="{{ $method->qrImageUrl() }}"
                                >
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" class="sr-only" @checked($index === 0)>
                                    @if ($method->imageUrl())
                                        <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-12 w-20 object-contain">
                                    @else
                                        <span class="text-sm font-black">{{ $method->name }}</span>
                                    @endif
                                    <span class="mt-2 text-xs font-semibold">{{ $method->name }}</span>
                                </label>
                            @empty
                                @foreach (['Yape', 'Plin', 'Transferencias bancarias'] as $index => $name)
                                    <label @class([
                                        'grid min-h-24 cursor-pointer place-items-center rounded-lg border bg-white px-4 py-4 text-center transition hover:border-[#2f6fbd]',
                                        'border-blue-200 ring-1 ring-blue-100' => $index === 0,
                                        'border-slate-100' => $index !== 0,
                                    ])>
                                        <input type="radio" name="payment_method_id" value="{{ $name }}" class="sr-only" @checked($index === 0)>
                                        <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-sm font-black text-[#2f6fbd]">{{ substr($name, 0, 1) }}</span>
                                        <span class="mt-2 text-xs font-semibold">{{ $name }}</span>
                                    </label>
                                @endforeach
                            @endforelse
                        </div>
                    </section>

                    <section class="mt-7">
                        <h2 class="text-sm font-black">2. Paga el costo total del pedido</h2>
                        <div class="mt-4 grid gap-5 rounded-lg border border-slate-100 px-5 py-5 sm:grid-cols-[1fr_180px] sm:items-center">
                            <div class="flex items-center gap-4">
                                <div class="grid h-16 w-16 place-items-center rounded-lg bg-slate-50 text-xl font-black text-[#2f6fbd]">
                                    <img data-payment-logo-preview src="{{ $initialPaymentMethod?->imageUrl() ?? '' }}" alt="{{ $initialPaymentMethod?->name ?? '' }}" @class([
                                        'h-11 w-12 object-contain',
                                        'hidden' => ! $initialPaymentMethod?->imageUrl(),
                                    ])>
                                    <span data-payment-logo-fallback @class(['hidden' => $initialPaymentMethod?->imageUrl()])>S/</span>
                                </div>
                                <div class="text-xs">
                                    <p class="font-semibold text-slate-500">Titular</p>
                                    <p class="mt-1 font-black" data-payment-owner>{{ $initialPaymentMethod?->titular ?: 'Import Textil Maso E.I.R.L' }}</p>
                                    <p class="mt-4 font-semibold text-slate-500">Numero de <span data-payment-name>{{ $initialPaymentMethod?->name ?? 'Yape' }}</span></p>
                                    <p class="mt-1 text-xl font-black" data-payment-number>{{ $initialPaymentMethod?->numero ?: '987 654 321' }}</p>
                                    <button type="button" data-copy-payment-number class="mt-2 rounded border border-slate-200 px-3 py-1 text-[11px] font-semibold transition hover:border-slate-950">Copiar numero</button>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="mx-auto grid h-28 w-28 place-items-center bg-white p-2 shadow-inner">
                                    <img data-payment-qr-image src="{{ $initialPaymentMethod?->qrImageUrl() ?? '' }}" alt="{{ $initialPaymentMethod?->qrImageUrl() ? 'QR '.$initialPaymentMethod->name : '' }}" @class([
                                        'h-full w-full object-contain',
                                        'hidden' => ! $initialPaymentMethod?->qrImageUrl(),
                                    ])>
                                    <div data-payment-qr-fallback @class([
                                        'grid h-full w-full grid-cols-5 gap-1',
                                        'hidden' => $initialPaymentMethod?->qrImageUrl(),
                                    ])>
                                        @for ($i = 0; $i < 25; $i++)
                                            <span @class(['bg-slate-950' => in_array($i % 7, [0, 2, 3], true), 'bg-slate-100' => ! in_array($i % 7, [0, 2, 3], true)])></span>
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-2 text-[11px] font-medium text-slate-500">Monto a pagar</p>
                                <p class="text-xl font-black">S/{{ number_format($total, 2) }}</p>
                                <p class="mt-2 rounded-md bg-blue-50 px-3 py-1 text-[11px] font-medium text-slate-500">Paga este monto para validar tu compra.</p>
                            </div>
                        </div>
                    </section>

                    <section class="mt-7">
                        <h2 class="text-sm font-black">3. Adjunta tu comprobante de pago</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="grid min-h-44 cursor-pointer place-items-center rounded-lg border border-dashed border-slate-300 px-5 py-6 text-center transition hover:border-[#2f6fbd]">
                                <input type="file" name="payment_receipt" class="sr-only" accept="image/*,.pdf">
                                <svg class="h-10 w-10 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 16v3h14v-3"/></svg>
                                <span class="mt-3 text-xs text-slate-500">Arrastra tu comprobante aqui<br>o selecciona un archivo</span>
                                <span class="mt-4 rounded-md bg-[#111] px-5 py-2 text-xs font-black text-white">Seleccionar archivo</span>
                            </label>
                            <div class="rounded-lg border border-slate-100 px-5 py-6">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100">
                                        <svg class="h-6 w-6 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M7 3h7l4 4v14H7Z"/><path d="M14 3v5h5"/></svg>
                                    </span>
                                    <div class="min-w-0 text-xs">
                                        <p class="truncate font-black">comprobante_yape.jpg</p>
                                        <p class="mt-1 text-slate-500">1.2 MB</p>
                                    </div>
                                </div>
                                <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full w-2/3 rounded-full bg-[#2f6fbd]"></div>
                                </div>
                                <div class="mt-2 flex justify-between text-[11px] font-medium text-slate-500">
                                    <span>Subiendo archivo...</span>
                                    <span>65%</span>
                                </div>
                                <button type="button" class="mt-4 h-9 w-full rounded-md border border-slate-200 text-xs font-black uppercase transition hover:border-slate-950">Cancelar</button>
                            </div>
                        </div>
                    </section>

                    <label class="mt-5 flex items-start gap-2 text-xs text-slate-500">
                        <input type="checkbox" required class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#2f6fbd] focus:ring-[#2f6fbd]">
                        Confirmo que el monto transferido coincide con el importe indicado en esta compra.
                    </label>

                    <button type="button" class="mt-4 inline-flex h-12 w-full items-center justify-center gap-3 rounded-md bg-[#111] px-5 text-xs font-black uppercase text-white transition hover:bg-[#2f6fbd]">
                        Confirmar pedido
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </form>
            </div>

            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)] lg:sticky lg:top-28">
                <h2 class="text-base font-black">Resumen de compra</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($items->take(4) as $item)
                        <div class="h-9 w-9 rounded-md bg-slate-50">
                            <img src="{{ $item['image'] ?? asset('images/default-hero-banner.png') }}" alt="{{ $item['producto'] ?? 'Producto' }}" class="h-full w-full object-contain">
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 space-y-4 border-b border-slate-100 pb-5 text-sm">
                    <div class="flex items-center justify-between"><span class="font-medium text-slate-600">Envio</span><span class="font-semibold">S/{{ number_format($shipping, 2) }}</span></div>
                    <div class="flex items-center justify-between"><span class="font-medium text-slate-600">Subtotal</span><span class="font-semibold">S/{{ number_format($subtotal, 2) }}</span></div>
                    @if ($discount > 0)
                        <div class="flex items-center justify-between text-emerald-600"><span class="font-medium">Ahorras</span><span class="font-semibold">-S/{{ number_format($discount, 2) }}</span></div>
                    @endif
                    <div class="flex items-center justify-between"><span class="font-medium text-slate-600">IGV (18%)</span><span class="font-semibold">S/{{ number_format($igv, 2) }}</span></div>
                </div>
                <div class="flex items-center justify-between py-5">
                    <span class="text-base font-black">Total</span>
                    <span class="text-base font-black">S/{{ number_format($total - $discount, 2) }}</span>
                </div>
                <button type="button" class="flex h-9 w-full items-center justify-between rounded-none border border-slate-200 px-3 text-xs font-semibold transition hover:border-slate-950">
                    <span class="inline-flex items-center gap-2"><img src="{{ asset('images/iconos/Ticket.svg') }}" alt="" class="h-4 w-4 object-contain" aria-hidden="true">Tienes un cupon?</span>
                    <span class="text-base leading-none">-</span>
                </button>
                <div class="mt-6 border-t border-slate-100 pt-5">
                    <p class="text-xs font-black">Aceptamos</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($paymentMethods as $method)
                            <div class="grid h-9 min-w-11 place-items-center rounded bg-slate-50 px-2">
                                @if ($method->imageUrl())
                                    <img src="{{ $method->imageUrl() }}" alt="{{ $method->name }}" class="h-6 w-10 object-contain">
                                @else
                                    <span class="text-[11px] font-black">{{ $method->name }}</span>
                                @endif
                            </div>
                        @empty
                            <span class="rounded bg-slate-50 px-3 py-2 text-xs font-black text-slate-600">Sin metodos activos</span>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-payment-method]').forEach((methodCard) => {
            methodCard.addEventListener('click', () => {
                const name = methodCard.dataset.paymentName || 'Yape';
                const owner = methodCard.dataset.paymentOwner || 'Import Textil Maso E.I.R.L';
                const number = methodCard.dataset.paymentNumber || '987 654 321';
                const logo = methodCard.dataset.paymentLogo || '';
                const qr = methodCard.dataset.paymentQr || '';
                const nameTarget = document.querySelector('[data-payment-name]');
                const ownerTarget = document.querySelector('[data-payment-owner]');
                const numberTarget = document.querySelector('[data-payment-number]');
                const logoPreview = document.querySelector('[data-payment-logo-preview]');
                const logoFallback = document.querySelector('[data-payment-logo-fallback]');
                const qrImage = document.querySelector('[data-payment-qr-image]');
                const qrFallback = document.querySelector('[data-payment-qr-fallback]');

                if (nameTarget) {
                    nameTarget.textContent = name;
                }

                if (ownerTarget) {
                    ownerTarget.textContent = owner;
                }

                if (numberTarget) {
                    numberTarget.textContent = number;
                }

                if (logoPreview && logo) {
                    logoPreview.src = logo;
                    logoPreview.alt = name;
                    logoPreview.classList.remove('hidden');
                    logoFallback?.classList.add('hidden');
                } else if (logoPreview) {
                    logoPreview.classList.add('hidden');
                    logoPreview.removeAttribute('src');
                    logoFallback?.classList.remove('hidden');
                }

                if (qrImage && qr) {
                    qrImage.src = qr;
                    qrImage.alt = `QR ${name}`;
                    qrImage.classList.remove('hidden');
                    qrFallback?.classList.add('hidden');
                } else if (qrImage) {
                    qrImage.classList.add('hidden');
                    qrImage.removeAttribute('src');
                    qrFallback?.classList.remove('hidden');
                }
            });
        });

        document.querySelector('[data-copy-payment-number]')?.addEventListener('click', async (event) => {
            const number = document.querySelector('[data-payment-number]')?.textContent?.trim() || '';

            if (! number) {
                return;
            }

            try {
                await navigator.clipboard.writeText(number);
                event.currentTarget.textContent = 'Copiado';
                setTimeout(() => {
                    event.currentTarget.textContent = 'Copiar numero';
                }, 1500);
            } catch {
                event.currentTarget.textContent = number;
            }
        });
    </script>
@endsection
