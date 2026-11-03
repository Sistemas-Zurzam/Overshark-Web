// Checkout (web/checkout/details.blade.php y payment.blade.php).
// Datos y entrega: departamento → provincia → distrito, domicilio solo en Lima, agencias por distrito y factura.
// Pago: muestra el panel del medio elegido, sube la captura (reducida en el navegador) y evita el doble envío.
import { shrinkImage } from './shrink-image';

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const title = (s) => String(s ?? '').toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());
const options = (items, placeholder, selected) => `<option value="">${placeholder}</option>${items
    .map((item) => `<option value="${item.id}"${String(item.id) === String(selected) ? ' selected' : ''}>${esc(title(item.name))}</option>`).join('')}`;

const details = document.querySelector('[data-checkout-details]');

if (details) {
    const ubigeo = JSON.parse(details.dataset.ubigeo || '[]');
    const $ = (sel) => details.querySelector(sel);
    const departamento = $('[data-ck-departamento]');
    const provincia = $('[data-ck-provincia]');
    const distrito = $('[data-ck-distrito]');
    const agency = $('[data-ck-agency]');
    const homeRadio = details.querySelector('input[name="delivery"][value="domicilio"]');
    const agencyRadio = details.querySelector('input[name="delivery"][value="agencia"]');

    const currentDepartment = () => ubigeo.find((d) => String(d.id) === departamento.value);
    const currentProvince = () => currentDepartment()?.provincias.find((p) => String(p.id) === provincia.value);
    const isLima = () => (currentDepartment()?.name ?? '').toUpperCase() === 'LIMA';

    // Con el distrito guardado, se reconstruyen departamento y provincia.
    const saved = Number(details.dataset.distrito || 0);
    let savedDep = '';
    let savedProv = '';
    ubigeo.forEach((d) => d.provincias.forEach((p) => {
        if (p.distritos.some((x) => x.id === saved)) {
            savedDep = d.id;
            savedProv = p.id;
        }
    }));

    departamento.innerHTML = options(ubigeo, 'Elige', savedDep);

    const fillProvinces = (selected = '') => {
        const dep = currentDepartment();
        provincia.innerHTML = options(dep?.provincias ?? [], 'Elige', selected);
        provincia.disabled = !dep;
    };
    const fillDistricts = (selected = '') => {
        const prov = currentProvince();
        distrito.innerHTML = options(prov?.distritos ?? [], 'Selecciona tu distrito', selected);
        distrito.disabled = !prov;
    };

    // Agencias de recojo cercanas al distrito elegido (web.checkout.courier-agencies).
    let agencyRequest = 0;
    const loadAgencies = async () => {
        const dep = currentDepartment();
        const prov = currentProvince();
        const dist = prov?.distritos.find((x) => String(x.id) === distrito.value);
        if (!dist) {
            agency.innerHTML = '<option value="">Elige primero tu distrito</option>';
            return;
        }
        const request = ++agencyRequest;
        agency.innerHTML = '<option value="">Buscando agencias…</option>';
        const params = new URLSearchParams({ departamento: dep.name, provincia: prov.name, distrito: dist.name });
        try {
            const response = await fetch(`${details.dataset.agenciesUrl}?${params}`, { headers: { Accept: 'application/json' } });
            const { agencies = [] } = await response.json();
            if (request !== agencyRequest) return;
            const selected = agency.dataset.selected;
            agency.innerHTML = agencies.length
                ? `<option value="">Elige una agencia</option>${agencies.map((a) => {
                    const label = `${a.name} — ${a.address}`;
                    return `<option value="${esc(label)}"${label === selected ? ' selected' : ''}>${esc(title(a.name))} — ${esc(a.address)}</option>`;
                }).join('')}`
                : '<option value="">No encontramos agencias cerca. Escríbenos por WhatsApp.</option>';
        } catch {
            agency.innerHTML = '<option value="">No pudimos cargar las agencias. Intenta de nuevo.</option>';
        }
    };

    const syncDelivery = () => {
        // Fuera de Lima solo hay recojo en agencia.
        const lima = !departamento.value || isLima();
        homeRadio.disabled = !lima;
        $('[data-ck-home]').classList.toggle('is-disabled', !lima);
        $('[data-ck-home-note]').textContent = lima ? 'Solo en Lima. Entrega estimada: 2 a 4 días hábiles.' : 'No disponible fuera de Lima: elige recojo en agencia.';
        if (!lima && homeRadio.checked) agencyRadio.checked = true;
        $('[data-ck-agency-field]').classList.toggle('hidden', !agencyRadio.checked);
        agency.required = agencyRadio.checked;
    };

    fillProvinces(savedProv);
    fillDistricts(saved || '');
    syncDelivery();
    if (agencyRadio.checked) loadAgencies();

    departamento.addEventListener('change', () => { fillProvinces(); fillDistricts(); syncDelivery(); loadAgencies(); });
    provincia.addEventListener('change', () => { fillDistricts(); loadAgencies(); });
    distrito.addEventListener('change', loadAgencies);
    [homeRadio, agencyRadio].forEach((radio) => radio.addEventListener('change', () => {
        syncDelivery();
        if (agencyRadio.checked) loadAgencies();
    }));

    details.querySelectorAll('[data-ck-invoice]').forEach((radio) => radio.addEventListener('change', () => {
        $('[data-ck-company]').classList.toggle('hidden', radio.value !== 'factura' || !radio.checked);
    }));

    details.addEventListener('submit', () => {
        details.querySelector('button[type="submit"]').disabled = true;
    });
}

