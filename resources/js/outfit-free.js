// Constructor libre de "Arma tu outfit" (web/outfit.blade.php): tres cuadros (Top, Bottom, Extra); al tocar uno
// se abre un modal con las prendas de la tienda (también de Bravos) para elegir modelo, color y talla.
// "Tu look" suma los precios individuales y envía las prendas a web.cart.look.
import { productTitle } from './product-name';
import { submitOnce } from './submit-once';

const root = document.querySelector('[data-free-look]');

if (root) {
    const catalog = JSON.parse(root.dataset.catalog || '[]');
    const categories = JSON.parse(root.dataset.categories || '{}');
    const slotLabels = JSON.parse(root.dataset.slots || '[]');
    const minItems = Number(root.dataset.min || 2);
    const $ = (sel) => root.querySelector(sel);
    const modal = $('[data-fl-modal]');
    const money = (n) => `S/ ${Number(n).toFixed(2)}`;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const title = productTitle;

    const slots = slotLabels.map(() => null);
    let active = 0;
    let draft = null;
    let brandFilter = '';
    let categoryFilter = '';
    let opener = null;

    const sizeOf = (pick) => pick.model.colors[pick.color].sizes.find((s) => s.size === pick.size);
    const priceOf = (pick) => sizeOf(pick)?.price ?? pick.model.price;
    const firstSize = (color) => color.sizes.find((s) => s.stock > 0)?.size ?? null;
    const newDraft = (model, colorIndex = 0) => ({ model, color: colorIndex, size: firstSize(model.colors[colorIndex]) });

    // ---- Cuadros y resumen -------------------------------------------------------------------
    const renderSlots = () => {
        $('[data-fl-slots]').innerHTML = slots.map((pick, i) => {
            const label = esc(slotLabels[i]);

            if (!pick) {
                return `
                <li>
                    <button type="button" class="os-focus group flex h-full w-full flex-col items-center justify-center gap-3 border-[1.5px] border-dashed border-[var(--shop-check-line)] bg-white p-4 text-center transition hover:border-[var(--hh-accent)]" data-fl-slot="${i}" aria-label="Elegir ${label}">
                        <span class="grid aspect-[3/4] w-full place-items-center bg-[var(--ob-empty)] text-[44px] font-light leading-none text-[var(--hh-accent)] transition group-hover:bg-[var(--hh-soft)]" aria-hidden="true">+</span>
                        <span class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">${label}</span>
                        <span class="text-[14px] font-bold text-[#545966]">Elegir ${label.toLowerCase()}</span>
                    </button>
                </li>`;
            }

            const color = pick.model.colors[pick.color];

            return `
            <li class="relative">
                <button type="button" class="os-focus flex h-full w-full flex-col gap-2 border-2 border-[var(--hh-accent)] bg-white p-3 text-left" data-fl-slot="${i}" aria-label="Cambiar ${label}">
                    <span class="block aspect-[3/4] w-full overflow-hidden bg-white"><img src="${esc(color.image)}" alt="" class="h-full w-full object-cover"></span>
                    <span class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">${label} · ${esc(pick.model.brand)}</span>
                    <span class="truncate text-[14px] font-bold">${esc(title(pick.model.name))} · ${esc(title(color.name))}</span>
                    <span class="text-[13px] font-medium text-[#545966]">Talla ${esc(pick.size)} · <strong class="text-[#0b0f14]">${money(priceOf(pick))}</strong></span>
                    <span class="text-[12px] font-extrabold text-[var(--hh-accent)]">Cambiar</span>
                </button>
                <button type="button" class="os-focus absolute right-1.5 top-1.5 grid h-8 w-8 place-items-center bg-white text-[18px] font-bold leading-none shadow" data-fl-clear="${i}" aria-label="Quitar ${label}">×</button>
            </li>`;
        }).join('');
    };

    const renderSummary = () => {
        const chosen = slots.map((pick, i) => ({ pick, i })).filter((entry) => entry.pick);
        const total = chosen.reduce((sum, entry) => sum + Number(priceOf(entry.pick)), 0);
        const missing = Math.max(0, minItems - chosen.length);

        $('[data-fl-count]').textContent = `Subtotal (${chosen.length} ${chosen.length === 1 ? 'prenda' : 'prendas'})`;
        $('[data-fl-total]').textContent = money(total);
        $('[data-fl-submit]').disabled = missing > 0;
        $('[data-fl-hint]').textContent = missing > 0
            ? `Elige ${missing} ${missing === 1 ? 'prenda' : 'prendas'} más para armar tu look.`
            : '¡Tu look está listo!';
        $('[data-fl-inputs]').innerHTML = missing > 0 ? '' : chosen.map((entry, n) => `
            <input type="hidden" name="items[${n}][producto_id]" value="${entry.pick.model.id}">
            <input type="hidden" name="items[${n}][color]" value="${esc(entry.pick.model.colors[entry.pick.color].name)}">
            <input type="hidden" name="items[${n}][talla]" value="${esc(entry.pick.size)}">`).join('');
    };

    const render = () => {
        renderSlots();
        renderSummary();
    };

    // ---- Modal -------------------------------------------------------------------------------
    const visibleModels = () => catalog
        .map((model, mi) => ({ model, mi }))
        .filter(({ model }) => (brandFilter === '' || model.brand === brandFilter) && (categoryFilter === '' || model.category === categoryFilter));

    const renderFilters = () => {
        const brands = [...new Set(catalog.map((m) => m.brand))];
        const present = new Set(catalog.map((m) => m.category));
        const chip = (attr, value, label, current) => `<button type="button" class="os-ob-chip os-focus${current === value ? ' is-active' : ''}" ${attr}="${esc(value)}" aria-pressed="${current === value}">${esc(label)}</button>`;

        $('[data-fl-brands]').innerHTML = brands.length > 1
            ? ['', ...brands].map((b) => chip('data-fl-brand', b, b === '' ? 'Todas las marcas' : b, brandFilter)).join('')
            : '';
        $('[data-fl-categories]').innerHTML = ['', ...Object.keys(categories).filter((slug) => present.has(slug))]
            .map((slug) => chip('data-fl-category', slug, slug === '' ? 'Todo' : categories[slug], categoryFilter)).join('');
    };

    const renderModels = () => {
        const list = visibleModels();

        $('[data-fl-empty]').classList.toggle('hidden', list.length > 0);
        $('[data-fl-models]').innerHTML = list.map(({ model, mi }) => {
            const selected = draft?.model === model;
            const color = selected ? model.colors[draft.color] : model.colors[0];

            return `
            <li class="os-ob-option${selected ? ' is-selected' : ''}">
                <button type="button" class="os-focus block w-full text-left" data-fl-model="${mi}" aria-pressed="${selected}">
                    <span class="relative block aspect-[223/220] overflow-hidden bg-white">
                        <img src="${esc(color.image)}" alt="" class="h-full w-full object-cover" loading="lazy">
                        <span class="absolute left-0 top-0 bg-[#0b0f14] px-2 py-1 text-[10px] font-extrabold uppercase tracking-[1px] text-white">${esc(model.brand)}</span>
                    </span>
                    <span class="mt-2.5 block truncate text-[14px] font-bold">${esc(title(model.name))}${selected ? ` · ${esc(title(color.name))}` : ''}</span>
                    <span class="mt-0.5 block text-[15px] font-extrabold">${money(selected ? (sizeOf(draft)?.price ?? model.price) : model.price)}</span>
                </button>
                ${selected ? `
                <div class="mt-3 flex flex-col gap-3">
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Color">
                        ${model.colors.map((c, ci) => `<button type="button" role="radio" aria-checked="${ci === draft.color}" class="os-bpdp-swatch os-focus !h-6 !w-6" style="--sw: ${esc(c.hex)}" data-fl-color="${ci}" aria-label="Color ${esc(title(c.name))}" title="${esc(title(c.name))}"></button>`).join('')}
                    </div>
                    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Talla">
                        ${color.sizes.map((s) => `<button type="button" role="radio" aria-checked="${s.size === draft.size}" class="os-bpdp-size os-focus !h-9 !min-w-10" data-fl-size="${esc(s.size)}" ${s.stock > 0 ? '' : 'disabled'}>${esc(s.size)}</button>`).join('')}
                    </div>
                </div>` : ''}
            </li>`;
        }).join('');

        $('[data-fl-confirm]').disabled = !draft?.size;
    };

    const open = (index) => {
        active = index;
        opener = document.activeElement;
        // Si ya hay una prenda en el cuadro, se abre con ella elegida para cambiar su color o talla.
        draft = slots[index] ? { ...slots[index] } : null;
        brandFilter = '';
        categoryFilter = '';
        $('[data-fl-modal-title]').textContent = `Elige tu ${slotLabels[index].toLowerCase()}`;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        renderFilters();
        renderModels();
        modal.querySelector('[data-fl-close]')?.focus();
    };

    const close = () => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        opener?.focus?.();
    };

    root.addEventListener('click', (event) => {
        const target = (selector) => event.target.closest(selector);
        const slot = target('[data-fl-slot]');
        const clear = target('[data-fl-clear]');

        if (clear) {
            slots[Number(clear.dataset.flClear)] = null;
            return render();
        }
        if (slot) return open(Number(slot.dataset.flSlot));
        if (target('[data-fl-close]') || event.target === modal.firstElementChild) return close();

        const brand = target('[data-fl-brand]');
        const category = target('[data-fl-category]');
        const model = target('[data-fl-model]');
        const color = target('[data-fl-color]');
        const size = target('[data-fl-size]');

        if (brand) {
            brandFilter = brand.dataset.flBrand;
            renderFilters();
        } else if (category) {
            categoryFilter = category.dataset.flCategory;
            renderFilters();
        } else if (model) {
            const picked = catalog[Number(model.dataset.flModel)];
            if (draft?.model !== picked) draft = newDraft(picked);
        } else if (color) {
            draft = newDraft(draft.model, Number(color.dataset.flColor));
        } else if (size && !size.disabled) {
            draft.size = size.dataset.flSize;
        } else if (target('[data-fl-confirm]') && draft?.size) {
            slots[active] = { ...draft };
            close();
            return render();
        } else {
            return;
        }

        renderModels();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    submitOnce($('[data-fl-form]'));
    render();
}
