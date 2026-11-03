// Arma tu outfit (web/outfit.blade.php): el look es un combo de outfit; cada prenda del combo es una
// pestaña con sus opciones (producto + color y tallas). "Tu look" muestra lo elegido, el ahorro real
// frente al precio normal y envía las variantes al carrito del combo (selections[] en orden de prendas).
const root = document.querySelector('[data-outfit-builder]');

if (root) {
    const combos = JSON.parse(root.dataset.combos || '[]');
    const $ = (sel) => root.querySelector(sel);
    const money = (n) => `S/ ${Number(n).toFixed(2)}`;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const title = (s) => String(s ?? '').toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());
    const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;

    let combo = combos.find((c) => c.available) ?? null;
    let step = 0;
    let filter = '';
    let picks = [];

    const isDone = (i) => Boolean(picks[i]?.id);
    const doneCount = () => combo.steps.filter((_, i) => isDone(i)).length;

    const scrollToBuilder = () => {
        $('#constructor')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const renderTabs = () => {
        $('[data-ob-tabs]').innerHTML = combo.steps.map((s, i) => `
            <button type="button" role="tab" aria-selected="${i === step}" class="os-ob-tab os-focus${i === step ? ' is-active' : ''}${isDone(i) ? ' is-done' : ''}" data-ob-tab="${i}">
                ${isDone(i) && i !== step ? '✓ ' : `${i + 1} · `}${esc(s.label)}
            </button>`).join('');
        root.querySelectorAll('[data-ob-tab]').forEach((b) => b.addEventListener('click', () => go(Number(b.dataset.obTab))));
    };

    const renderChips = () => {
        const products = [...new Set(combo.steps[step].options.map((o) => o.product))];
        const chips = $('[data-ob-chips]');

        chips.classList.toggle('hidden', products.length < 2);
        chips.innerHTML = ['', ...products].map((p) => `
            <button type="button" class="os-ob-chip os-focus${filter === p ? ' is-active' : ''}" data-ob-filter="${esc(p)}" aria-pressed="${filter === p}">${p === '' ? 'Todos' : esc(p)}</button>`).join('');
        chips.querySelectorAll('[data-ob-filter]').forEach((b) => b.addEventListener('click', () => {
            filter = b.dataset.obFilter;
            renderChips();
            renderOptions();
        }));
    };

    const renderOptions = () => {
        const pick = picks[step];
        const options = combo.steps[step].options
            .map((o, oi) => ({ ...o, oi }))
            .filter((o) => filter === '' || o.product === filter);

        $('[data-ob-options]').innerHTML = options.map((o) => {
            const selected = pick?.option === o.oi;
            return `
            <li class="os-ob-option${selected ? ' is-selected' : ''}">
                <button type="button" class="os-focus block w-full text-left" data-ob-option="${o.oi}" aria-pressed="${selected}">
                    <span class="relative block aspect-[223/220] overflow-hidden bg-white">
                        <img src="${esc(o.image)}" alt="" class="h-full w-full object-cover" loading="lazy">
                        ${selected ? '<span class="absolute left-2 top-2 bg-[var(--hh-accent)] px-2.5 py-1.5 text-[11px] font-extrabold tracking-[1px] text-white">✓ EN TU LOOK</span>' : ''}
                    </span>
                    <span class="mt-2.5 block truncate text-[14px] font-bold">${esc(o.product)} · ${esc(title(o.color))}</span>
                </button>
                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-x-2 gap-y-1.5">
                    <span class="text-[15px] font-extrabold">${money(o.price)}</span>
                    <span class="flex flex-wrap gap-1" role="group" aria-label="Talla">
                        ${o.sizes.map((z) => `<button type="button" class="os-ob-size os-focus${selected && pick.id === z.id ? ' is-active' : ''}${selected && !pick.id ? ' is-prompt' : ''}" data-ob-size="${o.oi}|${z.id}|${esc(z.size)}" aria-label="Talla ${esc(z.size)}">${esc(z.size)}</button>`).join('')}
                    </span>
                </div>
            </li>`;
        }).join('');

        root.querySelectorAll('[data-ob-option]').forEach((b) => b.addEventListener('click', () => {
            const o = combo.steps[step].options[Number(b.dataset.obOption)];
            choose(Number(b.dataset.obOption), o.sizes.length === 1 ? o.sizes[0] : null);
        }));
        root.querySelectorAll('[data-ob-size]').forEach((b) => b.addEventListener('click', () => {
            const [oi, id, size] = b.dataset.obSize.split('|');
            choose(Number(oi), { id: Number(id), size });
        }));
    };

    const renderLook = () => {
        $('[data-ob-combo]').textContent = `${combo.name} · ${combo.detail}`;
        $('[data-ob-progress]').textContent = `Paso ${step + 1} de ${combo.steps.length}`;

        $('[data-ob-slots]').innerHTML = combo.steps.map((s, i) => {
            const p = picks[i];
            const o = p ? s.options[p.option] : null;

            if (!o) {
                return `
                <li class="flex items-center gap-3.5 border-[1.5px] border-dashed border-[var(--shop-check-line)] p-3 pr-4">
                    <span class="h-[96px] w-[76px] shrink-0 bg-[var(--ob-empty)] sm:h-[120px] sm:w-[96px]" aria-hidden="true"></span>
                    <span class="flex min-w-0 flex-1 flex-col gap-1">
                        <span class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">${esc(s.label)}</span>
                        <span class="text-[15px] font-bold text-[#888]">Elige tu ${esc(s.label.toLowerCase())}</span>
                    </span>
                    <button type="button" class="os-focus shrink-0 text-[13px] font-extrabold text-[var(--hh-accent)] hover:underline" data-ob-tab-go="${i}">+ Añadir</button>
                </li>`;
            }

            return `
            <li class="flex items-center gap-3.5 bg-white p-3 pr-4">
                <img src="${esc(o.image)}" alt="" class="h-[96px] w-[76px] shrink-0 object-cover sm:h-[120px] sm:w-[96px]">
                <span class="flex min-w-0 flex-1 flex-col gap-1">
                    <span class="text-[11px] font-extrabold uppercase tracking-[1.5px] text-[var(--hh-accent)]">${esc(s.label)}</span>
                    <span class="truncate text-[15px] font-bold">${esc(o.product)} · ${esc(title(o.color))}</span>
                    <span class="text-[13px] font-medium ${p.id ? 'text-[#545966]' : 'text-[#c0392b]'}">${money(o.price)} · ${p.id ? `Talla ${esc(p.size)}` : 'Elige tu talla'}</span>
                </span>
                <button type="button" class="os-focus shrink-0 text-[13px] font-extrabold text-[var(--hh-accent)] hover:underline" data-ob-tab-go="${i}">Cambiar</button>
            </li>`;
        }).join('');
        root.querySelectorAll('[data-ob-tab-go]').forEach((b) => b.addEventListener('click', () => go(Number(b.dataset.obTabGo))));

        const chosen = combo.steps.map((s, i) => (picks[i] ? s.options[picks[i].option] : null)).filter(Boolean);
        const subtotal = chosen.reduce((sum, o) => sum + Number(o.price), 0);
        const missing = combo.steps.length - doneCount();
        const saving = missing === 0 ? subtotal - combo.price : 0;

        $('[data-ob-subtotal-label]').textContent = `Subtotal (${plural(chosen.length, 'prenda', 'prendas')})`;
        $('[data-ob-subtotal]').textContent = money(subtotal);
        $('[data-ob-saving-row]').classList.toggle('hidden', saving <= 0);
        $('[data-ob-saving-row]').classList.toggle('flex', saving > 0);
        $('[data-ob-saving]').textContent = `– ${money(saving)}`;
        $('[data-ob-total]').textContent = money(combo.price);
        $('[data-ob-submit]').disabled = missing > 0;
        $('[data-ob-hint]').textContent = missing > 0
            ? `Elige ${plural(missing, 'prenda', 'prendas')} más para completar el look a ${money(combo.price)}.`
            : (saving > 0 ? `¡Listo! Ahorras ${money(saving)} con el combo.` : '¡Tu look está listo!');

        const form = $('[data-ob-form]');
        form.action = combo.cart_url;
        $('[data-ob-inputs]').innerHTML = missing > 0 ? '' : picks.map((p) => `<input type="hidden" name="selections[]" value="${p.id}">`).join('');
    };

    const render = () => {
        renderTabs();
        renderChips();
        renderOptions();
        renderLook();
    };

    const go = (i) => {
        step = i;
        filter = '';
        render();
    };

    const choose = (option, size) => {
        picks[step] = { option, id: size?.id ?? null, size: size?.size ?? null };

        // Con la talla elegida, pasa a la siguiente prenda pendiente.
        if (size) {
            const next = combo.steps.findIndex((_, i) => !isDone(i));
            if (next !== -1 && next !== step) {
                return go(next);
            }
        }

        render();

        // En celular "Tu look" va debajo de las prendas: al completarlo, se baja hasta el botón.
        if (doneCount() === combo.steps.length && window.matchMedia('(max-width: 1023px)').matches) {
            $('[data-ob-form]').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    const load = (id, scroll = true) => {
        combo = combos.find((c) => String(c.id) === String(id) && c.available) ?? combo;
        picks = [];
        go(0);
        if (scroll) scrollToBuilder();
    };

    root.querySelectorAll('[data-ob-pick]').forEach((b) => b.addEventListener('click', () => load(b.dataset.obPick)));
    root.querySelectorAll('[data-ob-reset]').forEach((b) => b.addEventListener('click', () => load(combo?.id, false)));

    if (combo) {
        render();
    }
}
