// Detalle de combo (web/combo-show.blade.php): cada unidad elige color y talla (y producto, en combos
// de elección). Primero el color; las tallas sin stock en ese color quedan deshabilitadas. La foto, el
// texto de la unidad y el resumen "Tu combo" se actualizan al instante.
import { productTitle } from './product-name';
const form = document.querySelector('[data-combo-builder]');

if (form) {
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const title = productTitle;
    const sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    const bySize = (a, b) => (sizeOrder.indexOf(a.toUpperCase()) + 1 || 99) - (sizeOrder.indexOf(b.toUpperCase()) + 1 || 99);
    const summary = document.querySelector('[data-combo-summary]');
    const units = [];

    form.querySelectorAll('[data-combo-unit]').forEach((element) => {
        const variants = JSON.parse(element.dataset.variants || '[]');
        const input = element.querySelector('[data-combo-variant]');
        const product = element.querySelector('[data-combo-product]');
        const start = variants.find((v) => String(v.id) === input.value) ?? variants[0];
        const unit = { element, variants, input, product, color: start?.color, size: start?.talla };

        unit.pool = () => variants.filter((v) => !product || v.producto === product.value);
        unit.current = () => unit.pool().find((v) => v.color === unit.color && v.talla === unit.size);

        unit.render = () => {
            const pool = unit.pool();
            const colors = [...new Map(pool.map((v) => [v.color, v])).values()];
            if (!colors.some((v) => v.color === unit.color)) unit.color = colors[0]?.color;
            const sizes = [...new Set(pool.map((v) => v.talla))].sort(bySize);
            const sizesForColor = pool.filter((v) => v.color === unit.color).map((v) => v.talla);
            if (!sizesForColor.includes(unit.size)) unit.size = [...sizesForColor].sort(bySize)[0];

            element.querySelector('[data-combo-colors]').innerHTML = colors.map((v) => `
                <button type="button" role="radio" aria-checked="${v.color === unit.color}" class="os-bpdp-swatch os-focus !h-7 !w-7" style="--sw: ${esc(v.hex)}" data-color="${esc(v.color)}" aria-label="Color ${esc(title(v.color))}" title="${esc(title(v.color))}"></button>`).join('');
            element.querySelector('[data-combo-sizes]').innerHTML = sizes.map((size) => `
                <button type="button" role="radio" aria-checked="${size === unit.size}" class="os-bpdp-size os-focus !h-10 !min-w-11" data-size="${esc(size)}" ${sizesForColor.includes(size) ? '' : 'disabled title="Agotada en este color"'}>${esc(size)}</button>`).join('');

            const chosen = unit.current();
            input.value = chosen?.id ?? '';
            element.querySelector('[data-combo-choice]').textContent = chosen ? `${title(chosen.color)} · Talla ${chosen.talla}` : 'Elige color y talla';
            if (chosen?.image) element.querySelector('[data-combo-photo]').src = chosen.image;
            renderSummary();
        };

        element.addEventListener('click', (event) => {
            const color = event.target.closest('[data-color]');
            const size = event.target.closest('[data-size]');
            if (color) unit.color = color.dataset.color;
            else if (size && !size.disabled) unit.size = size.dataset.size;
            else return;
            unit.render();
        });
        product?.addEventListener('change', unit.render);
        units.push(unit);
    });

    function renderSummary() {
        if (!summary || units.length === 0) return;
        summary.innerHTML = units.map((unit) => {
            const chosen = unit.current();
            return `<li class="flex items-center gap-3">
                <span class="h-4 w-4 shrink-0 rounded-full shadow-[inset_0_0_0_1px_rgb(11_16_21/15%)]" style="background:${esc(chosen?.hex ?? '#e5e7eb')}" aria-hidden="true"></span>
                <span class="min-w-0 flex-1 truncate font-semibold">${esc(title(chosen?.producto ?? 'Prenda'))}</span>
                <span class="shrink-0 text-[#545966]">${chosen ? `${esc(title(chosen.color))} · ${esc(chosen.talla)}` : 'Por elegir'}</span>
            </li>`;
        }).join('');
    }

    // "Usar la misma elección en todas": copia la primera unidad de la prenda a las demás.
    form.querySelectorAll('[data-combo-slot]').forEach((slot) => {
        slot.querySelector('[data-combo-copy]')?.addEventListener('click', () => {
            const slotUnits = units.filter((unit) => slot.contains(unit.element));
            const [first, ...rest] = slotUnits;
            rest.forEach((unit) => {
                if (unit.product && first.product) unit.product.value = first.product.value;
                unit.color = first.color;
                unit.size = first.size;
                unit.render();
            });
        });
    });

    form.addEventListener('submit', (event) => {
        const missing = units.some((unit) => !unit.input.value);
        const error = form.querySelector('[data-combo-error]');
        if (missing) {
            event.preventDefault();
            error.textContent = 'Completa el color y la talla de cada prenda.';
            error.classList.remove('hidden');
            return;
        }
        document.querySelectorAll('[data-combo-submit]').forEach((button) => { button.disabled = true; });
    });

    units.forEach((unit) => unit.render());
}
