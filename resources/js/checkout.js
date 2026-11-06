// Checkout (web/checkout/details.blade.php y payment.blade.php).
// Datos y entrega: departamento → provincia → distrito, domicilio solo en Lima, agencias por distrito,
// recojo en almacén (sin dirección ni envío) y factura.
// Pago: despliega el panel del medio elegido, sube la captura (reducida en el navegador), exige aceptar los
// términos y evita el doble envío.
import { shrinkImage } from './shrink-image';
import { productTitle } from './product-name';
import { submitOnce } from './submit-once';
import deliveryCoverage from '../data/delivery-coverage.json';

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const title = productTitle;
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
    const pickupRadio = details.querySelector('input[name="delivery"][value="almacen"]');
    const summary = document.querySelector('[data-ck-summary]');
    const coveragePanel = $('[data-delivery-coverage]');
    const coverageMapElement = $('[data-delivery-map]');
    const coverageStatus = $('[data-delivery-coverage-status]');
    const locateButton = $('[data-delivery-locate]');
    const addressSearchButton = $('[data-delivery-address-search]');
    const switchAgencyButton = $('[data-delivery-switch-agency]');
    const switchHint = $('[data-delivery-switch-hint]');
    const deliveryLat = $('[data-delivery-lat]');
    const deliveryLng = $('[data-delivery-lng]');
    const addressInput = details.querySelector('input[name="address"]');
    const addressFields = $('[data-ck-address-fields]');
    const googleMapsKey = details.dataset.googleMapsKey;
    const money = (n) => `S/ ${n.toFixed(2)}`;
    let coverageMap = null;
    let coverageMarker = null;
    let googleMapsPromise = null;
    let coverageMapPromise = null;

    const googleRing = (ring) => ring.map(([lng, lat]) => ({ lat, lng }));
    const loadGoogleMaps = () => {
        if (window.google?.maps) return Promise.resolve();
        if (!googleMapsKey) return Promise.reject(new Error('Google Maps no está configurado.'));
        if (googleMapsPromise) return googleMapsPromise;

        googleMapsPromise = new Promise((resolve, reject) => {
            window.__oversharkGoogleMapsReady = resolve;
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(googleMapsKey)}&callback=__oversharkGoogleMapsReady&loading=async&v=weekly`;
            script.async = true;
            script.onerror = () => reject(new Error('No se pudo cargar Google Maps.'));
            document.head.appendChild(script);
        });
        return googleMapsPromise;
    };
    const pointInRing = (lat, lng, ring) => {
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const [xi, yi] = ring[i];
            const [xj, yj] = ring[j];
            const cross = (lng - xi) * (yj - yi) - (lat - yi) * (xj - xi);
            if (Math.abs(cross) <= 1e-8
                && lng >= Math.min(xi, xj) - 1e-8 && lng <= Math.max(xi, xj) + 1e-8
                && lat >= Math.min(yi, yj) - 1e-8 && lat <= Math.max(yi, yj) + 1e-8) return true;
            if (((yi > lat) !== (yj > lat)) && (lng < ((xj - xi) * (lat - yi)) / ((yj - yi) || Number.EPSILON) + xi)) inside = !inside;
        }
        return inside;
    };
    const isCovered = (lat, lng) => {
        const { coverage, exclusions = [] } = deliveryCoverage;
        if (!pointInRing(lat, lng, coverage.outer)) return false;
        if ((coverage.holes || []).some((hole) => pointInRing(lat, lng, hole))) return false;
        return !exclusions.some((polygon) => pointInRing(lat, lng, polygon));
    };
    const setCoverageStatus = (message, covered = null, offerAgency = false) => {
        coverageStatus.textContent = message;
        coverageStatus.classList.toggle('text-[#147d5b]', covered === true);
        coverageStatus.classList.toggle('text-[#a3261c]', covered === false);
        coverageStatus.classList.toggle('text-[#65717c]', covered === null);
        switchAgencyButton.hidden = !offerAgency;
        switchHint.classList.toggle('hidden', !offerAgency);
    };
    const setDeliveryPoint = (latlng) => {
        const lat = Number(latlng.lat.toFixed(7));
        const lng = Number(latlng.lng.toFixed(7));
        deliveryLat.value = lat;
        deliveryLng.value = lng;
        coverageMarker.setPosition({ lat, lng });
        const covered = isCovered(lat, lng);
        setCoverageStatus(
            covered ? 'Dirección ubicada dentro de la cobertura de delivery.' : 'Dirección fuera de cobertura. Cambia a agencia Shalom o recojo en almacén.',
            covered,
            !covered,
        );
    };
    const clearDeliveryPoint = () => {
        deliveryLat.value = '';
        deliveryLng.value = '';
        setCoverageStatus('Vuelve a marcar tu dirección exacta en el mapa.');
    };
    const initCoverageMap = () => {
        if (coverageMap || !coverageMapElement) return Promise.resolve();
        if (coverageMapPromise) return coverageMapPromise;
        coverageMapPromise = (async () => {
          try {
            await loadGoogleMaps();
            const { coverage, exclusions = [] } = deliveryCoverage;
            coverageMap = new window.google.maps.Map(coverageMapElement, {
                center: { lat: -12.04, lng: -77.03 },
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });
            const boundary = new window.google.maps.Polygon({
                paths: googleRing(coverage.outer),
                strokeColor: '#147d5b', strokeOpacity: 0.95, strokeWeight: 2,
                fillColor: '#27ae7c', fillOpacity: 0.16, map: coverageMap,
            });
            [...(coverage.holes || []), ...exclusions].forEach((polygon) => new window.google.maps.Polygon({
                paths: googleRing(polygon),
                strokeColor: '#a3261c', strokeOpacity: 0.95, strokeWeight: 1.5,
                fillColor: '#dc4138', fillOpacity: 0.38, map: coverageMap,
            }));

            const bounds = new window.google.maps.LatLngBounds();
            coverage.outer.forEach(([lng, lat]) => bounds.extend({ lat, lng }));
            coverageMap.fitBounds(bounds);

            const savedLat = Number(deliveryLat.value);
            const savedLng = Number(deliveryLng.value);
            const hasSavedPoint = deliveryLat.value !== '' && deliveryLng.value !== '' && Number.isFinite(savedLat) && Number.isFinite(savedLng);
            const initial = hasSavedPoint ? { lat: savedLat, lng: savedLng } : { lat: -12.04, lng: -77.03 };
            coverageMarker = new window.google.maps.Marker({ position: initial, draggable: true, map: coverageMap });
            coverageMap.addListener('click', (event) => setDeliveryPoint(event.latLng.toJSON()));
            coverageMarker.addListener('dragend', () => setDeliveryPoint(coverageMarker.getPosition().toJSON()));
            if (hasSavedPoint) setDeliveryPoint(initial);
        } catch (error) {
            coverageMapElement.innerHTML = '<div class="flex h-full items-center justify-center bg-[#f3f6f8] px-6 text-center text-sm text-[#65717c]">Configura GOOGLE_MAPS_API_KEY para mostrar Google Maps.</div>';
            setCoverageStatus(error.message || 'No se pudo mostrar el mapa de Google.', false);
          }
        })();
        return coverageMapPromise;
    };

    const searchDeliveryAddress = async () => {
        const address = addressInput.value.trim();
        if (!address) {
            setCoverageStatus('Escribe tu dirección exacta antes de buscarla.', false);
            addressInput.focus();
            return;
        }

        const department = currentDepartment();
        const province = currentProvince();
        const district = province?.distritos.find((item) => String(item.id) === distrito.value);
        const query = [address, district?.name, province?.name, department?.name, 'Perú'].filter(Boolean).join(', ');
        addressSearchButton.disabled = true;
        setCoverageStatus('Buscando la dirección en Google Maps…');

        try {
            await initCoverageMap();
            if (!coverageMap) return;

            const { Geocoder } = await window.google.maps.importLibrary('geocoding');
            const geocoder = new Geocoder();
            const { results = [] } = await geocoder.geocode({
                address: query,
                componentRestrictions: { country: 'PE' },
                region: 'PE',
            });
            if (!results.length) {
                setCoverageStatus('Google Maps no encontró esa dirección. Prueba agregando calle, número y distrito.', false);
                return;
            }

            const point = results[0].geometry.location.toJSON();
            coverageMap.setCenter(point);
            coverageMap.setZoom(16);
            setDeliveryPoint(point);
        } catch (error) {
            console.error('No se pudo buscar la dirección de delivery:', error);
            setCoverageStatus('No pudimos buscar la dirección. Revisa que Geocoding API esté habilitada para esta clave.', false);
        } finally {
            addressSearchButton.disabled = false;
        }
    };

    addressSearchButton.addEventListener('click', searchDeliveryAddress);
    addressInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && homeRadio.checked) {
            event.preventDefault();
            searchDeliveryAddress();
        }
    });
    switchAgencyButton.addEventListener('click', () => {
        agencyRadio.checked = true;
        agencyRadio.dispatchEvent(new Event('change', { bubbles: true }));
        $('[data-ck-agency-field]').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    locateButton.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setCoverageStatus('Tu navegador no permite detectar la ubicación. Marca el punto en el mapa.', false);
            return;
        }
        locateButton.disabled = true;
        setCoverageStatus('Buscando tu ubicación…');
        navigator.geolocation.getCurrentPosition((position) => {
            const point = { lat: position.coords.latitude, lng: position.coords.longitude };
            initCoverageMap().then(() => {
                if (coverageMap) {
                    coverageMap.setCenter(point);
                    coverageMap.setZoom(16);
                    setDeliveryPoint(point);
                }
                locateButton.disabled = false;
            });
        }, () => {
            setCoverageStatus('No pudimos acceder a tu ubicación. Marca el punto en el mapa.', false);
            locateButton.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });

    const currentDepartment = () => ubigeo.find((d) => String(d.id) === departamento.value);
    const currentProvince = () => currentDepartment()?.provincias.find((p) => String(p.id) === provincia.value);

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

    // Agencias Shalom de la provincia elegida, primero las del distrito (web.checkout.courier-agencies).
    // El buscador filtra por distrito, avenida o nombre; debajo se muestra la dirección de la elegida.
    const agencySearch = $('[data-ck-agency-search]');
    const agencyDetail = $('[data-ck-agency-detail]');
    let agencyRequest = 0;
    let agencyList = [];

    const showAgencyDetail = () => {
        const chosen = agencyList.find((a) => String(a.id) === agency.value);
        agencyDetail.textContent = chosen ? `Dirección: ${chosen.address}` : '';
        if (agency.value) agency.dataset.selected = agency.value;
    };

    const agencyOption = (a) => `<option value="${a.id}"${String(a.id) === agency.dataset.selected ? ' selected' : ''}>${esc(title(a.name))}${a.zone ? ` · ${esc(title(a.zone))}` : ''}</option>`;

    const loadAgencies = async () => {
        const dep = currentDepartment();
        const prov = currentProvince();
        const dist = prov?.distritos.find((x) => String(x.id) === distrito.value);
        if (!dist) {
            agency.innerHTML = '<option value="">Elige primero tu distrito</option>';
            agencyDetail.textContent = '';
            return;
        }
        const request = ++agencyRequest;
        agency.innerHTML = '<option value="">Buscando agencias Shalom…</option>';
        const params = new URLSearchParams({ departamento: dep.name, provincia: prov.name, distrito: dist.name, q: agencySearch.value.trim() });
        try {
            const response = await fetch(`${details.dataset.agenciesUrl}?${params}`, { headers: { Accept: 'application/json' } });
            const { agencies = [] } = await response.json();
            if (request !== agencyRequest) return;
            agencyList = agencies;
            const near = agencies.filter((a) => a.near);
            const others = agencies.filter((a) => !a.near);
            agency.innerHTML = agencies.length
                ? `<option value="">Elige una agencia Shalom (${agencies.length})</option>${near.length ? `<optgroup label="En ${esc(title(dist.name))}">${near.map(agencyOption).join('')}</optgroup>` : ''}${others.length ? `<optgroup label="${near.length ? 'Otras agencias cercanas' : `Agencias en ${esc(title(prov.name))}`}">${others.map(agencyOption).join('')}</optgroup>` : ''}`
                : `<option value="">${agencySearch.value.trim() ? 'Ninguna agencia coincide con tu búsqueda.' : 'No hay agencias Shalom en tu zona. Escríbenos por WhatsApp.'}</option>`;
            showAgencyDetail();
        } catch {
            agency.innerHTML = '<option value="">No pudimos cargar las agencias. Intenta de nuevo.</option>';
        }
    };

    let searchTimer;
    agencySearch.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadAgencies, 250);
    });
    agency.addEventListener('change', showAgencyDetail);

    const syncDelivery = () => {
        homeRadio.disabled = false;
        $('[data-ck-home]').classList.remove('is-disabled');
        $('[data-ck-home-note]').textContent = 'Disponible dentro del área de cobertura marcada en el mapa.';
        addressSearchButton.classList.toggle('hidden', !homeRadio.checked);
        addressFields.classList.toggle('hidden', agencyRadio.checked);
        addressInput.required = homeRadio.checked;
        $('[data-ck-agency-field]').classList.toggle('hidden', !agencyRadio.checked);
        agency.required = agencyRadio.checked;

        // Recojo en almacén: sin dirección. Envío según la entrega (domicilio, agencia Shalom o almacén gratis).
        $('[data-ck-address]').classList.toggle('hidden', pickupRadio.checked);
        coveragePanel.classList.toggle('hidden', !homeRadio.checked);
        if (homeRadio.checked) {
            initCoverageMap();
        }
        if (summary) {
            const subtotal = Number(summary.dataset.subtotal);
            const costs = JSON.parse(summary.dataset.shippingCosts || '{}');
            const chosen = [homeRadio, agencyRadio, pickupRadio].find((radio) => radio.checked)?.value ?? 'domicilio';
            const shipping = Number(costs[chosen] ?? 0);
            summary.querySelector('[data-ck-shipping]').textContent = shipping > 0 ? money(shipping) : 'Gratis';
            summary.querySelector('[data-ck-total]').textContent = money(subtotal + shipping);
        }
    };

    fillProvinces(savedProv);
    fillDistricts(saved || '');
    syncDelivery();
    if (agencyRadio.checked) loadAgencies();

    departamento.addEventListener('change', () => { clearDeliveryPoint(); fillProvinces(); fillDistricts(); syncDelivery(); loadAgencies(); });
    provincia.addEventListener('change', () => { clearDeliveryPoint(); fillDistricts(); loadAgencies(); });
    distrito.addEventListener('change', () => { clearDeliveryPoint(); loadAgencies(); });
    addressInput.addEventListener('input', () => {
        if (homeRadio.checked) clearDeliveryPoint();
    });
    [homeRadio, agencyRadio, pickupRadio].forEach((radio) => radio.addEventListener('change', () => {
        syncDelivery();
        if (agencyRadio.checked) loadAgencies();
    }));

    details.querySelectorAll('[data-ck-invoice]').forEach((radio) => radio.addEventListener('change', () => {
        $('[data-ck-company]').classList.toggle('hidden', radio.value !== 'factura' || !radio.checked);
    }));

    details.addEventListener('submit', (event) => {
        if (!homeRadio.checked) return;
        if (!deliveryLat.value || !deliveryLng.value) {
            event.preventDefault();
            setCoverageStatus('Marca el punto exacto de tu dirección en el mapa para continuar.', false);
            coveragePanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (!isCovered(Number(deliveryLat.value), Number(deliveryLng.value))) {
            event.preventDefault();
            setCoverageStatus('La dirección está fuera de cobertura. Elige agencia Shalom o recojo en almacén.', false, true);
            coveragePanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    // Evita el doble envío y vuelve a habilitar el botón si la clienta regresa con Atrás.
    submitOnce(details);
}

const payment = document.querySelector('[data-checkout-payment]');

if (payment) {
    const $ = (sel) => payment.querySelector(sel);
    const receiptBox = $('[data-ck-receipt]');
    const status = $('[data-ck-receipt-status]');
    const label = $('[data-ck-receipt-label]');
    const preview = $('[data-ck-receipt-preview]');
    const submit = $('[data-ck-submit]');
    const terms = $('[data-ck-terms]');
    const payError = $('[data-ck-pay-error]');
    const culqiToken = $('[data-ck-culqi-token]');
    let hasReceipt = payment.dataset.hasReceipt === '1';
    let uploading = false;

    const selectedMethod = () => payment.querySelector('[data-ck-method]:checked');
    // Con qué se paga ahora: el método elegido, o el del separo en contraentrega (sin separo: nada).
    const payWith = () => {
        const method = selectedMethod();
        if (method?.dataset.kind !== 'cod') return method;
        return payment.querySelector('[data-ck-deposit]:checked');
    };
    const needsReceipt = () => payWith()?.dataset.receipt === '1';
    const kindNow = () => payWith()?.dataset.kind;
    // La tarjeta se cobra con Culqi; Yape (QR de Ligo Pay) se activará al integrar su API.
    const byCulqi = () => kindNow() === 'card';
    const isCod = () => selectedMethod()?.dataset.kind === 'cod';
    const amountNow = () => Number(isCod() ? payment.dataset.deposit : payment.dataset.total);
    // Panel visible del medio con el que se paga (el del método, o el del separo en contraentrega).
    const activePanel = () => (isCod()
        ? payment.querySelector(`[data-ck-deposit-panel="${payWith()?.value}"]`)
        : payment.querySelector(`[data-ck-panel="${selectedMethod()?.value}"]`));

    const render = () => {
        const method = selectedMethod();
        const deposit = payment.querySelector('[data-ck-deposit]:checked');
        payment.querySelectorAll('[data-ck-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.ckPanel !== method?.value));
        payment.querySelectorAll('[data-ck-deposit-panel]').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.ckDepositPanel !== deposit?.value));
        receiptBox.classList.toggle('hidden', !needsReceipt());
        const unavailable = payWith()?.dataset.available === '0';
        submit.disabled = uploading || !method || unavailable || (needsReceipt() && !hasReceipt) || !terms?.checked;
        const amount = `S/ ${amountNow().toFixed(2)}`;
        submit.textContent = kindNow() === 'card' ? `PAGAR ${amount} CON TARJETA` : (kindNow() === 'yape' ? `PAGAR ${amount} CON YAPE` : 'REALIZAR PEDIDO');
    };

    payment.querySelectorAll('[data-ck-method], [data-ck-deposit]').forEach((radio) => radio.addEventListener('change', render));
    terms?.addEventListener('change', render);

    const showPayError = (message) => {
        payError.textContent = message;
        payError.classList.toggle('hidden', !message);
    };

    // Solo números en los campos de la tarjeta; el número se agrupa de a 4.
    payment.querySelectorAll('[data-card-number], [data-card-cvv], [data-card-month], [data-card-year], [data-card-doc-number]').forEach((input) => {
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '');
            input.value = input.matches('[data-card-number]') ? digits.slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 ') : digits;
        });
    });

    const luhn = (number) => {
        let sum = 0;
        [...number].reverse().forEach((d, i) => {
            let n = Number(d);
            if (i % 2) n = n * 2 > 9 ? n * 2 - 9 : n * 2;
            sum += n;
        });
        return sum % 10 === 0;
    };

    // Revisa el formulario de tarjeta visible y arma lo que se envía a Culqi (o devuelve el error para la clienta).
    const culqiRequest = () => {
        const panel = activePanel();
        const value = (sel) => panel?.querySelector(sel)?.value.trim() ?? '';
        const number = value('[data-card-number]').replace(/\s/g, '');
        const cvv = value('[data-card-cvv]');
        const month = value('[data-card-month]').padStart(2, '0');
        const year = value('[data-card-year]');
        const now = new Date();
        if (number.length < 13 || !luhn(number)) return { error: 'Revisa el número de tu tarjeta.' };
        if (!/^\d{3,4}$/.test(cvv)) return { error: 'El CVV tiene 3 o 4 dígitos (detrás de tu tarjeta).' };
        if (!(Number(month) >= 1 && Number(month) <= 12)) return { error: 'El mes de vencimiento va de 01 a 12.' };
        if (!/^\d{4}$/.test(year) || Number(year) < now.getFullYear() || (Number(year) === now.getFullYear() && Number(month) < now.getMonth() + 1)) return { error: 'Revisa la fecha de vencimiento: la tarjeta parece vencida.' };
        if (value('[data-card-name]').length < 3) return { error: 'Escribe el nombre como aparece en tu tarjeta.' };
        return { path: '/tokens', body: { card_number: number, cvv, expiration_month: month, expiration_year: year, email: payment.dataset.email } };
    };

    // Tarjeta: los datos van directo de este navegador a Culqi (llave pública) y vuelve un token; solo el
    // token se envía con el pedido y el servidor cobra (CulqiGateway). La web nunca recibe la tarjeta.
    const payWithCulqi = async () => {
        const request = culqiRequest();
        if (request.error) {
            showPayError(request.error);
            return;
        }
        submit.disabled = true;
        submit.textContent = 'PROCESANDO PAGO…';
        // Modo simulación: no se llama a Culqi; 4000 0000 0000 0002 simula una tarjeta rechazada.
        if (payment.dataset.simulate === '1') {
            culqiToken.value = request.body.card_number === '4000000000000002' ? 'sim_tkn_declined' : 'sim_tkn_ok';
            payment.submit();
            return;
        }
        try {
            const response = await fetch(`${payment.dataset.culqiUrl}${request.path}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${payment.dataset.culqiKey}` },
                body: JSON.stringify(request.body),
            });
            const data = await response.json();
            if (!response.ok || !data.id) throw new Error(data.user_message || '');
            culqiToken.value = data.id;
            payment.submit();
        } catch (error) {
            showPayError(error.message || 'No pudimos procesar tu pago. Revisa los datos e intenta de nuevo.');
            render();
        }
    };

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
        showPayError('');
        if (byCulqi() && !culqiToken.value) {
            event.preventDefault();
            if (!payment.dataset.culqiKey && payment.dataset.simulate !== '1') {
                showPayError('El pago con tarjeta (Culqi) aún no está activo en la tienda. Por ahora elige otro medio de pago.');
                return;
            }
            payWithCulqi();
            return;
        }
        // Evita el doble envío; el servidor además reutiliza el pedido del mismo token.
        submit.disabled = true;
        submit.textContent = 'PROCESANDO PEDIDO…';
    });

    render();
}
