// Arma tu outfit de Bravos (web/bravos-outfit.blade.php): una prenda por paso (top y pantalón), cada una
// con su color y talla. "Tu outfit" suma los precios individuales y envía las prendas a web.cart.look.
import { productTitle } from './product-name';
import { submitOnce } from './submit-once';
const root = document.querySelector('[data-bravos-outfit]');

if (root) {
    const steps = JSON.parse(root.dataset.steps || '{}');
    const keys = ['tops', 'bottoms'];
    const labels = { tops: 'Top', bottoms: 'Pantalón' };
    const categories = { poleras: 'POLERAS', polos: 'POLOS', camisas: 'CAMISAS', pantalones: 'PANTALONES' };
    const $ = (sel) => root.querySelector(sel);
    const money = (n) => `S/ ${Number(n).toFixed(2)}`;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const title = productTitle;

    const firstSize = (color) => color.sizes.find((s) => s.stock > 0)?.size ?? null;
    const pick = (model, colorIndex = 0) => ({ model, color: colorIndex, size: firstSize(model.colors[colorIndex]) });

    // Cada paso arranca con el modelo con más stock, en su primer color y primera talla disponible.
    const picks = Object.fromEntries(keys.map((key) => [key, pick(steps[key][0])]));

    const renderOptions = (key) => {
        const current = picks[key];
        $(`[data-bo-options="${key}"]`).innerHTML = steps[key].map((model, mi) => {
            const selected = current.model === model;
            const color = selected ? model.colors[current.color] : model.colors[0];
            return `
            <li class="os-bo-card${selected ? ' is-selected' : ''}">
                <button type="button" class="os-focus block w-full text-left" data-bo-pick="${key}|${mi}" aria-pressed="${selected}">
                    <span class="block aspect-[380/314] overflow-hidden bg-white">
                        <img src="${esc(color.image)}" alt="" class="h-full w-full object-cover" loading="lazy">
                    </span>
                    <span class="mt-2.5 block text-[11px] font-bold tracking-[1px] text-[#666]">${categories[model.category] ?? ''}</span>
                    <span class="mt-1 block truncate text-[15px] font-bold text-[#0b0f14]">${esc(title(model.name))} · ${esc(title(color.name))}</span>
                    <span class="mt-1 block text-[16px] font-extrabold text-[#0b0f14]">${money(model.price)}</span>
                </button>
                ${selected ? `
                <div class="mt-3 flex flex-col gap-3">
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Color de ${esc(title(model.name))}">
                        ${model.colors.map((c, ci) => `<button type="button" role="radio" aria-checked="${ci === current.color}" class="os-bpdp-swatch os-focus !h-6 !w-6" style="--sw: ${esc(c.hex)}" data-bo-color="${key}|${ci}" aria-label="Color ${esc(title(c.name))}" title="${esc(title(c.name))}"></button>`).join('')}
                    </div>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Talla de ${esc(title(model.name))}">
                        ${color.sizes.map((s) => `<button type="button" role="radio" aria-checked="${s.size === current.size}" class="os-bpdp-size os-focus !h-10 !min-w-11" data-bo-size="${key}|${esc(s.size)}" ${s.stock > 0 ? '' : 'disabled'}>${esc(s.size)}</button>`).join('')}
                    </div>
                </div>` : ''}
                <p class="mt-3 text-[12px] font-extrabold leading-[1.35] text-[#0a0a0a]">${selected ? '✓ EN TU LOOK' : 'ELEGIR ESTA PRENDA'}</p>
            </li>`;
        }).join('');
    };

    const renderSummary = () => {
        const chosen = keys.map((key) => ({ key, ...picks[key], colorData: picks[key].model.colors[picks[key].color] }));
        $('[data-bo-summary]').innerHTML = chosen.map((p) => `
            <li class="flex gap-4">
                <img src="${esc(p.colorData.image)}" alt="" class="h-[112px] w-[88px] shrink-0 bg-white object-cover">
                <div class="flex min-w-0 flex-col gap-2 leading-[1.35]">
                    <p class="text-[11px] font-extrabold tracking-[1px] text-[#666]">${labels[p.key].toUpperCase()}</p>
                    <p class="text-[16px] font-bold text-[#0a0a0a]">${esc(title(p.model.name))} · ${esc(title(p.colorData.name))}</p>
                    <p class="text-[13px] ${p.size ? 'text-[#666]' : 'text-[#c0392b]'}">${p.size ? `Talla ${esc(p.size)}` : 'Sin tallas en este color'} · Cantidad 1</p>
                    <p class="text-[18px] font-extrabold text-[#0a0a0a]">${money(p.model.price)}</p>
                </div>
            </li>`).join('');

        const total = chosen.reduce((sum, p) => sum + Number(p.model.price), 0);
        $('[data-bo-count]').textContent = `Subtotal · ${chosen.length} prendas`;
        $('[data-bo-total]').textContent = money(total);
        $('[data-bo-submit]').disabled = chosen.some((p) => !p.size);
        $('[data-bo-inputs]').innerHTML = chosen.map((p, i) => `
            <input type="hidden" name="items[${i}][producto_id]" value="${p.model.id}">
            <input type="hidden" name="items[${i}][color]" value="${esc(p.colorData.name)}">
            <input type="hidden" name="items[${i}][talla]" value="${esc(p.size ?? '')}">`).join('');
    };

    const render = () => {
        keys.forEach(renderOptions);
        renderSummary();
    };

    root.addEventListener('click', (event) => {
        const pickButton = event.target.closest('[data-bo-pick]');
        const colorButton = event.target.closest('[data-bo-color]');
        const sizeButton = event.target.closest('[data-bo-size]');

        if (pickButton) {
            const [key, mi] = pickButton.dataset.boPick.split('|');
            const model = steps[key][Number(mi)];
            if (picks[key].model !== model) picks[key] = pick(model);
        } else if (colorButton) {
            const [key, ci] = colorButton.dataset.boColor.split('|');
            picks[key] = pick(picks[key].model, Number(ci));
        } else if (sizeButton && !sizeButton.disabled) {
            const [key, size] = sizeButton.dataset.boSize.split('|');
            picks[key].size = size;
        } else {
            return;
        }
        render();
    });

    submitOnce($('[data-bo-form]'));
    render();
}
