// Ficha de producto (web/product-show.blade.php): galería, color, talla y cantidad.
// Al elegir un color se muestran sus fotos y solo se habilitan las tallas con stock en ese color.
const page = document.querySelector('[data-pdp]');

if (page) {
    const stock = JSON.parse(page.dataset.pdpStock || '{}');
    const $ = (sel) => page.querySelector(sel);
    const $$ = (sel) => [...page.querySelectorAll(sel)];
    const mainImage = page.querySelector('[data-product-card-image]');
    const submitButtons = $$('[data-pdp-submit]');
    let color = $('[data-pdp-input-color]').value;
    let size = $('[data-pdp-input-size]').value;
    let qty = 1;

    const showImage = (src) => {
        if (!src || !mainImage) return;
        mainImage.src = src;
        mainImage.style.transform = '';
        $$('[data-pdp-thumb]').forEach((t) => t.classList.toggle('is-active', t.dataset.image === src));
    };

    const bindThumbs = () => $$('[data-pdp-thumb]').forEach((thumb) => {
        thumb.addEventListener('click', () => showImage(thumb.dataset.image));
    });

    const renderThumbs = (images) => {
        $('[data-pdp-thumbs]').innerHTML = images.slice(0, 5).map((src, i) => `
            <button type="button" data-pdp-thumb data-image="${src}" class="os-pdp-dot${i === 0 ? ' is-active' : ''}" aria-label="Ver foto ${i + 1}"></button>`).join('');
        bindThumbs();
        toggleArrows();
    };

    // Flechas: recorren las fotos del color elegido y, al pasar la última (o la primera),
    // siguen con el color siguiente (o anterior). Los puntos solo se ven si el color tiene varias fotos.
    const colorButtons = $$('[data-pdp-color]');
    const toggleArrows = () => {
        $('[data-pdp-thumbs]').style.display = $$('[data-pdp-thumb]').length > 1 ? '' : 'none';
        const show = $$('[data-pdp-thumb]').length > 1 || colorButtons.length > 1;
        $$('[data-pdp-step]').forEach((b) => { b.style.display = show ? '' : 'none'; });
    };

    $$('[data-pdp-step]').forEach((button) => button.addEventListener('click', () => {
        const step = Number(button.dataset.pdpStep);
        const dots = $$('[data-pdp-thumb]');
        const current = dots.findIndex((d) => d.classList.contains('is-active'));
        const target = current + step;

        if (target >= 0 && target < dots.length) {
            showImage(dots[target].dataset.image);
            return;
        }

        const index = colorButtons.findIndex((b) => b.dataset.pdpColor === color);
        const nextColor = colorButtons[(index + step + colorButtons.length) % colorButtons.length];
        nextColor?.click();
        // Hacia atrás se entra por la última foto del color anterior.
        if (step < 0) {
            const last = $$('[data-pdp-thumb]').at(-1);
            if (last) showImage(last.dataset.image);
        }
    }));

    const available = (c, s) => (stock[c]?.[s] ?? 0) > 0;

    const render = () => {
        $$('[data-pdp-color]').forEach((b) => b.setAttribute('aria-checked', String(b.dataset.pdpColor === color)));
        $('[data-pdp-color-name]').textContent = color;

        // Si la talla elegida no existe en este color, se pasa a la primera disponible.
        if (!available(color, size)) {
            size = $$('[data-pdp-size]').map((b) => b.dataset.pdpSize).find((s) => available(color, s)) ?? size;
        }

        $$('[data-pdp-size]').forEach((b) => {
            const ok = available(color, b.dataset.pdpSize);
            b.disabled = !ok;
            b.setAttribute('aria-checked', String(ok && b.dataset.pdpSize === size));
            b.title = ok ? '' : 'Agotado en este color';
        });
        $('[data-pdp-size-name]').textContent = size;

        const units = stock[color]?.[size] ?? 0;
        qty = Math.max(1, Math.min(qty, units || 1));
        $('[data-pdp-qty-value]').textContent = qty;

        const note = $('[data-pdp-stock-note]');
        note.classList.toggle('hidden', !(units > 0 && units <= 5));
        note.textContent = units === 1 ? '¡Última unidad en esta talla!' : `¡Quedan solo ${units} unidades en esta talla!`;

        $('[data-pdp-input-color]').value = color;
        $('[data-pdp-input-size]').value = size;
        $('[data-pdp-input-qty]').value = qty;
        submitButtons.forEach((b) => { b.disabled = units < 1; });
    };

    $$('[data-pdp-color]').forEach((button) => button.addEventListener('click', () => {
        color = button.dataset.pdpColor;
        const images = JSON.parse(button.dataset.images || '[]');
        if (images.length) {
            renderThumbs(images);
            showImage(images[0]);
        }
        render();
    }));

    $$('[data-pdp-size]').forEach((button) => button.addEventListener('click', () => {
        if (button.disabled) return;
        size = button.dataset.pdpSize;
        render();
    }));

    $$('[data-pdp-qty]').forEach((button) => button.addEventListener('click', () => {
        const max = stock[color]?.[size] ?? 1;
        qty = Math.max(1, Math.min(max, qty + Number(button.dataset.pdpQty)));
        render();
    }));

    bindThumbs();
    toggleArrows();
    render();
}
