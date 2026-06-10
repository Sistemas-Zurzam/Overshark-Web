@extends('layouts.web')

@section('title', 'Libro de reclamaciones | Overshark')

@php
    $submitted = session('claim_submitted');
    $initialStep = $submitted ? 4 : 1;
    $fieldClass =
        'mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100';
    $labelClass = 'text-base font-semibold text-slate-950';
@endphp

@section('content')
    <section class="bg-white px-5 py-10 text-slate-950 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="text-center">
                <h1 class="text-4xl font-black tracking-normal sm:text-5xl">Libro de reclamaciones</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                    Queremos ayudarte a resolver cualquier inconveniente. Completa el formulario y te atenderemos en
                    brevedad.
                </p>
            </div>

            <div class="mt-10" data-claim-wizard data-initial-step="{{ $initialStep }}">
                <ol class="grid grid-cols-4 items-start gap-2 text-center text-xs font-semibold text-slate-500 sm:text-sm">
                    @foreach ([
            1 => 'Datos personales',
            2 => 'Datos del pedido',
            3 => 'Detalle del reclamo',
            4 => 'Confirmacion',
        ] as $step => $label)
                        <li class="relative">
                            @if ($step < 4)
                                <span class="absolute left-1/2 top-4 hidden h-px w-full bg-slate-200 sm:block"></span>
                            @endif
                            <span data-claim-step-indicator="{{ $step }}"
                                class="relative mx-auto grid h-9 w-9 place-items-center rounded-full border border-slate-300 bg-white text-base font-black text-slate-400">{{ $step }}</span>
                            <span data-claim-step-label="{{ $step }}"
                                class="mt-3 block text-slate-500">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>

                <div
                    class="mx-auto mt-8 max-w-4xl rounded-2xl bg-white px-5 py-8 shadow-[0_20px_65px_rgba(17,17,17,0.07)] sm:px-8 lg:px-12">
                    @if ($errors->any())
                        <div
                            class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            Revisa los campos marcados antes de enviar tu reclamo.
                        </div>
                    @endif

                    @if ($submitted)
                        <div data-claim-step="4" class="grid min-h-[320px] place-items-center text-center">
                            <div>
                                <div
                                    class="mx-auto grid h-16 w-16 place-items-center rounded-full border-2 border-cyan-600 text-cyan-700">
                                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7" aria-hidden="true">
                                        <path d="M5 12.5 10 17 19 7" />
                                    </svg>
                                </div>
                                <h2 class="mt-7 text-2xl font-black sm:text-3xl">Tu reclamo fue enviado correctamente.</h2>
                                <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-600">
                                    Hemos recibido tu reclamo y lo atenderemos conforme a la normativa vigente del Libro de
                                    Reclamaciones.
                                </p>
                                <a href="{{ route('web.home') }}" class="btn-primary mt-8 gap-3 px-8 py-4">
                                    Regresar al inicio
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7" aria-hidden="true">
                                        <path d="M5 12h14" />
                                        <path d="m12 5 7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('web.claims.store') }}" novalidate>
                            @csrf

                            <div data-claim-step="1">
                                <h2 class="text-2xl font-black">1. Datos personales del consumidor</h2>
                                <div class="mt-6 grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="consumer_name" class="{{ $labelClass }}">Nombres y apellidos <span
                                                class="text-red-600">*</span></label>
                                        <input id="consumer_name" name="consumer_name" value="{{ old('consumer_name') }}"
                                            class="{{ $fieldClass }}" placeholder="Juan Perez Gomez" required>
                                        @error('consumer_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="document_number" class="{{ $labelClass }}">Tipo y numero de documento
                                            <span class="text-red-600">*</span></label>
                                        <div class="mt-2 grid grid-cols-[88px_1fr] gap-2">
                                            <select name="document_type"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm outline-none focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100"
                                                required>
                                                @foreach (['DNI', 'CE', 'RUC', 'Pasaporte'] as $type)
                                                    <option value="{{ $type }}" @selected(old('document_type', 'DNI') === $type)>
                                                        {{ $type }}</option>
                                                @endforeach
                                            </select>
                                            <input id="document_number" name="document_number"
                                                value="{{ old('document_number') }}"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100"
                                                placeholder="75351525" required>
                                        </div>
                                        @error('document_number')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="address" class="{{ $labelClass }}">Direccion <span
                                                class="text-red-600">*</span></label>
                                        <input id="address" name="address" value="{{ old('address') }}"
                                            class="{{ $fieldClass }}" placeholder="Ej. Av. Arequipa 1234, Lima, Peru"
                                            required>
                                        @error('address')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="email" class="{{ $labelClass }}">Correo electronico <span
                                                class="text-red-600">*</span></label>
                                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                                            class="{{ $fieldClass }}" placeholder="correo@ejemplo.com" required>
                                        @error('email')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="phone" class="{{ $labelClass }}">Telefono / Celular <span
                                                class="text-red-600">*</span></label>
                                        <input id="phone" name="phone" value="{{ old('phone') }}"
                                            class="{{ $fieldClass }}" placeholder="920332344" required>
                                        @error('phone')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <fieldset class="mt-7">
                                    <legend class="{{ $labelClass }}">Eres menor de edad?</legend>
                                    <div class="mt-4 flex flex-wrap gap-8 text-sm font-medium">
                                        <label class="inline-flex items-center gap-3"><input type="radio" name="is_minor"
                                                value="0" class="h-4 w-4 accent-cyan-700" @checked(old('is_minor', '0') === '0')>
                                            No</label>
                                        <label class="inline-flex items-center gap-3"><input type="radio"
                                                name="is_minor" value="1" class="h-4 w-4 accent-cyan-700"
                                                @checked(old('is_minor') === '1')> Si, soy menor de edad</label>
                                    </div>
                                </fieldset>

                                <div data-guardian-fields
                                    class="{{ old('is_minor') === '1' ? 'grid' : 'hidden' }} mt-6 gap-6 rounded-lg border border-cyan-100 bg-cyan-50 p-4 md:grid-cols-3">
                                    <div class="md:col-span-3 text-sm font-semibold text-cyan-900">Si eres menor de edad,
                                        el formulario sera completado por tu padre, madre o tutor.</div>
                                    <div>
                                        <label for="guardian_name" class="text-sm font-semibold">Nombre del
                                            apoderado</label>
                                        <input id="guardian_name" name="guardian_name"
                                            value="{{ old('guardian_name') }}" class="{{ $fieldClass }}"
                                            placeholder="Nombre completo">
                                    </div>
                                    <div>
                                        <label for="guardian_document_type" class="text-sm font-semibold">Tipo
                                            documento</label>
                                        <select id="guardian_document_type" name="guardian_document_type"
                                            class="{{ $fieldClass }}">
                                            @foreach (['DNI', 'CE', 'RUC', 'Pasaporte'] as $type)
                                                <option value="{{ $type }}" @selected(old('guardian_document_type', 'DNI') === $type)>
                                                    {{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="guardian_document_number" class="text-sm font-semibold">Numero
                                            documento</label>
                                        <input id="guardian_document_number" name="guardian_document_number"
                                            value="{{ old('guardian_document_number') }}" class="{{ $fieldClass }}"
                                            placeholder="Documento">
                                    </div>
                                </div>

                                <div class="mt-8 flex justify-end">
                                    <button type="button" data-claim-next class="btn-primary gap-3 px-8 py-4">Continuar
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7" aria-hidden="true">
                                            <path d="M5 12h14" />
                                            <path d="m12 5 7 7-7 7" />
                                        </svg></button>
                                </div>
                            </div>

                            <div data-claim-step="2" class="hidden">
                                <h2 class="text-2xl font-black">2. Datos del pedido o servicio</h2>
                                <div class="mt-6 grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="{{ $labelClass }}">Tipo de comprobante <span
                                                class="text-red-600">*</span></label>
                                        <select name="receipt_type" class="{{ $fieldClass }}" required>
                                            @foreach (['Boleta', 'Factura', 'Ticket', 'Otro'] as $type)
                                                <option value="{{ $type }}" @selected(old('receipt_type', 'Boleta') === $type)>
                                                    {{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="order_number" class="{{ $labelClass }}">Numero de pedido o
                                            comprobante <span class="text-red-600">*</span></label>
                                        <input id="order_number" name="order_number" value="{{ old('order_number') }}"
                                            class="{{ $fieldClass }}" placeholder="Ej. OV-2026-001245" required>
                                    </div>
                                    <div>
                                        <label for="purchase_date" class="{{ $labelClass }}">Fecha de compra <span
                                                class="text-red-600">*</span></label>
                                        <input id="purchase_date" type="date" name="purchase_date"
                                            value="{{ old('purchase_date') }}" max="{{ now()->toDateString() }}"
                                            class="{{ $fieldClass }}" required>
                                    </div>
                                    <div>
                                        <label for="purchase_channel" class="{{ $labelClass }}">Canal de compra <span
                                                class="text-red-600">*</span></label>
                                        <input id="purchase_channel" name="purchase_channel"
                                            value="{{ old('purchase_channel') }}" class="{{ $fieldClass }}"
                                            placeholder="Sitio web" required>
                                    </div>
                                    <div>
                                        <label for="claimed_amount" class="{{ $labelClass }}">Monto reclamado</label>
                                        <input id="claimed_amount" type="number" step="0.01" min="0"
                                            name="claimed_amount" value="{{ old('claimed_amount') }}"
                                            class="{{ $fieldClass }}" placeholder="Ej. S/ 99.90">
                                    </div>
                                    <div>
                                        <label for="order_product" class="{{ $labelClass }}">Producto o servicio
                                            relacionado <span class="text-red-600">*</span></label>
                                        <input id="order_product" name="order_product"
                                            value="{{ old('order_product') }}" class="{{ $fieldClass }}"
                                            placeholder="Ej. Baby Tee Manga Larga Negra" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="order_description" class="{{ $labelClass }}">Descripcion breve del
                                            pedido o servicio</label>
                                        <textarea id="order_description" name="order_description" rows="4" class="{{ $fieldClass }}"
                                            placeholder="Describe brevemente el producto, servicio o pedido relacionado a tu solicitud.">{{ old('order_description') }}</textarea>
                                    </div>
                                </div>
                                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                                    <button type="button" data-claim-prev class="btn-secondary gap-3 px-8 py-4"><svg
                                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7" aria-hidden="true">
                                            <path d="M19 12H5" />
                                            <path d="m12 19-7-7 7-7" />
                                        </svg> Regresar</button>
                                    <button type="button" data-claim-next class="btn-primary gap-3 px-8 py-4">Continuar
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7" aria-hidden="true">
                                            <path d="M5 12h14" />
                                            <path d="m12 5 7 7-7 7" />
                                        </svg></button>
                                </div>
                            </div>

                            <div data-claim-step="3" class="hidden">
                                <h2 class="text-2xl font-black">3. Detalle del reclamo o queja</h2>
                                <fieldset class="mt-6 grid gap-4 md:grid-cols-2">
                                    <label class="rounded-lg border border-slate-200 p-5 transition hover:border-cyan-600">
                                        <span class="flex items-start gap-3">
                                            <input type="radio" name="claim_type" value="reclamo"
                                                class="mt-1 h-4 w-4 accent-cyan-700" @checked(old('claim_type', 'reclamo') === 'reclamo') required>
                                            <span>
                                                <span class="block font-bold">Reclamo</span>
                                                <span class="mt-2 block text-sm leading-6 text-slate-600">Disconformidad
                                                    relacionada con los productos o servicios.</span>
                                            </span>
                                        </span>
                                    </label>
                                    <label class="rounded-lg border border-slate-200 p-5 transition hover:border-cyan-600">
                                        <span class="flex items-start gap-3">
                                            <input type="radio" name="claim_type" value="queja"
                                                class="mt-1 h-4 w-4 accent-cyan-700" @checked(old('claim_type') === 'queja') required>
                                            <span>
                                                <span class="block font-bold">Queja</span>
                                                <span class="mt-2 block text-sm leading-6 text-slate-600">Malestar o
                                                    disconformidad respecto a la atencion recibida.</span>
                                            </span>
                                        </span>
                                    </label>
                                </fieldset>

                                <div class="mt-6 grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="expected_solution" class="{{ $labelClass }}">Pedido o solucion
                                            esperada <span class="text-red-600">*</span></label>
                                        <input id="expected_solution" name="expected_solution"
                                            value="{{ old('expected_solution') }}" class="{{ $fieldClass }}"
                                            placeholder="Ej. cambio de producto, devolucion o reembolso" required>
                                    </div>
                                    <div>
                                        <label for="claim_product" class="{{ $labelClass }}">Producto o servicio
                                            relacionado <span class="text-red-600">*</span></label>
                                        <input id="claim_product" name="claim_product"
                                            value="{{ old('claim_product') }}" class="{{ $fieldClass }}"
                                            placeholder="Ej. Baby Tee Manga Larga Negra" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="claim_description" class="{{ $labelClass }}">Descripcion del
                                            reclamo o queja <span class="text-red-600">*</span></label>
                                        <textarea id="claim_description" name="claim_description" rows="4" class="{{ $fieldClass }}"
                                            placeholder="Indicanos el detalle de tu caso." required>{{ old('claim_description') }}</textarea>
                                    </div>
                                </div>

                                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                                    <button type="button" data-claim-prev class="btn-secondary gap-3 px-8 py-4"><svg
                                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7" aria-hidden="true">
                                            <path d="M19 12H5" />
                                            <path d="m12 19-7-7 7-7" />
                                        </svg> Regresar</button>
                                    <button type="submit" class="btn-primary gap-3 px-8 py-4">Enviar reclamo <svg
                                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.7" aria-hidden="true">
                                            <path d="M5 12h14" />
                                            <path d="m12 5 7 7-7 7" />
                                        </svg></button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <script>
        document.querySelectorAll('[data-claim-wizard]').forEach((wizard) => {
            let currentStep = Number(wizard.dataset.initialStep || 1);
            const steps = Array.from(wizard.querySelectorAll('[data-claim-step]'));
            const guardianFields = wizard.querySelector('[data-guardian-fields]');
            const guardianInputs = Array.from(guardianFields?.querySelectorAll('input, select') || []);

            const setGuardianRequired = () => {
                const isMinor = wizard.querySelector('input[name="is_minor"]:checked')?.value === '1';
                guardianFields?.classList.toggle('hidden', !isMinor);
                guardianFields?.classList.toggle('grid', isMinor);
                guardianInputs.forEach((input) => {
                    input.required = isMinor;
                });
            };

            const paint = () => {
                steps.forEach((step) => {
                    step.classList.toggle('hidden', Number(step.dataset.claimStep) !== currentStep);
                });

                wizard.querySelectorAll('[data-claim-step-indicator]').forEach((indicator) => {
                    const step = Number(indicator.dataset.claimStepIndicator);
                    const active = step <= currentStep;
                    indicator.classList.toggle('bg-cyan-700', active);
                    indicator.classList.toggle('border-cyan-700', active);
                    indicator.classList.toggle('text-white', active);
                    indicator.classList.toggle('bg-white', !active);
                    indicator.classList.toggle('border-slate-300', !active);
                    indicator.classList.toggle('text-slate-400', !active);
                });

                wizard.querySelectorAll('[data-claim-step-label]').forEach((label) => {
                    const active = Number(label.dataset.claimStepLabel) === currentStep;
                    label.classList.toggle('text-slate-950', active);
                    label.classList.toggle('text-slate-500', !active);
                });
            };

            const currentFieldsAreValid = () => {
                const step = wizard.querySelector(`[data-claim-step="${currentStep}"]`);
                const fields = Array.from(step?.querySelectorAll('input, select, textarea') || []);
                const invalid = fields.find((field) => !field.checkValidity());

                if (invalid) {
                    invalid.reportValidity();
                    return false;
                }

                return true;
            };

            wizard.querySelectorAll('[data-claim-next]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!currentFieldsAreValid()) {
                        return;
                    }

                    currentStep = Math.min(4, currentStep + 1);
                    paint();
                    wizard.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });

            wizard.querySelectorAll('[data-claim-prev]').forEach((button) => {
                button.addEventListener('click', () => {
                    currentStep = Math.max(1, currentStep - 1);
                    paint();
                    wizard.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });

            wizard.querySelectorAll('input[name="is_minor"]').forEach((radio) => radio.addEventListener('change',
                setGuardianRequired));
            setGuardianRequired();
            paint();
        });
    </script>
@endsection
