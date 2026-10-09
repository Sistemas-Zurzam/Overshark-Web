// Probador "Arma tu outfit con IA". El servidor guarda la foto y el modelo en la sesión; aquí solo se
// refleja ese estado y se arma la selección de prendas (guardada en sessionStorage del navegador).

const root = document.querySelector('[data-outfit-builder]');
const dataElement = document.getElementById('outfit-builder-data');

if (root && dataElement) {
    initOutfitBuilder(JSON.parse(dataElement.textContent));
}

function initOutfitBuilder(data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const storageKey = 'overshark.outfit.selection';
    const maxExtras = 3;
    const slotLabels = { top: 'Superior', bottom: 'Inferior', extra: 'Complemento' };
    const variantIndex = new Map();

    data.products.forEach((product) => {
        product.variants.forEach((variant) => variantIndex.set(variant.id, { product, variant }));
    });

    let state = data.state;
    let pollTimer = null;
    let pollAttempts = 0;
    let selection = restoreSelection();

    const $ = (selector, scope = document) => scope.querySelector(selector);
    const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
    const formatPrice = (value) => (value === null || value === undefined ? 'Precio por confirmar' : `S/ ${Number(value).toFixed(2)}`);
    const variantLabel = (variant) => [variant.size, variant.color].filter(Boolean).join(' · ');

    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    };

    async function request(url, { method = 'POST', body, json } = {}) {
        const headers = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
        let payload = body;

        if (json !== undefined) {
            headers['Content-Type'] = 'application/json';
            payload = JSON.stringify(json);
        }

        try {
            const response = await fetch(url, { method, headers, body: payload, credentials: 'same-origin' });
            let responseData = {};

            try {
                responseData = await response.json();
            } catch {
                responseData = {};
            }

            return { ok: response.ok, status: response.status, data: responseData };
        } catch {
            return { ok: false, status: 0, data: {} };
        }
    }

    function errorMessage(result, fallback) {
        if (result.status === 0) {
            return 'No pudimos conectar con la tienda. Revisa tu conexión e inténtalo de nuevo.';
        }
        if (result.status === 429) {
            return 'Demasiados intentos seguidos. Espera un minuto y vuelve a intentarlo.';
        }
        if (result.status === 419) {
            return 'Tu sesión expiró. Recarga la página para continuar.';
        }
        const errors = result.data?.errors;
        if (errors) {
            return Object.values(errors).flat()[0] ?? fallback;
        }
        return result.data?.message || fallback;
    }

    function applyState(nextState) {
        if (nextState && 'photo_url' in nextState) {
            state = nextState;
        }
        renderPhoto();
        renderModel();
        renderSteps();
        renderSelection();

        if (localMode) {
            ensureAnalysis();
        }
    }

    // ---- Pasos ----
    function renderSteps() {
        const hasPhoto = Boolean(state.photo_url);
        const hasModel = localMode ? fitting.status === 'ready' : state.model?.status === 'completed';
        const active = !hasPhoto ? 1 : (!hasModel && (data.aiAvailable || localMode) ? 2 : 3);
        const done = { 1: hasPhoto, 2: hasModel, 3: false };

        $$('[data-step]').forEach((step) => {
            const number = Number(step.dataset.step);
            const stepState = done[number] ? 'done' : (number === active ? 'active' : 'pending');
            step.dataset.stepState = stepState;
            if (number === active) {
                step.setAttribute('aria-current', 'step');
            } else {
                step.removeAttribute('aria-current');
            }
            $('[data-step-status]', step).textContent = { done: 'completado', active: 'paso actual', pending: 'pendiente' }[stepState];
        });
    }

    // ---- Foto ----
    const photoPreview = $('[data-photo-preview]');
    const photoEmpty = $('[data-photo-empty]');
    const photoInput = $('[data-photo-input]');
    const photoStatus = $('[data-photo-status]');
    const photoDelete = $('[data-photo-delete]');

    function renderPhoto() {
        const hasPhoto = Boolean(state.photo_url);
        if (hasPhoto && photoPreview.getAttribute('src') !== state.photo_url) {
            photoPreview.src = state.photo_url;
        }
        photoPreview.classList.toggle('hidden', !hasPhoto);
        photoEmpty.classList.toggle('hidden', hasPhoto);
        photoDelete.classList.toggle('hidden', !hasPhoto);
        $('[data-photo-camera-label]').textContent = hasPhoto ? 'Tomar otra foto' : 'Tomar foto';
        $('[data-photo-upload-label]').textContent = hasPhoto ? 'Reemplazar foto' : 'Subir foto';
    }

    async function uploadPhoto(file, fileName) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            photoStatus.textContent = 'Usa una imagen JPEG, PNG o WebP.';
            return;
        }
        if (file.size > data.maxPhotoBytes) {
            photoStatus.textContent = 'La foto no puede superar los 10 MB.';
            return;
        }

        const formData = new FormData();
        formData.append('photo', file, fileName ?? file.name);
        photoStatus.textContent = 'Subiendo foto…';

        const result = await request(data.routes.photoStore, { body: formData });

        if (!result.ok) {
            photoStatus.textContent = errorMessage(result, 'No pudimos subir la foto. Inténtalo de nuevo.');
            return;
        }

        stopPolling();
        photoStatus.textContent = 'Foto lista.';
        applyState(result.data);
    }

    photoInput.addEventListener('change', () => {
        const [file] = photoInput.files;
        if (file) {
            clearTurn();
            uploadPhoto(file);
        }
        photoInput.value = '';
    });

    photoDelete.addEventListener('click', async () => {
        photoStatus.textContent = 'Eliminando foto…';
        const result = await request(data.routes.photoDestroy, { method: 'DELETE' });
        if (!result.ok) {
            photoStatus.textContent = errorMessage(result, 'No pudimos eliminar la foto.');
            return;
        }
        stopPolling();
        clearTurn();
        photoPreview.removeAttribute('src');
        photoStatus.textContent = 'Foto eliminada.';
        applyState(result.data);
        $('[data-photo-camera]').focus();
    });

    // ---- Cámara ----
    const cameraDialog = $('[data-camera-dialog]');
    const cameraVideo = $('[data-camera-video]');
    const cameraCapture = $('[data-camera-capture]');
    const cameraStatus = $('[data-camera-status]');
    let cameraStream = null;
    let capturedBlob = null;

    function setCameraMode(mode) {
        const captured = mode === 'captured';
        cameraVideo.classList.toggle('hidden', captured);
        cameraCapture.classList.toggle('hidden', !captured);
        $('[data-camera-shoot]').classList.toggle('hidden', captured);
        $('[data-camera-retake]').classList.toggle('hidden', !captured);
        $('[data-camera-confirm]').classList.toggle('hidden', !captured);
    }

    function stopCamera() {
        cameraStream?.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        cameraVideo.srcObject = null;
        if (cameraCapture.src) {
            URL.revokeObjectURL(cameraCapture.src);
            cameraCapture.removeAttribute('src');
        }
        capturedBlob = null;
    }

    function cameraUnavailable(message) {
        photoStatus.textContent = `${message} Puedes subir un archivo con «Subir foto».`;
        photoInput.focus();
    }

    $('[data-photo-camera]').addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia || typeof cameraDialog.showModal !== 'function') {
            cameraUnavailable('Tu navegador no permite usar la cámara.');
            return;
        }

        setCameraMode('live');
        cameraStatus.textContent = 'Solicitando acceso a la cámara…';
        cameraDialog.showModal();

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            cameraVideo.srcObject = cameraStream;
            await cameraVideo.play();
            cameraStatus.textContent = 'Encuadra tu rostro y la parte superior del cuerpo, y pulsa «Capturar».';
        } catch {
            cameraDialog.close();
            cameraUnavailable('No pudimos acceder a la cámara.');
        }
    });

    $('[data-camera-shoot]').addEventListener('click', () => {
        if (!cameraVideo.videoWidth) {
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = cameraVideo.videoWidth;
        canvas.height = cameraVideo.videoHeight;
        canvas.getContext('2d').drawImage(cameraVideo, 0, 0);
        canvas.toBlob((blob) => {
            if (!blob) {
                cameraStatus.textContent = 'No pudimos capturar la imagen. Inténtalo de nuevo.';
                return;
            }
            capturedBlob = blob;
            cameraCapture.src = URL.createObjectURL(blob);
            setCameraMode('captured');
            cameraStatus.textContent = '¿Te gusta? Usa esta foto o repítela.';
            $('[data-camera-confirm]').focus();
        }, 'image/jpeg', 0.92);
    });

    $('[data-camera-retake]').addEventListener('click', () => {
        if (cameraCapture.src) {
            URL.revokeObjectURL(cameraCapture.src);
            cameraCapture.removeAttribute('src');
        }
        capturedBlob = null;
        setCameraMode('live');
        cameraStatus.textContent = 'Pulsa «Capturar» cuando estés listo.';
        $('[data-camera-shoot]').focus();
    });

    $('[data-camera-confirm]').addEventListener('click', () => {
        const blob = capturedBlob;
        cameraDialog.close();
        if (blob) {
            clearTurn();
            uploadPhoto(new File([blob], 'camara.jpg', { type: 'image/jpeg' }));
        }
    });

    $('[data-camera-cancel]').addEventListener('click', () => cameraDialog.close());
    cameraDialog.addEventListener('close', stopCamera);
    window.addEventListener('pagehide', stopCamera);

    // ---- Modelo ----
    const modelMessage = $('[data-model-message]');
    const modelImage = $('[data-model-image]');
    const modelExample = $('[data-model-example]');
    const modelExampleLabel = $('[data-model-example-label]');
    const modelBadge = $('[data-model-badge]');
    const modelGenerate = $('[data-model-generate]');
    const modelConsent = $('[data-model-consent]');
    const modelDescription = $('[data-model-description]');
    let modelError = null;

    // ---- Probador local (sin IA externa): MediaPipe en el navegador, ver virtual-fitting.js ----
    const fittingCanvas = $('[data-fitting-canvas]');
    const fitting = { status: 'idle', photoUrl: null, analysis: null, module: null, garments: new Map(), renderToken: 0, applied: 0 };
    const localMode = !data.aiAvailable && Boolean(fittingCanvas);

    async function ensureAnalysis() {
        const url = state.photo_url;

        if (!url) {
            Object.assign(fitting, { status: 'idle', photoUrl: null, analysis: null, applied: 0 });
            return;
        }

        if (fitting.photoUrl === url) {
            return;
        }

        Object.assign(fitting, { status: 'loading', photoUrl: url, analysis: null, applied: 0 });
        renderModel();

        try {
            fitting.module ??= await import('./virtual-fitting.js');
            const engine = await fitting.module.loadFittingEngine();
            const image = await fitting.module.loadImage(url);
            const analysis = await fitting.module.analyzePerson(engine, image);

            if (fitting.photoUrl !== url) {
                return;
            }

            fitting.analysis = analysis;
            fitting.status = analysis ? 'ready' : 'nopose';
        } catch {
            if (fitting.photoUrl === url) {
                fitting.status = 'error';
            }
        }

        renderSteps();
        renderSelection();
        renderModel();

        if (viewerOpen) {
            openViewer();
        }
    }

    async function garmentFor({ product, variant }) {
        if (product.slot === 'extra') {
            return null;
        }

        if (fitting.garments.has(variant.id)) {
            return fitting.garments.get(variant.id);
        }

        const { loadImage, anchorsFromAlpha, anchorsForIllustration } = fitting.module;
        let garment = null;

        try {
            if (variant.fitting_image) {
                const image = await loadImage(variant.fitting_image);
                garment = { slot: product.slot, image, anchors: anchorsFromAlpha(image, product.slot) };
            } else {
                const template = document.querySelector(`[data-garment-template="${product.garment}"]`);
                const svg = template?.content.querySelector('svg')?.cloneNode(true);

                if (svg) {
                    svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                    svg.setAttribute('width', '600');
                    svg.setAttribute('height', '600');
                    svg.querySelector('[data-garment-fill]')?.setAttribute('fill', variant.swatch);
                    // Sin contornos: al deformarse se dibujarían como cuñas oscuras sobre el torso.
                    svg.querySelectorAll('path').forEach((path) => path.setAttribute('stroke', 'none'));
                    const blobUrl = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(svg)], { type: 'image/svg+xml' }));
                    const image = await loadImage(blobUrl);
                    garment = { slot: product.slot, image, anchors: anchorsForIllustration(product.garment, 600) };
                }
            }
        } catch {
            garment = null;
        }

        if (garment?.anchors) {
            fitting.garments.set(variant.id, garment);
            return garment;
        }

        return null;
    }

    async function drawFitting() {
        if (!localMode || fitting.status !== 'ready') {
            return;
        }

        const token = ++fitting.renderToken;
        const entries = [selection.top, selection.bottom].filter(Boolean).map((id) => variantIndex.get(id));
        const garments = (await Promise.all(entries.map(garmentFor))).filter(Boolean);

        if (token !== fitting.renderToken || fitting.status !== 'ready') {
            return;
        }

        fitting.module.renderOutfit(fittingCanvas, fitting.analysis, garments);
        fitting.applied = garments.length;
    }

    function renderLocalStage(hasPhoto) {
        const ready = fitting.status === 'ready';
        const messages = {
            idle: 'Sube o toma una foto de frente, de cuerpo entero o hasta las rodillas, para empezar.',
            loading: 'Preparando el probador en tu dispositivo… la primera vez puede tardar unos segundos.',
            nopose: 'No detectamos bien tu cuerpo. Usa una foto de frente, de una sola persona, con hombros y cadera visibles.',
            error: 'No pudimos iniciar el probador en este navegador. Prueba con Chrome, Edge o Safari actualizados.',
        };

        modelMessage.textContent = !hasPhoto
            ? messages.idle
            : ready
                ? (fitting.applied
                    ? 'Así se vería tu outfit. Es una simulación aproximada hecha en tu dispositivo: el calce real puede variar.'
                    : 'Elige un polo, camisa o pantalón y lo verás al instante sobre tu foto.')
                : messages[fitting.status] ?? messages.loading;
        modelDescription.textContent = 'Tu foto se procesa en tu propio dispositivo: elige prendas y míralas sobre ti al instante.';

        fittingCanvas.classList.toggle('hidden', !ready);
        modelImage.classList.toggle('hidden', !hasPhoto || ready);
        modelImage.classList.add('os-stage-photo');
        modelImage.classList.toggle('opacity-50', fitting.status === 'loading');
        if (hasPhoto && !ready && modelImage.getAttribute('src') !== state.photo_url) {
            modelImage.src = state.photo_url;
        }
        modelImage.alt = 'Tu foto';
        modelExample?.classList.toggle('hidden', hasPhoto);
        if (modelExampleLabel) {
            modelExampleLabel.classList.remove('hidden');
            modelExampleLabel.textContent = !hasPhoto ? 'Modelo de ejemplo' : (ready && fitting.applied ? 'Simulación aproximada' : 'Tu foto');
        }
        modelBadge.classList.add('hidden');
        modelBadge.classList.remove('flex');
    }

    function renderModel() {
        const hasPhoto = Boolean(state.photo_url);

        if (localMode) {
            renderLocalStage(hasPhoto);
            return;
        }

        const status = state.model?.status ?? null;
        const hasModelImage = Boolean(state.model?.image_url);
        const completed = status === 'completed' && hasModelImage;
        let message;
        let label = 'Generar mi modelo';
        let canGenerate = false;

        if (!hasPhoto) {
            message = 'Sube o toma una foto para empezar.';
        } else if (!data.aiAvailable) {
            message = 'Esta es tu foto. Para ver las prendas puestas sobre ella falta activar el probador con IA en la tienda; mientras tanto puedes elegir tu outfit y agregarlo al carrito.';
        } else if (status === 'processing') {
            message = hasModelImage
                ? 'Probándote el outfit… suele tardar entre 10 y 40 segundos.'
                : 'Generando tu modelo… puede tardar unos segundos.';
            label = 'Procesando…';
        } else if (completed) {
            message = modelError ?? state.model.message ?? (state.model.variant_ids?.length
                ? 'Así te queda el outfit. Cambia de prenda y vuelve a pulsar «Probar outfit».'
                : 'Tu modelo está listo. Elige prendas y pulsa «Probar outfit».');
            label = 'Generar de nuevo';
            canGenerate = true;
        } else if (status === 'failed' || modelError) {
            message = modelError ?? state.model?.message ?? 'No se pudo generar tu modelo.';
            label = 'Reintentar';
            canGenerate = true;
        } else {
            message = 'Tu foto está lista. Acepta el envío y genera tu modelo.';
            canGenerate = true;
        }

        modelMessage.textContent = message;
        modelDescription.textContent = completed
            ? 'Tu modelo está listo. Prueba diferentes prendas y arma tu outfit.'
            : 'Genera tu modelo a partir de tu foto para probarte las prendas.';
        modelGenerate.disabled = !canGenerate || (modelConsent ? !modelConsent.checked : true);
        $('[data-model-generate-label]').textContent = label;

        // Sin modelo generado, el escenario muestra la foto del cliente tal cual (sin prendas aplicadas).
        // Con IA se muestra el último resultado (también mientras se procesa el siguiente).
        const stageSrc = hasModelImage ? state.model.image_url : (hasPhoto ? state.photo_url : null);
        if (stageSrc && modelImage.getAttribute('src') !== stageSrc) {
            modelImage.src = stageSrc;
        }
        modelImage.alt = state.model?.variant_ids?.length ? 'Tu foto con el outfit probado' : 'Tu foto';
        modelImage.classList.toggle('hidden', !stageSrc);
        modelImage.classList.add('os-stage-photo');
        modelImage.classList.toggle('opacity-50', status === 'processing');
        modelExample?.classList.toggle('hidden', Boolean(stageSrc));
        if (modelExampleLabel) {
            const applied = hasModelImage && state.model.variant_ids?.length;
            modelExampleLabel.classList.toggle('hidden', Boolean(applied));
            modelExampleLabel.textContent = hasPhoto ? 'Tu foto · prendas sin aplicar' : 'Modelo de ejemplo';
        }
        modelBadge.classList.toggle('hidden', !(completed && state.model.variant_ids?.length));
        modelBadge.classList.toggle('flex', Boolean(completed && state.model.variant_ids?.length));
        $('[data-model-rotate]')?.classList.toggle('hidden', !completed);
        $('[data-model-rotate]')?.classList.toggle('flex', Boolean(completed));

        if (status === 'processing') {
            startPolling();
        }
    }

    modelConsent?.addEventListener('change', renderModel);

    async function runModelAction(url, json) {
        modelError = null;
        modelGenerate.disabled = true;
        modelMessage.textContent = 'Enviando…';
        const result = await request(url, { json });

        if (!result.ok) {
            modelError = errorMessage(result, 'No se pudo completar la operación.');
            applyState(result.data);
            return;
        }

        applyState(result.data);
    }

    modelGenerate.addEventListener('click', () => {
        runModelAction(data.routes.modelGenerate, { consent: modelConsent?.checked ? 1 : 0 });
    });

    function startPolling() {
        if (pollTimer) {
            return;
        }
        pollTimer = window.setInterval(async () => {
            pollAttempts += 1;
            const result = await request(data.routes.modelStatus, { method: 'GET' });
            if (result.ok) {
                state = result.data;
            }
            if (!result.ok || state.model?.status !== 'processing' || pollAttempts > 90) {
                stopPolling();
                if (pollAttempts > 90) {
                    modelError = 'La generación está tardando demasiado. Vuelve a intentarlo más tarde.';
                }
                applyState(state);
            }
        }, 4000);
    }

    function stopPolling() {
        window.clearInterval(pollTimer);
        pollTimer = null;
        pollAttempts = 0;
    }

    // ---- Filtros ----
    const filterButtons = $$('[data-filter]');
    const productCards = $$('[data-product-card]');

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;
            filterButtons.forEach((other) => other.setAttribute('aria-pressed', String(other === button)));
            let visible = 0;
            productCards.forEach((card) => {
                const show = filter === 'all' || card.dataset.category === filter;
                card.classList.toggle('hidden', !show);
                visible += show ? 1 : 0;
            });
            $('[data-filter-empty]')?.classList.toggle('hidden', visible > 0);
        });
    });

    // ---- Selección ----
    function restoreSelection() {
        const empty = { top: null, bottom: null, extra: [] };
        try {
            const saved = JSON.parse(window.sessionStorage.getItem(storageKey) ?? 'null');
            if (!saved) {
                return empty;
            }
            const valid = (id) => (variantIndex.has(id) ? id : null);
            return {
                top: valid(saved.top),
                bottom: valid(saved.bottom),
                extra: (saved.extra ?? []).filter((id) => variantIndex.has(id)).slice(0, maxExtras),
            };
        } catch {
            return empty;
        }
    }

    function persistSelection() {
        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify(selection));
        } catch {
            // Sin almacenamiento disponible: la selección dura hasta recargar.
        }
    }

    const selectedIds = () => [selection.top, selection.bottom, ...selection.extra].filter(Boolean);

    function selectedVariantFor(productId) {
        return selectedIds().map((id) => variantIndex.get(id)).find((entry) => entry.product.id === productId) ?? null;
    }

    function removeProduct(productId) {
        const entry = selectedVariantFor(productId);
        if (!entry) {
            return;
        }
        const id = entry.variant.id;
        if (selection.top === id) {
            selection.top = null;
        } else if (selection.bottom === id) {
            selection.bottom = null;
        } else {
            selection.extra = selection.extra.filter((extraId) => extraId !== id);
        }
        persistSelection();
        renderSelection();
    }

    function selectVariant(product, variant) {
        removeProduct(product.id);
        if (product.slot === 'extra') {
            selection.extra = [...selection.extra, variant.id];
        } else {
            selection[product.slot] = variant.id;
        }
        persistSelection();
        renderSelection();
    }

    function renderSelection() {
        const ids = selectedIds();
        const list = $('[data-selection-list]');
        const preview = $('[data-selection-preview]');
        const previewList = $('[data-selection-preview-list]');
        const appliedIds = state.model?.status === 'completed' ? (state.model.variant_ids ?? []) : [];
        const localReady = localMode && fitting.status === 'ready';
        const applied = localReady || (ids.length > 0 && ids.length === appliedIds.length && ids.every((id) => appliedIds.includes(id)));

        list.replaceChildren();
        previewList.replaceChildren();

        ids.forEach((id) => {
            const { product, variant } = variantIndex.get(id);
            const item = el('li', 'flex items-center justify-between gap-3 rounded-lg border border-edge bg-ink/50 px-3 py-2');
            const text = el('div', 'min-w-0');
            text.append(
                el('p', 'text-[10px] font-bold uppercase tracking-[0.14em] text-electric-soft', slotLabels[product.slot]),
                el('p', 'truncate text-sm font-semibold text-white', product.name),
                el('p', 'text-xs text-mist', [variantLabel(variant), formatPrice(variant.price)].filter(Boolean).join(' · ')),
            );
            const remove = el('button', 'os-focus shrink-0 rounded-md px-2 py-1 text-xs font-bold uppercase text-haze hover:text-red-300', 'Quitar');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Quitar ${product.name}`);
            remove.addEventListener('click', () => removeProduct(product.id));
            item.append(text, remove);
            list.append(item);

            previewList.append(el('li', 'rounded-full border border-electric/50 bg-electric/10 px-3 py-1 text-xs font-semibold text-white', product.name));
        });

        productCards.forEach((card) => {
            const entry = selectedVariantFor(Number(card.dataset.productId));
            card.dataset.selected = String(Boolean(entry));
            const chooseButton = $('[data-product-choose]', card);
            const name = data.products.find((product) => product.id === Number(card.dataset.productId))?.name ?? '';
            chooseButton.setAttribute('aria-label', entry ? `Cambiar o quitar ${name}` : `Elegir ${name}`);
            if (entry) {
                const onPhoto = localReady ? entry.product.slot !== 'extra' : applied;
                $('[data-product-selected-text]', card).textContent = onPhoto ? (localReady ? 'En tu foto' : 'En tu modelo') : (variantLabel(entry.variant) || 'Seleccionada');
            }

            // La ilustración (si no hay foto) toma el color elegido, o vuelve al original.
            const fill = $('[data-garment-fill]', card);
            if (fill) {
                fill.dataset.defaultFill ??= fill.getAttribute('fill');
                fill.setAttribute('fill', entry?.variant.swatch ?? fill.dataset.defaultFill);
            }
        });

        $('[data-selection-empty]').classList.toggle('hidden', ids.length > 0);
        preview.classList.toggle('hidden', ids.length === 0);
        $('[data-selection-preview-title]').textContent = localReady
            ? 'Prendas en tu foto'
            : applied ? 'Prendas en tu modelo' : 'Prendas seleccionadas (aún no aplicadas al modelo)';

        $('[data-outfit-finish]').disabled = ids.length === 0;
        const tryButton = $('[data-outfit-try]');
        if (tryButton) {
            tryButton.disabled = ids.length === 0 || state.model?.status !== 'completed';
        }
        $('[data-selection-error]').classList.add('hidden');

        if (localReady) {
            drawFitting().then(() => {
                renderModel();
                viewer?.updatePhoto?.();
            });
        }

        if (turn.frames) {
            refreshTurnGarments();
        }
    }

    // ---- Vista 3D: la foto del cliente con las prendas, en relieve (photo-3d.js) ----
    const viewerWrap = $('[data-viewer-360]');
    const viewerStage = $('[data-viewer-360-stage]');
    const viewerStatus = $('[data-viewer-360-status]');
    const viewerToggle = $('[data-viewer-360-toggle]');
    const viewerAuto = $('[data-viewer-360-auto]');
    const depthCache = { photoUrl: null, canvas: null };
    let viewer = null;
    let viewerOpen = false;

    // Mientras la vista 3D está abierta se ocultan la foto y sus etiquetas para que no asomen detrás.
    function setStageHidden(hidden) {
        [fittingCanvas, modelImage, modelExample, modelExampleLabel].forEach((node) => node?.classList.toggle('invisible', hidden));
    }

    function syncAutoButton() {
        const on = Boolean(viewer?.autoRotate);
        viewerAuto.textContent = on ? 'Pausar giro' : 'Girar';
        viewerAuto.setAttribute('aria-pressed', String(on));
    }

    async function openViewer() {
        viewerOpen = true;
        setStageHidden(true);
        viewerWrap.classList.remove('hidden');
        viewerStatus.classList.remove('hidden');
        viewerToggle.setAttribute('aria-pressed', 'true');
        $('[data-viewer-360-label]').textContent = 'Ver en mi foto';

        // Con un giro grabado se muestra el cuerpo real girando (turntable.js).
        if (turn.frames) {
            try {
                const { createTurntable } = await import('./turntable.js');
                await refreshTurnGarments();

                if (!viewerOpen) {
                    return;
                }

                viewer?.dispose();
                viewer = createTurntable(viewerStage, turn.frames, renderTurnFrame, {
                    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                });
                viewerStatus.classList.add('hidden');
                $('[data-viewer-360-note]').textContent = 'Tu giro real · la prenda se ve en las vistas de frente';
                syncAutoButton();
            } catch {
                viewerStatus.textContent = 'No pudimos mostrar tu giro en este navegador.';
            }
            return;
        }

        if (fitting.status !== 'ready') {
            viewerStatus.textContent = state.photo_url
                ? 'Espera a que tu foto termine de procesarse para verla en 3D.'
                : 'Sube o toma tu foto para verla en 3D.';
            return;
        }

        const photoUrl = fitting.photoUrl;
        viewerStatus.textContent = depthCache.photoUrl === photoUrl
            ? 'Preparando la vista 3D…'
            : 'Calculando la profundidad de tu foto… la primera vez descarga unos 55 MB y puede tardar.';

        try {
            const [{ createPhoto3dViewer, estimateDepth }] = await Promise.all([import('./photo-3d.js'), drawFitting()]);

            if (depthCache.photoUrl !== photoUrl) {
                depthCache.canvas = await estimateDepth(fitting.analysis.base);
                depthCache.photoUrl = photoUrl;
            }

            if (!viewerOpen || fitting.photoUrl !== photoUrl) {
                return;
            }

            viewer?.dispose();
            viewer = createPhoto3dViewer(viewerStage, {
                photo: fittingCanvas,
                depthCanvas: depthCache.canvas,
                maskCanvas: fitting.module.reliablePersonMask(fitting.analysis),
                reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            });
            viewerStatus.classList.add('hidden');
            $('[data-viewer-360-note]').textContent = viewer.fullTurn
                ? '3D aproximado · la espalda es una silueta simulada'
                : '3D aproximado · gira hasta 30°';
            syncAutoButton();
        } catch {
            viewerStatus.textContent = 'No pudimos crear la vista 3D en este navegador. Prueba con Chrome, Edge o Safari actualizados.';
        }
    }

    function closeViewer() {
        viewerOpen = false;
        viewer?.dispose();
        viewer = null;
        setStageHidden(false);
        viewerWrap.classList.add('hidden');
        viewerToggle.setAttribute('aria-pressed', 'false');
        $('[data-viewer-360-label]').textContent = turn.frames ? 'Ver giro 360°' : 'Ver en 3D';
    }

    // ---- Giro 360° grabado (turntable.js): cuadros solo en memoria, nunca se suben ----
    const turn = { frames: null, garments: [] };
    const turnDialog = $('[data-turn-dialog]');
    const turnVideo = $('[data-turn-video]');
    const turnStatus = $('[data-turn-status]');
    const turnBar = $('[data-turn-bar]');
    const turnCountdown = $('[data-turn-countdown]');
    const turnStart = $('[data-turn-start]');
    let turnStream = null;
    let turnAbort = null;

    function clearTurn() {
        turn.frames?.forEach((frame) => URL.revokeObjectURL(frame.url));
        turn.frames = null;
        turn.garments = [];
        if (viewerOpen) {
            closeViewer();
        }
        $('[data-viewer-360-label]').textContent = 'Ver en 3D';
    }

    async function refreshTurnGarments() {
        if (!turn.frames || !fitting.module) {
            return;
        }
        const entries = [selection.top, selection.bottom].filter(Boolean).map((id) => variantIndex.get(id));
        turn.garments = (await Promise.all(entries.map(garmentFor))).filter(Boolean);
        viewer?.redraw?.();
    }

    function renderTurnFrame(frame, canvas) {
        if (frame.frontal && frame.analysis && turn.garments.length) {
            fitting.module.renderOutfit(canvas, frame.analysis, turn.garments);
            return;
        }
        canvas.width = frame.image.naturalWidth;
        canvas.height = frame.image.naturalHeight;
        canvas.getContext('2d').drawImage(frame.image, 0, 0);
    }

    function stopTurnCamera() {
        turnAbort?.abort();
        turnAbort = null;
        turnStream?.getTracks().forEach((track) => track.stop());
        turnStream = null;
        turnVideo.srcObject = null;
    }

    async function handleTurnFrames(blobs) {
        photoStatus.textContent = 'Analizando tu giro… 0%';

        try {
            fitting.module ??= await import('./virtual-fitting.js');
            const [{ analyzeFrames, mostFrontalFrame }, engine] = await Promise.all([
                import('./turntable.js'),
                fitting.module.loadFittingEngine(),
            ]);
            const frames = await analyzeFrames(blobs, fitting.module, engine, {
                onProgress: (p) => {
                    photoStatus.textContent = `Analizando tu giro… ${Math.round(p * 100)}%`;
                },
            });
            const front = mostFrontalFrame(frames);

            if (!front) {
                frames.forEach((frame) => URL.revokeObjectURL(frame.url));
                photoStatus.textContent = 'No te encontramos de frente en el video. Grábalo de cuerpo entero, con buena luz y empezando de frente.';
                return;
            }

            clearTurn();
            turn.frames = frames;
            // El cuadro más frontal pasa a ser la foto del probador (lo único que se sube al servidor).
            await uploadPhoto(new File([front.blob], 'giro-frente.jpg', { type: 'image/jpeg' }));
            photoStatus.textContent = 'Giro listo: mira tu giro 360° en el panel del modelo.';
            openViewer();
        } catch (error) {
            photoStatus.textContent = error?.message?.startsWith('El video') || error?.message?.startsWith('No pudimos')
                ? error.message
                : 'No pudimos procesar el giro en este navegador.';
        }
    }

    $('[data-turn-record]')?.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia || typeof turnDialog.showModal !== 'function') {
            photoStatus.textContent = 'Tu navegador no permite usar la cámara. Puedes subir un video con «Subir video».';
            return;
        }

        turnStatus.textContent = 'Solicitando acceso a la cámara…';
        turnBar.style.width = '0%';
        turnStart.disabled = true;
        turnDialog.showModal();

        try {
            turnStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 1280 } }, audio: false });
            turnVideo.srcObject = turnStream;
            await turnVideo.play();
            turnStatus.textContent = 'Cuando estés listo, pulsa «Empezar».';
            turnStart.disabled = false;
        } catch {
            turnDialog.close();
            photoStatus.textContent = 'No pudimos acceder a la cámara. Puedes subir un video con «Subir video».';
        }
    });

    turnStart?.addEventListener('click', async () => {
        turnStart.disabled = true;
        turnAbort = new AbortController();
        const { signal } = turnAbort;

        try {
            turnCountdown.classList.replace('hidden', 'grid');
            for (const n of [3, 2, 1]) {
                turnCountdown.textContent = String(n);
                turnStatus.textContent = 'Prepárate…';
                await new Promise((resolve) => setTimeout(resolve, 1000));
                if (signal.aborted) {
                    return;
                }
            }
            turnCountdown.classList.replace('grid', 'hidden');
            turnStatus.textContent = 'Gira despacio una vuelta completa…';

            const { captureFromVideoElement } = await import('./turntable.js');
            const blobs = await captureFromVideoElement(turnVideo, {
                signal,
                onProgress: (p) => {
                    turnBar.style.width = `${Math.round(p * 100)}%`;
                },
            });

            turnDialog.close();
            await handleTurnFrames(blobs);
        } catch {
            // Cancelado por el cliente.
        } finally {
            turnCountdown.classList.replace('grid', 'hidden');
        }
    });

    $('[data-turn-cancel]')?.addEventListener('click', () => turnDialog.close());
    turnDialog?.addEventListener('close', stopTurnCamera);
    window.addEventListener('pagehide', stopTurnCamera);

    $('[data-turn-input]')?.addEventListener('change', async (event) => {
        const [file] = event.target.files;
        event.target.value = '';

        if (!file) {
            return;
        }
        if (file.size > 100 * 1024 * 1024) {
            photoStatus.textContent = 'El video no puede superar los 100 MB.';
            return;
        }

        photoStatus.textContent = 'Leyendo el video… 0%';
        try {
            const { framesFromVideoFile } = await import('./turntable.js');
            const blobs = await framesFromVideoFile(file, {
                onProgress: (p) => {
                    photoStatus.textContent = `Leyendo el video… ${Math.round(p * 100)}%`;
                },
            });
            await handleTurnFrames(blobs);
        } catch (error) {
            photoStatus.textContent = error?.message || 'No pudimos leer el video.';
        }
    });

    viewerToggle?.addEventListener('click', () => (viewerOpen ? closeViewer() : openViewer()));
    $('[data-viewer-360-left]')?.addEventListener('click', () => {
        viewer?.rotateBy(-(viewer.fullTurn ? Math.PI / 6 : Math.PI / 18));
        syncAutoButton();
    });
    $('[data-viewer-360-right]')?.addEventListener('click', () => {
        viewer?.rotateBy(viewer.fullTurn ? Math.PI / 6 : Math.PI / 18);
        syncAutoButton();
    });
    viewerAuto?.addEventListener('click', () => {
        viewer?.setAutoRotate(!viewer.autoRotate);
        syncAutoButton();
    });
    window.addEventListener('pagehide', () => viewer?.dispose());

    $('[data-outfit-try]')?.addEventListener('click', () => {
        runModelAction(data.routes.tryOn, { variant_ids: selectedIds() });
    });

    // ---- Diálogo de talla y color ----
    const variantDialog = $('[data-variant-dialog]');
    const variantForm = $('[data-variant-form]');
    let dialogProduct = null;

    function radioOption(name, value, label, checked) {
        const option = el('label', 'os-option');
        const input = el('input', 'sr-only');
        input.type = 'radio';
        input.name = name;
        input.value = value;
        input.checked = checked;
        option.append(input, document.createTextNode(label));
        return option;
    }

    function renderSizes(product, color, preferredSize) {
        const sizes = product.variants.filter((variant) => (variant.color ?? '') === color);
        const container = $('[data-variant-sizes]');
        container.replaceChildren();
        const chosen = sizes.find((variant) => variant.size === preferredSize) ?? sizes[0];
        sizes.forEach((variant) => {
            container.append(radioOption('outfit-size', String(variant.id), variant.size || 'Única', variant.id === chosen?.id));
        });
        updateVariantPrice();
    }

    function currentDialogVariant() {
        const id = Number(variantForm.querySelector('input[name="outfit-size"]:checked')?.value);
        return dialogProduct?.variants.find((variant) => variant.id === id) ?? null;
    }

    function updateVariantPrice() {
        const variant = currentDialogVariant();
        $('[data-variant-price]').textContent = variant ? formatPrice(variant.price) : '';
    }

    function openVariantDialog(product) {
        dialogProduct = product;
        const current = selectedVariantFor(product.id)?.variant ?? null;
        const colors = [...new Set(product.variants.map((variant) => variant.color ?? ''))];
        const activeColor = current ? (current.color ?? '') : colors[0];
        const colorsContainer = $('[data-variant-colors]');
        const notice = $('[data-variant-replace]');
        const confirm = $('[data-variant-confirm]');

        $('[data-variant-title]').textContent = product.name;
        colorsContainer.replaceChildren(...colors.map((color) => radioOption('outfit-color', color, color || 'Único', color === activeColor)));
        renderSizes(product, activeColor, current?.size);

        notice.classList.add('hidden');
        confirm.disabled = false;
        if (!current) {
            const occupying = product.slot === 'extra' ? null : selection[product.slot];
            if (occupying) {
                notice.textContent = `Reemplazará a ${variantIndex.get(occupying).product.name} como prenda ${slotLabels[product.slot].toLowerCase()}.`;
                notice.classList.remove('hidden');
            } else if (product.slot === 'extra' && selection.extra.length >= maxExtras) {
                notice.textContent = `Ya elegiste ${maxExtras} complementos. Quita uno para agregar este.`;
                notice.classList.remove('hidden');
                confirm.disabled = true;
            }
        }

        confirm.textContent = current ? 'Actualizar' : 'Seleccionar';
        $('[data-variant-remove]').classList.toggle('hidden', !current);
        variantDialog.showModal();
    }

    variantForm.addEventListener('change', (event) => {
        if (event.target.name === 'outfit-color') {
            renderSizes(dialogProduct, event.target.value, currentDialogVariant()?.size);
        } else {
            updateVariantPrice();
        }
    });

    variantForm.addEventListener('submit', (event) => {
        const variant = currentDialogVariant();
        if (!variant) {
            event.preventDefault();
            return;
        }
        selectVariant(dialogProduct, variant);
    });

    $('[data-variant-cancel]').addEventListener('click', () => variantDialog.close());
    $('[data-variant-remove]').addEventListener('click', () => {
        removeProduct(dialogProduct.id);
        variantDialog.close();
    });

    productCards.forEach((card) => {
        $('[data-product-choose]', card).addEventListener('click', () => {
            const product = data.products.find((item) => item.id === Number(card.dataset.productId));
            if (product) {
                openVariantDialog(product);
            }
        });
    });

    // ---- Resumen y carrito ----
    const summaryDialog = $('[data-summary-dialog]');
    const summaryMessage = $('[data-summary-message]');
    const summaryConfirm = $('[data-summary-confirm]');

    function renderSummary(summary) {
        const lines = $('[data-summary-lines]');
        lines.replaceChildren();

        (summary.lines ?? []).forEach((line) => {
            const item = el('li', 'flex items-start justify-between gap-3 py-3');
            const info = el('div', 'min-w-0');
            info.append(el('p', 'text-sm font-semibold text-white', line.name));
            if (line.available) {
                info.append(el('p', 'text-xs text-mist', [line.size, line.color].filter(Boolean).join(' · ')));
            } else {
                info.append(el('p', 'text-xs font-semibold text-red-300', line.reason));
                const remove = el('button', 'os-focus mt-1 text-xs font-bold uppercase text-electric-soft hover:text-white', 'Quitar del outfit');
                remove.type = 'button';
                remove.addEventListener('click', () => {
                    const entry = variantIndex.get(line.variant_id);
                    if (entry) {
                        removeProduct(entry.product.id);
                    } else {
                        selection.top = selection.top === line.variant_id ? null : selection.top;
                        selection.bottom = selection.bottom === line.variant_id ? null : selection.bottom;
                        selection.extra = selection.extra.filter((id) => id !== line.variant_id);
                        persistSelection();
                        renderSelection();
                    }
                    loadSummary();
                });
                info.append(remove);
            }
            item.append(info, el('p', 'shrink-0 text-sm font-black', line.available ? formatPrice(line.price) : '—'));
            lines.append(item);
        });

        $('[data-summary-total]').textContent = formatPrice(summary.total ?? 0);
        summaryConfirm.disabled = !summary.all_available || !(summary.lines ?? []).length;
        summaryMessage.className = 'mt-3 min-h-5 text-sm font-semibold';
        if (!summary.all_available) {
            summaryMessage.classList.add('text-red-300');
            summaryMessage.textContent = 'Algunas prendas ya no están disponibles. Quítalas para continuar.';
        } else {
            summaryMessage.textContent = '';
        }
    }

    async function loadSummary() {
        const ids = selectedIds();
        if (!ids.length) {
            summaryDialog.close();
            return;
        }
        summaryConfirm.disabled = true;
        summaryMessage.className = 'mt-3 min-h-5 text-sm font-semibold text-mist';
        summaryMessage.textContent = 'Verificando precios y stock…';
        const result = await request(data.routes.summary, { json: { variant_ids: ids } });
        if (!result.ok) {
            summaryMessage.classList.add('text-red-300');
            summaryMessage.textContent = errorMessage(result, 'No pudimos verificar tu outfit.');
            return;
        }
        renderSummary(result.data);
    }

    $('[data-outfit-finish]').addEventListener('click', () => {
        $('[data-summary-lines]').replaceChildren();
        $('[data-summary-total]').textContent = formatPrice(0);
        summaryDialog.showModal();
        loadSummary();
    });

    $('[data-summary-close]').addEventListener('click', () => summaryDialog.close());

    summaryConfirm.addEventListener('click', async () => {
        summaryConfirm.disabled = true;
        summaryMessage.className = 'mt-3 min-h-5 text-sm font-semibold text-mist';
        summaryMessage.textContent = 'Agregando al carrito…';
        const result = await request(data.routes.cart, { json: { variant_ids: selectedIds() } });

        if (result.status === 409) {
            renderSummary(result.data);
            return;
        }
        if (!result.ok) {
            summaryMessage.classList.add('text-red-300');
            summaryMessage.textContent = errorMessage(result, 'No pudimos agregar el outfit al carrito.');
            summaryConfirm.disabled = false;
            return;
        }

        summaryMessage.classList.add('text-emerald-300');
        summaryMessage.textContent = result.data.message;
        selection = { top: null, bottom: null, extra: [] };
        persistSelection();
        window.location.assign(result.data.redirect);
    });

    applyState(state);
}
