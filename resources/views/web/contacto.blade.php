@extends('layouts.web')

{{--
    Contacto (diseño Figma "05 · Contacto · Overshark"): cabecera, canales (WhatsApp, correo, horario
    y envíos), formulario (se guarda en contact_messages y se revisa en Admin → Mensajes), ayuda rápida
    y preguntas frecuentes. El WhatsApp sale de Admin → Identidad visual; sin número no se muestra.
--}}

@section('title', 'Contacto | Overshark')
@section('meta_description', 'Escríbenos: consultas sobre pedidos, productos, tallas, cambios y ventas por mayor en Overshark.')

@php
    $brand = \App\Models\BrandSetting::current();
    // Desde Bravos (?marca=bravos): paleta negra y amarilla (.os-hh--bravos) e íconos en negro.
    // Línea mujer: paleta Overshark Girls (.os-hh--girls) e íconos en rosa.
    $bravos = ($brandPage ?? null)?->slug === 'bravos';
    $girls = ! $bravos && session('storefront.line') === 'mujer';
    $icon = fn (string $name) => asset('images/contacto/icon-'.$name.($bravos ? '-bravos' : ($girls ? '-girls' : '')).'.svg');
    $display = fn (string $hh, string $bv) => $bravos ? 'font-extrabold uppercase tracking-[-0.01em] '.$bv : 'os-hh-display '.$hh;
    $whatsappUrl = $brand->whatsappUrl();
    $email = \App\Support\SiteContent::get('contacto.email');
    $input = 'w-full border bg-[#f5f7fa] px-4 text-[14px] font-medium text-[#0b0f14] outline-none transition placeholder:text-[#8a93a0] focus:border-[var(--hh-accent)] focus:bg-white focus:ring-4 focus:ring-[var(--hh-accent)]/15';
    $border = fn (string $field) => $errors->has($field) ? 'border-red-500' : 'border-[var(--shop-line)]';
    $faqs = \App\Support\SiteContent::faqs();
@endphp

@if ($bravos)
    @section('body-class', 'theme-neon theme-bravos')
@endif

