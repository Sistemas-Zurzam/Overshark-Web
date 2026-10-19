// Arma tu outfit (web/outfit.blade.php): combo → una prenda por paso (tarjetas producto + color
// con sus tallas; elegir talla avanza) → "Tu outfit está listo" y envío al carrito del combo.
const root = document.querySelector('[data-outfit-builder]');

if (root) {
    const combos = JSON.parse(root.dataset.combos || '[]');
    const $ = (sel) => root.querySelector(sel);
    const $$ = (sel) => [...root.querySelectorAll(sel)];
    const money = (n) => `S/${Number(n).toFixed(2)}`;
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    let combo = null;
    let step = 0;
    let picks = [];

    const show = (view) => {
        $$('[data-ob-view]').forEach((el) => el.classList.toggle('hidden', el.dataset.obView !== view));
        const header = document.querySelector('header')?.offsetHeight ?? 0;
        window.scrollTo({ top: (view === 'combos' ? root : root.querySelector(`[data-ob-view="${view}"]`)).getBoundingClientRect().top + window.scrollY - header - 12, behavior: 'smooth' });
    };

    const renderChips = () => {
        const chips = [`<button type="button" class="os-ob-chip-step is-done" data-ob-go="combo">✓ Combo</button>`]
            .concat(combo.steps.map((s, i) => {
                const done = Boolean(picks[i]);
                const current = i === step && !isReady();
                return `<button type="button" class="os-ob-chip-step${done ? ' is-done' : ''}${current ? ' is-current' : ''}" data-ob-go="${i}"${!done && !current ? ' disabled' : ''}>${done ? '✓ ' : ''}${esc(s.label)}</button>`;
            }));
        $$('[data-ob-chips]').forEach((nav) => {
            nav.innerHTML = chips.join('');
            nav.querySelectorAll('[data-ob-go]').forEach((b) => b.addEventListener('click', () => {
                if (b.dataset.obGo === 'combo') return reset();
                step = Number(b.dataset.obGo);
                renderStep();
            }));
        });
    };

    const isReady = () => combo && picks.length === combo.steps.length && picks.every(Boolean);

    const renderStep = () => {
        const s = combo.steps[step];
        $('[data-ob-step-title]').textContent = `Elige tu ${s.label.toLowerCase()}`;
        $('[data-ob-step-sub]').textContent = `${combo.detail} · ${money(combo.price)}`;
        $('[data-ob-building]').textContent = `Armando ${combo.detail}`;
        $('[data-ob-badge]').textContent = `Combo: ${money(combo.price)}`;
        $('[data-ob-picked]').innerHTML = picks.filter(Boolean).map((p) => `<img src="${esc(p.image)}" alt="${esc(p.product)}" class="h-14 w-11 rounded-md bg-white object-cover">`).join('');

        const track = $('[data-ob-track]');
        track.innerHTML = s.options.map((o, oi) => `
            <article class="os-ob-card${picks[step]?.option === oi ? ' is-picked' : ''}">
                <div class="os-ob-card__img"><img src="${esc(o.image)}" alt="" loading="lazy"></div>
                <div class="p-3">
                    <p class="text-sm font-bold leading-tight">${esc(o.product)} · ${esc(o.color)}</p>
                    <p class="mt-1 text-lg font-black">${money(o.price)}</p>
                    <p class="mt-2 text-[10px] font-black uppercase tracking-[0.15em] text-haze">Elige tu talla</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        ${o.sizes.map((z) => `<button type="button" class="os-ob-size${picks[step]?.id === z.id ? ' is-picked' : ''}" data-ob-size="${oi}|${z.id}|${esc(z.size)}">${esc(z.size)}</button>`).join('')}
                    </div>
                </div>
            </article>`).join('');
        track.scrollLeft = 0;
        track.querySelectorAll('[data-ob-size]').forEach((b) => b.addEventListener('click', () => {
            const [oi, id, size] = b.dataset.obSize.split('|');
            const o = s.options[Number(oi)];
            picks[step] = { option: Number(oi), id: Number(id), size, product: o.product, color: o.color, image: o.image, price: o.price };
            const next = combo.steps.findIndex((_, i) => !picks[i]);
            if (next === -1) return renderReady();
            step = next;
            renderStep();
        }));

        renderChips();
        show('steps');
    };

    const renderReady = () => {
        const form = $('[data-ob-form]');
        form.action = combo.cart_url;
        $('[data-ob-inputs]').innerHTML = picks.map((p) => `<input type="hidden" name="selections[]" value="${p.id}">`).join('');
        $('[data-ob-summary]').innerHTML = picks.map((p, i) => `
            <div class="os-ob-card w-44">
                <div class="os-ob-card__img"><img src="${esc(p.image)}" alt=""></div>
                <div class="p-3 text-center">
                    <p class="text-sm font-bold leading-tight">${esc(p.product)} · ${esc(p.color)}</p>
                    <p class="mt-1 text-xs text-haze">Talla ${esc(p.size)} · ${esc(combo.steps[i].label)}</p>
                </div>
            </div>`).join('');
        const normal = picks.reduce((sum, p) => sum + p.price, 0);
        $('[data-ob-normal]').textContent = normal > combo.price ? `Precio normal: ${money(normal)}` : '';
        $('[data-ob-total]').textContent = money(combo.price);
        renderChips();
        show('ready');
    };

    const reset = () => {
        combo = null;
        picks = [];
        step = 0;
        show('combos');
    };

    $$('[data-ob-pick]').forEach((b) => b.addEventListener('click', () => {
        combo = combos.find((c) => String(c.id) === b.dataset.obPick);
        picks = [];
        step = 0;
        renderStep();
    }));
    $$('[data-ob-reset]').forEach((b) => b.addEventListener('click', reset));
    $$('[data-ob-scroll]').forEach((b) => b.addEventListener('click', () => {
        const track = $('[data-ob-track]');
        track.scrollBy({ left: Number(b.dataset.obScroll) * track.clientWidth * 0.8, behavior: 'smooth' });
    }));
}
