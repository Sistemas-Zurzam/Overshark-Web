// Cortina al cambiar de colección (hombre ⇄ mujer), igual que la del selector (explore.js).
// Al hacer clic en un enlace a /?linea=… de la otra colección, la cortina sube y cubre la
// pantalla; en la página nueva arranca cerrada (clase puesta en el <head> del layout) y se abre.
const KEY = 'os-line-wipe';
const LINES = {
    hombre: { a: '#2F6FD0', b: '#03111E' },
    mujer: { a: '#D63A78', b: '#3A1229' },
};
const EASE = 'cubic-bezier(.87,0,.13,1)';
const root = document.documentElement;
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function buildCurtain(line, closed) {
    const colors = LINES[line] ?? LINES.hombre;
    const clip = closed ? 'inset(0% 0 0 0)' : 'inset(100% 0 0 0)';
    const curtain = document.createElement('div');
    curtain.className = 'line-wipe';
    curtain.setAttribute('aria-hidden', 'true');
    curtain.innerHTML = `
        <div class="line-wipe__layer" style="background:${colors.a};clip-path:${clip}"></div>
        <div class="line-wipe__layer line-wipe__layer--b" style="background:${colors.b};clip-path:${clip}">
            <span class="line-wipe__logo" style="opacity:${closed ? 1 : 0}">${line === 'mujer' ? 'OVERSHARK GIRLS' : 'OVERSHARK'}</span>
        </div>`;
    document.body.append(curtain);
    return curtain;
}

function storage(action, value) {
    try {
        if (action === 'set') sessionStorage.setItem(KEY, value);
        if (action === 'remove') sessionStorage.removeItem(KEY);
    } catch {
        // Sin sessionStorage (modo privado estricto): simplemente no hay cortina de entrada.
    }
}

// Entrada: la cortina viene cerrada desde la página anterior y se abre hacia arriba.
function reveal() {
    const line = root.dataset.lineWipe;
    if (!root.classList.contains('line-wipe-in')) return;
    storage('remove');

    const curtain = buildCurtain(line, true);
    root.classList.remove('line-wipe-in');
    const [a, b] = curtain.querySelectorAll('.line-wipe__layer');
    const open = { clipPath: ['inset(0% 0 0 0)', 'inset(0 0 100% 0)'] };

    b.querySelector('.line-wipe__logo').animate({ opacity: [1, 0] }, { duration: 250, delay: 150, fill: 'forwards' });
    b.animate(open, { duration: 650, delay: 250, easing: EASE, fill: 'forwards' });
    a.animate(open, { duration: 650, delay: 420, easing: EASE, fill: 'forwards' }).finished.then(() => curtain.remove());
}

// Salida: la cortina sube con el color de la colección de destino y luego navega.
function wipeTo(href, line) {
    storage('set', line);
    const curtain = buildCurtain(line, false);
    const [a, b] = curtain.querySelectorAll('.line-wipe__layer');
    const close = { clipPath: ['inset(100% 0 0 0)', 'inset(0% 0 0 0)'] };

    a.animate(close, { duration: 550, easing: EASE, fill: 'forwards' });
    b.animate(close, { duration: 600, delay: 200, easing: EASE, fill: 'forwards' }).finished.then(() => {
        b.querySelector('.line-wipe__logo').animate({ opacity: [0, 1], letterSpacing: ['.3em', '.2em'] }, { duration: 300, fill: 'forwards' });
        setTimeout(() => window.location.assign(href), 220);
    });
}

function targetLine(link) {
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== '/') return null;
    const line = url.searchParams.get('linea');
    return line in LINES && line !== root.dataset.line ? line : null;
}

document.addEventListener('click', (event) => {
    if (reduce || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest('a[href]');
    if (!link || link.target === '_blank') return;
    const line = targetLine(link);
    if (!line) return;
    event.preventDefault();
    wipeTo(link.href, line);
});

// Al volver con "atrás" (bfcache) la cortina de salida no debe quedar cerrada.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) document.querySelectorAll('.line-wipe').forEach((curtain) => curtain.remove());
});

reveal();
