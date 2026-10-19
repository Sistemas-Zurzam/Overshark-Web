@extends('layouts.web')

{{-- Contacto: formulario (se guarda en contact_messages y se revisa en Admin → Mensajes) y datos de atención. --}}

@section('title', 'Contacto | Overshark')
@section('meta_description', 'Escríbenos: consultas sobre pedidos, productos, tallas, cambios y ventas por mayor en Overshark.')

@php
    $input = 'w-full rounded-xl border bg-panel px-4 py-3 text-sm text-white outline-none transition placeholder:text-haze focus:border-electric focus:ring-4 focus:ring-electric/25';
    $border = fn (string $field) => $errors->has($field) ? 'border-red-400' : 'border-edge-strong';
@endphp

@section('content')
    <div class="px-4 pb-20 pt-10 text-white sm:px-6 lg:px-10 lg:pt-14">
        <div class="mx-auto max-w-[1180px]">
            <header class="max-w-2xl">
                <p class="text-xs font-black uppercase tracking-[0.3em] text-electric-soft">Contacto</p>
                <h1 class="mt-3 font-heading text-4xl font-black uppercase leading-[1.02] sm:text-5xl">Hablemos. <span class="text-electric">Estamos para ayudarte.</span></h1>
                <p class="mt-4 text-base leading-7 text-mist sm:text-lg">¿Tienes una consulta sobre un pedido, una talla o quieres comprar por mayor? Escríbenos y te responderemos por correo o teléfono.</p>
            </header>

            <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.8fr)]">
                <section class="rounded-2xl border border-edge bg-[linear-gradient(160deg,rgb(15_33_72/72%),rgb(6_18_48/45%))] p-5 sm:p-8" aria-labelledby="form-title">
                    @if (session('contact_sent'))
                        <div class="py-10 text-center" role="status">
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-electric/15 text-electric-soft">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            </span>
                            <h2 id="form-title" class="mt-4 text-2xl font-black uppercase">¡Mensaje enviado!</h2>
                            <p class="mx-auto mt-2 max-w-md text-mist">Gracias por escribirnos. Revisaremos tu mensaje y te responderemos lo antes posible en nuestro horario de atención.</p>
                            <a href="{{ route('web.contact') }}" class="mt-6 inline-flex rounded-full border border-white/70 px-6 py-3 text-xs font-black uppercase tracking-[0.1em] transition hover:bg-white hover:text-ink">Enviar otro mensaje</a>
                        </div>
                    @else
                        <h2 id="form-title" class="text-xl font-black uppercase">Escríbenos</h2>
                        <p class="mt-1 text-sm text-haze">Los campos con * son obligatorios.</p>

                        @if ($errors->any())
                            <div class="mt-5 rounded-xl border border-red-400/50 bg-red-500/10 px-4 py-3 text-sm font-semibold text-red-200" role="alert">Revisa los campos marcados.</div>
                        @endif

                        <form action="{{ route('web.contact.store') }}" method="POST" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                            @csrf
                            <div class="hidden" aria-hidden="true">
                                <label>No llenes este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">Nombre y apellido *</span>
                                <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120" class="{{ $input }} {{ $border('name') }}" @error('name') aria-invalid="true" aria-describedby="e-name" @enderror>
                                @error('name')<span id="e-name" class="mt-1 block text-xs font-semibold text-red-300">{{ $message }}</span>@enderror
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">Correo *</span>
                                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="180" class="{{ $input }} {{ $border('email') }}" @error('email') aria-invalid="true" aria-describedby="e-email" @enderror>
                                @error('email')<span id="e-email" class="mt-1 block text-xs font-semibold text-red-300">{{ $message }}</span>@enderror
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">Teléfono / WhatsApp <span class="font-normal text-haze">(opcional)</span></span>
                                <input type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" maxlength="30" class="{{ $input }} {{ $border('phone') }}" @error('phone') aria-invalid="true" aria-describedby="e-phone" @enderror>
                                @error('phone')<span id="e-phone" class="mt-1 block text-xs font-semibold text-red-300">{{ $message }}</span>@enderror
                            </label>
                            <label class="block">
                                <span class="mb-2 block text-sm font-bold">Motivo *</span>
                                <select name="subject" required class="{{ $input }} {{ $border('subject') }}" @error('subject') aria-invalid="true" aria-describedby="e-subject" @enderror data-contact-subject>
                                    <option value="">Elige una opción</option>
                                    @foreach ($subjects as $value => $label)
                                        <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('subject')<span id="e-subject" class="mt-1 block text-xs font-semibold text-red-300">{{ $message }}</span>@enderror
                            </label>
                            <label @class(['block sm:col-span-2', 'hidden' => old('subject') !== 'pedido']) data-contact-order>
                                <span class="mb-2 block text-sm font-bold">Número de pedido <span class="font-normal text-haze">(si lo tienes)</span></span>
                                <input type="text" name="order_number" value="{{ old('order_number') }}" maxlength="60" class="{{ $input }} border-edge-strong">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="mb-2 block text-sm font-bold">Mensaje *</span>
                                <textarea name="message" rows="6" required maxlength="2000" class="{{ $input }} {{ $border('message') }}" placeholder="Cuéntanos en qué podemos ayudarte" @error('message') aria-invalid="true" aria-describedby="e-message" @enderror>{{ old('message') }}</textarea>
                                @error('message')<span id="e-message" class="mt-1 block text-xs font-semibold text-red-300">{{ $message }}</span>@enderror
                            </label>
                            <label class="flex items-start gap-3 text-sm text-mist sm:col-span-2">
                                <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) required class="mt-0.5 h-5 w-5 shrink-0 rounded border-edge-strong bg-panel text-electric focus:ring-electric">
                                <span>Autorizo a Overshark a usar mis datos solo para responder a este mensaje. *</span>
                            </label>
                            @error('privacy')<span class="-mt-3 block text-xs font-semibold text-red-300 sm:col-span-2">{{ $message }}</span>@enderror

                            <div class="sm:col-span-2">
                                <button type="submit" class="btn-primary h-12 w-full px-8 text-sm sm:w-auto">Enviar mensaje</button>
                            </div>
                        </form>
                    @endif
                </section>

                <aside class="space-y-4" aria-label="Otros canales">
                    <div class="rounded-2xl border border-edge bg-panel/70 p-6">
                        <h2 class="text-sm font-black uppercase tracking-[0.15em] text-electric-soft">Correo</h2>
                        <a href="mailto:ventas@overshark.com" class="mt-2 block text-lg font-bold hover:text-electric-soft">ventas@overshark.com</a>
                    </div>
                    <div class="rounded-2xl border border-edge bg-panel/70 p-6">
                        <h2 class="text-sm font-black uppercase tracking-[0.15em] text-electric-soft">Horario de atención</h2>
                        <p class="mt-2 text-lg font-bold">Lunes a viernes</p>
                        <p class="text-mist">9:00 a. m. a 6:00 p. m.</p>
                    </div>
                    <div class="rounded-2xl border border-edge bg-panel/70 p-6">
                        <h2 class="text-sm font-black uppercase tracking-[0.15em] text-electric-soft">¿Un reclamo?</h2>
                        <p class="mt-2 text-sm leading-6 text-mist">Si quieres presentar un reclamo o una queja formal, usa nuestro Libro de Reclamaciones.</p>
                        <a href="{{ route('web.claims.create') }}" class="mt-3 inline-flex text-xs font-black uppercase tracking-[0.15em] text-electric-soft hover:text-white">Ir al Libro de Reclamaciones →</a>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        // Muestra «Número de pedido» solo cuando el motivo es un pedido.
        document.querySelector('[data-contact-subject]')?.addEventListener('change', (e) => {
            document.querySelector('[data-contact-order]')?.classList.toggle('hidden', e.target.value !== 'pedido');
        });
    </script>
@endsection
