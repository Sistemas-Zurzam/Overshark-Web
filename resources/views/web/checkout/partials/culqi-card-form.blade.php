{{--
    Formulario de tarjeta (Culqi). Los campos NO tienen name: nunca se envían a nuestro servidor. checkout.js
    los manda directo a Culqi con la llave pública (POST /tokens) y solo el token viaja con el pedido.
    $prefix hace únicos los id (el formulario aparece en "Tarjeta" y en el separo de contraentrega).
--}}
<div class="flex flex-col gap-5" data-culqi-card>
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="os-ck-field">Tipo de documento
            <select class="os-ck-input" data-card-doc-type aria-label="Tipo de documento">
                @foreach (['DNI', 'CE'] as $docType)
                    <option value="{{ $docType }}" @selected(($customer['doc_type'] ?? 'DNI') === $docType)>{{ $docType }}</option>
                @endforeach
            </select>
        </label>
        <label class="os-ck-field">Número del documento
            <input type="text" class="os-ck-input" value="{{ $customer['doc_number'] ?? '' }}" inputmode="numeric" maxlength="12" autocomplete="off" data-card-doc-number>
        </label>
    </div>

    <div class="flex flex-col gap-4">
        <p class="border-b border-[#0b1015] pb-3 text-[13px] font-extrabold uppercase tracking-[.5px]">Datos de la tarjeta</p>
        <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_200px]">
            <label class="os-ck-field" for="{{ $prefix }}-number">
                <span>Número de tarjeta <span class="text-[#d22]" aria-hidden="true">*</span></span>
                <input id="{{ $prefix }}-number" type="text" class="os-ck-input tracking-[1px]" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" maxlength="23" data-card-number>
            </label>
            <label class="os-ck-field" for="{{ $prefix }}-cvv">
                <span>CVV - Código de seguridad <span class="text-[#d22]" aria-hidden="true">*</span></span>
                <input id="{{ $prefix }}-cvv" type="password" class="os-ck-input" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" data-card-cvv>
            </label>
        </div>
        <div class="grid gap-4 grid-cols-2">
            <label class="os-ck-field" for="{{ $prefix }}-month">
                <span>Mes de vencimiento <span class="text-[#d22]" aria-hidden="true">*</span></span>
                <input id="{{ $prefix }}-month" type="text" class="os-ck-input" inputmode="numeric" autocomplete="cc-exp-month" placeholder="MM" maxlength="2" data-card-month>
            </label>
            <label class="os-ck-field" for="{{ $prefix }}-year">
                <span>Año de vencimiento <span class="text-[#d22]" aria-hidden="true">*</span></span>
                <input id="{{ $prefix }}-year" type="text" class="os-ck-input" inputmode="numeric" autocomplete="cc-exp-year" placeholder="AAAA" maxlength="4" data-card-year>
            </label>
        </div>
        <label class="os-ck-field" for="{{ $prefix }}-name">
            <span>Nombre impreso en la tarjeta <span class="text-[#d22]" aria-hidden="true">*</span></span>
            <input id="{{ $prefix }}-name" type="text" class="os-ck-input uppercase" autocomplete="cc-name" placeholder="Como aparece en la tarjeta" maxlength="60" data-card-name>
        </label>
    </div>
    <p class="text-[12px] leading-[1.45] text-[#65717c]">Pago seguro procesado por Culqi. Overshark no ve ni guarda los datos de tu tarjeta.</p>
</div>