@section('content')
    <div @class(['os-hh', 'os-hh--girls' => $girls, 'os-hh--bravos' => $bravos])>
        {{-- Cabecera de página --}}
        <header class="[background:var(--shop-head)] py-12 text-white lg:py-14">
            <div class="os-hh-wrap flex flex-col items-start gap-3.5">
                <nav aria-label="Ruta" class="text-[13px] font-semibold text-[var(--hh-muted)]">
                    <a href="{{ $bravos ? route('web.brands.show', $brandPage) : route('web.home') }}" class="hover:text-white">Inicio</a>
                    <span class="mx-1.5" aria-hidden="true">›</span>
                    <span class="text-white">Contacto</span>
                </nav>
                <h1 class="{{ $display('text-[54px] leading-[0.95] min-[400px]:text-[64px] sm:text-[112px] sm:leading-[104px]', 'text-[34px] leading-[1.05] min-[400px]:text-[40px] sm:text-[72px]') }}">Contáctanos</h1>
                <p class="text-[16px] font-medium text-[var(--shop-intro)] sm:text-[18px]">¿Dudas con tu pedido, tallas o cambios? Te ayudamos antes, durante y después de tu compra.</p>
            </div>
        </header>

        {{-- Canales --}}
        <section class="os-hh-wrap pt-10 lg:pt-14" aria-label="Canales de atención">
            <ul @class(['grid gap-4 sm:grid-cols-2', 'lg:grid-cols-4' => $whatsappUrl, 'lg:grid-cols-3' => ! $whatsappUrl])>
                @if ($whatsappUrl)
                    <li class="flex flex-col items-start gap-3 bg-[var(--shop-deep)] px-6 py-7 text-white">
                        <span class="grid h-12 w-12 place-items-center bg-[var(--hh-bright)]" aria-hidden="true"><img src="{{ $icon('whatsapp') }}" alt="" width="22" height="22"></span>
                        <h2 class="text-[12px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-bright)]">WhatsApp</h2>
                        <p class="text-[16px] font-bold leading-[23px]">{{ $brand->whatsappDisplay() }}</p>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="os-focus text-[13px] font-extrabold text-[var(--hh-bright)] hover:underline">Escríbenos →</a>
                    </li>
                @endif
                <li class="flex flex-col items-start gap-3 border border-[var(--shop-line)] px-6 py-7">
                    <span class="grid h-12 w-12 place-items-center bg-[var(--hh-soft)]" aria-hidden="true"><img src="{{ $icon('correo') }}" alt="" width="22" height="22"></span>
                    <h2 class="text-[12px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">Correo</h2>
                    <p class="break-all text-[16px] font-bold leading-[23px]">{{ $email }}</p>
                    <a href="mailto:{{ $email }}" class="os-focus text-[13px] font-extrabold text-[#545966] hover:text-[var(--hh-accent)]">Enviar correo →</a>
                </li>
                <li class="flex flex-col items-start gap-3 border border-[var(--shop-line)] px-6 py-7">
                    <span class="grid h-12 w-12 place-items-center bg-[var(--hh-soft)]" aria-hidden="true"><img src="{{ $icon('horario') }}" alt="" width="22" height="22"></span>
                    <h2 class="text-[12px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">Horario de atención</h2>
                    <p class="text-[16px] font-bold leading-[23px]">{{ \App\Support\SiteContent::get('contacto.horario') }}</p>
                    <p class="text-[13px] font-extrabold text-[#545966]">Te respondemos en horario de atención</p>
                </li>
                <li class="flex flex-col items-start gap-3 border border-[var(--shop-line)] px-6 py-7">
                    <span class="grid h-12 w-12 place-items-center bg-[var(--hh-soft)]" aria-hidden="true"><img src="{{ $icon('envios') }}" alt="" width="22" height="22"></span>
                    <h2 class="text-[12px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">Envíos</h2>
                    <p class="text-[16px] font-bold leading-[23px]">{{ \App\Support\SiteContent::t('Lima: delivery {lima} · Agencia Shalom {agencia}') }}</p>
                    <p class="text-[13px] font-extrabold text-[#545966]">Recojo en almacén gratis</p>
                </li>
            </ul>
        </section>

        {{-- Formulario y ayuda --}}
        <div class="os-hh-wrap grid gap-8 pb-16 pt-10 lg:grid-cols-[minmax(0,1fr)_400px] lg:pb-[88px] lg:pt-14">
            <section class="flex flex-col gap-5 border border-[var(--shop-line)] p-5 sm:p-10" aria-labelledby="form-title">
                @if (session('contact_sent'))
                    <div class="py-10 text-center" role="status">
                        <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-[var(--hh-soft)] text-[var(--hh-accent)]">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                        </span>
                        <h2 id="form-title" class="{{ $display('mt-4 text-[40px] leading-none', 'mt-4 text-[28px] leading-[1.1]') }}">¡Mensaje enviado!</h2>
                        <p class="mx-auto mt-3 max-w-md text-[15px] font-medium text-[#545966]">Gracias por escribirnos. Revisaremos tu mensaje y te responderemos lo antes posible en nuestro horario de atención.</p>
                        <a href="{{ route('web.contact', $bravos ? ['marca' => 'bravos'] : []) }}" class="os-hh-btn os-focus mt-6 bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)]">Enviar otro mensaje</a>
                    </div>
                @else
                    <div class="flex flex-col gap-1.5 border-b-2 border-[#0b0f14] pb-3">
                        <h2 id="form-title" class="{{ $display('text-[44px] leading-[44px]', 'text-[30px] leading-[1.15]') }}">Escríbenos</h2>
                        <p class="text-[14px] font-medium text-[#545966]">Te respondemos por correo o WhatsApp en horario de atención.</p>
                    </div>

                    @if ($errors->any())
                        <div class="border border-red-300 bg-red-50 px-4 py-3 text-[13px] font-bold text-red-700" role="alert">Revisa los campos marcados.</div>
                    @endif

                    <form action="{{ route('web.contact.store') }}" method="POST" class="grid gap-5 sm:grid-cols-2" novalidate>
                        @if ($bravos)<input type="hidden" name="marca" value="bravos">@endif
                        @csrf
                        <div class="hidden" aria-hidden="true">
                            <label>No llenes este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <label class="block">
                            <span class="mb-2 block text-[13px] font-bold">Nombre y apellido</span>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ej. Ana Torres" autocomplete="name" required maxlength="120" class="{{ $input }} {{ $border('name') }} h-12" @error('name') aria-invalid="true" aria-describedby="e-name" @enderror>
                            @error('name')<span id="e-name" class="mt-1 block text-[12px] font-bold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-[13px] font-bold">Correo</span>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="tucorreo@ejemplo.com" autocomplete="email" required maxlength="180" class="{{ $input }} {{ $border('email') }} h-12" @error('email') aria-invalid="true" aria-describedby="e-email" @enderror>
                            @error('email')<span id="e-email" class="mt-1 block text-[12px] font-bold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-[13px] font-bold">Celular <span class="font-medium text-[#8a93a0]">(opcional)</span></span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="9XX XXX XXX" autocomplete="tel" inputmode="tel" maxlength="30" class="{{ $input }} {{ $border('phone') }} h-12" @error('phone') aria-invalid="true" aria-describedby="e-phone" @enderror>
                            @error('phone')<span id="e-phone" class="mt-1 block text-[12px] font-bold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-[13px] font-bold">N° de pedido <span class="font-medium text-[#8a93a0]">(opcional)</span></span>
                            <input type="text" name="order_number" value="{{ old('order_number') }}" placeholder="#000000" maxlength="60" class="{{ $input }} {{ $border('order_number') }} h-12">
                        </label>
                        <label class="block">
                            <span class="mb-2 block text-[13px] font-bold">¿Cómo prefieres que te contactemos?</span>
                            <select name="prefers_contact" required class="{{ $input }} {{ $border('prefers_contact') }} h-12 cursor-pointer appearance-none bg-[length:16px] bg-[position:right_16px_center] bg-no-repeat pr-11" style="background-image: url('{{ asset('images/home-hombre/chevron-abajo.svg') }}')" @error('prefers_contact') aria-invalid="true" aria-describedby="e-prefers" @enderror>
                                @foreach ($preferences as $value => $label)
                                    <option value="{{ $value }}" @selected(old('prefers_contact', 'correo') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('prefers_contact')<span id="e-prefers" class="mt-1 block text-[12px] font-bold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-2 block text-[13px] font-bold">Motivo</span>
                            <select name="subject" required class="{{ $input }} {{ $border('subject') }} h-12 cursor-pointer appearance-none bg-[length:16px] bg-[position:right_16px_center] bg-no-repeat pr-11" style="background-image: url('{{ asset('images/home-hombre/chevron-abajo.svg') }}')" @error('subject') aria-invalid="true" aria-describedby="e-subject" @enderror>
                                <option value="">Elige una opción</option>
                                @foreach ($subjects as $value => $label)
                                    <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('subject')<span id="e-subject" class="mt-1 block text-[12px] font-bold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-2 block text-[13px] font-bold">Mensaje</span>
                            <textarea name="message" rows="6" required maxlength="2000" placeholder="Cuéntanos en qué te ayudamos…" class="{{ $input }} {{ $border('message') }} min-h-[140px] py-3.5" @error('message') aria-invalid="true" aria-describedby="e-message" @enderror>{{ old('message') }}</textarea>
                            @error('message')<span id="e-message" class="mt-1 block text-[12px] font-bold text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="flex items-start gap-2.5 text-[13px] font-medium text-[#545966] sm:col-span-2">
                            <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) required class="mt-px h-[18px] w-[18px] shrink-0 rounded-[3px] border-[var(--shop-check-line)] accent-[var(--hh-accent)]">
                            <span>Acepto la <a href="{{ route('web.privacy') }}" data-legal-open="privacy" target="_blank" rel="noopener" class="font-bold underline underline-offset-4">Política de Privacidad</a> y el tratamiento de mis datos para responder a este mensaje.</span>
                        </label>
                        @error('privacy')<span class="-mt-3 block text-[12px] font-bold text-red-600 sm:col-span-2">{{ $message }}</span>@enderror

                        <button type="submit" class="os-hh-btn os-focus w-full bg-[#0b0f14] text-white hover:bg-[var(--hh-accent)] sm:col-span-2">Enviar mensaje →</button>
                    </form>
                @endif
            </section>

            <aside class="flex flex-col gap-4" aria-label="Ayuda rápida">
                @if ($whatsappUrl)
                    <div class="flex flex-col items-start gap-3.5 bg-[var(--shop-deep)] px-8 py-9 text-white">
                        <p class="text-[12px] font-extrabold uppercase tracking-[2px] text-[var(--hh-bright)]">Respuesta rápida</p>
                        <h2 class="{{ $display('text-[48px] leading-[46px]', 'text-[32px] leading-[1.15]') }}">¿Lo necesitas<br>ya mismo?</h2>
                        <p class="text-[15px] font-medium leading-[23px] text-[var(--hh-muted)]">Escríbenos por WhatsApp y un asesor te ayuda con tallas, stock y el estado de tu pedido.</p>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="os-hh-btn os-focus w-full bg-[var(--hh-bright)] text-[var(--shop-deep)] hover:bg-[var(--hh-bright-hover)]">Abrir WhatsApp →</a>
                    </div>
                @endif
                <div class="border border-[var(--shop-line)]">
                    <img src="{{ asset('images/contacto/mapa.webp') }}" alt="" class="h-40 w-full object-cover" loading="lazy">
                    <div class="flex flex-col gap-1 px-6 pb-5 pt-[18px]">
                        <p class="text-[16px] font-extrabold">Enviamos a todo el Perú</p>
                        <p class="text-[13px] font-medium text-[#545966]">Tienda 100% online</p>
                    </div>
                </div>
                <a href="{{ route('web.claims.create') }}" class="os-focus group flex items-center justify-between bg-[#f5f7fa] px-6 py-5 transition hover:bg-[var(--hh-soft)]">
                    <span class="flex flex-col gap-1">
                        <span class="text-[15px] font-extrabold">Libro de Reclamaciones</span>
                        <span class="text-[13px] font-medium text-[#545966]">Registra tu queja o reclamo</span>
                    </span>
                    <span class="text-[20px] font-extrabold text-[var(--hh-accent)] transition group-hover:translate-x-1" aria-hidden="true">→</span>
                </a>
            </aside>
        </div>

        {{-- Preguntas frecuentes --}}
        <section id="faq" class="scroll-mt-[var(--header-h)] bg-[var(--hh-soft)] pb-16 pt-16 lg:pb-[100px] lg:pt-[88px]" aria-labelledby="faq-title">
            <div class="os-hh-wrap flex flex-col gap-6">
                <header class="os-hh-head">
                    <div>
                        <p class="os-hh-eyebrow">Ayuda</p>
                        <h2 id="faq-title" class="os-hh-title">Preguntas frecuentes</h2>
                        <p class="os-hh-sub">Lo que más nos preguntan antes de comprar</p>
                    </div>
                </header>
                <div class="flex flex-col gap-3">
                    @foreach ($faqs as [$question, $answer])
                        <details class="os-faq group bg-white px-5 py-[22px] sm:px-7" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[16px] font-extrabold sm:text-[17px]">
                                {{ $question }}
                                <span class="text-[24px] leading-none text-[var(--hh-accent)]" aria-hidden="true"><span class="group-open:hidden">+</span><span class="hidden group-open:inline">–</span></span>
                            </summary>
                            <p class="mt-3 text-[15px] font-medium leading-6 text-[#545966]">{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