const payment = document.querySelector('[data-checkout-payment]');

if (payment) {
    const $ = (sel) => payment.querySelector(sel);
    const receiptBox = $('[data-ck-receipt]');
    const status = $('[data-ck-receipt-status]');
    const label = $('[data-ck-receipt-label]');
    const preview = $('[data-ck-receipt-preview]');
    const submit = $('[data-ck-submit]');
    let hasReceipt = payment.dataset.hasReceipt === '1';
    let uploading = false;

    const selectedMethod = () => payment.querySelector('[data-ck-method]:checked');
    const needsReceipt = () => selectedMethod()?.dataset.receipt === '1';

    const render = () => {
        const method = selectedMethod();
        payment.querySelectorAll('[data-ck-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.ckPanel !== method?.value));
        receiptBox.classList.toggle('hidden', !needsReceipt());
        submit.disabled = uploading || !method || (needsReceipt() && !hasReceipt);
    };

    payment.querySelectorAll('[data-ck-method]').forEach((radio) => radio.addEventListener('change', render));

    payment.querySelectorAll('[data-ck-copy]').forEach((button) => button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.ckCopy);
            button.textContent = 'Número copiado ✓';
        } catch {
            button.textContent = button.dataset.ckCopy;
        }
    }));

    $('[data-ck-receipt-input]').addEventListener('change', async (event) => {
        const [file] = event.target.files;
        if (!file) return;
        uploading = true;
        render();
        status.textContent = 'Subiendo captura…';

        const body = new FormData();
        body.append('payment_receipt', await shrinkImage(file, 1600));
        body.append('_token', payment.querySelector('input[name="_token"]').value);

        try {
            const response = await fetch(payment.dataset.receiptUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const data = await response.json();
            hasReceipt = true;
            preview.src = data.url;
            preview.classList.remove('hidden');
            label.textContent = 'Captura subida ✓ · Cambiar';
            status.textContent = '';
        } catch {
            status.textContent = 'No pudimos subir la captura. Prueba con otra imagen.';
        } finally {
            uploading = false;
            event.target.value = '';
            render();
        }
    });

    payment.addEventListener('submit', (event) => {
        if (submit.disabled) {
            event.preventDefault();
            return;
        }
        // Evita el doble envío; el servidor además reutiliza el pedido del mismo token.
        submit.disabled = true;
        submit.textContent = 'CONFIRMANDO…';
    });

    render();
}
