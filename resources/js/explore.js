// Overshark · animaciones del selector de colección (explore.blade.php).
// Mismo comportamiento que el prototipo de Figma (overshark-web/index.html):
// entrada con SplitText, rayos, burbujas en canvas, tilt 3D de las tarjetas,
// botón magnético y cortina de transición al elegir una colección.
import { gsap } from 'gsap';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(SplitText);

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// Burbujas que suben, dibujadas en canvas (más liviano que cientos de nodos).
function bubbles(canvas, { count = 34, color = '190,225,255', rise = 0.45 } = {}) {
    if (!canvas) return () => {};
    const ctx = canvas.getContext('2d');
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    let w = 0;
    let h = 0;
    let raf = 0;

    const resize = () => {
        w = canvas.clientWidth;
        h = canvas.clientHeight;
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();

    const make = (y = Math.random() * h) => ({
        x: Math.random() * w,
        y,
        r: 1.5 + Math.random() * 7,
        s: (0.2 + Math.random() * 0.8) * rise,
        a: 0.15 + Math.random() * 0.45,
        p: Math.random() * Math.PI * 2,
    });
    const parts = Array.from({ length: count }, () => make());

    const draw = () => {
        ctx.clearRect(0, 0, w, h);
        for (const b of parts) {
            b.y -= b.s;
            b.p += 0.01;
            b.x += Math.sin(b.p) * 0.25;
            if (b.y < -20) Object.assign(b, make(h + 20));
            ctx.beginPath();
            ctx.arc(b.x, b.y, b.r, 0, Math.PI * 2);
            ctx.strokeStyle = `rgba(${color},${b.a})`;
            ctx.lineWidth = 1.2;
            ctx.stroke();
        }
        if (!reduce) raf = requestAnimationFrame(draw);
    };
    draw();

    window.addEventListener('resize', resize);
    return () => {
        cancelAnimationFrame(raf);
        window.removeEventListener('resize', resize);
    };
}

function rays(root) {
    $$('.ex-sea__rays i', root).forEach((ray, i) => {
        gsap.to(ray, { rotation: `+=${i % 2 ? 5 : -5}`, opacity: 0.55, duration: 4 + i, yoyo: true, repeat: -1, ease: 'sine.inOut' });
    });
}

function magnetic(root) {
    $$('.ex-pill', root).forEach((el) => {
        const xTo = gsap.quickTo(el, 'x', { duration: 0.5, ease: 'power3' });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.5, ease: 'power3' });
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            xTo((e.clientX - r.left - r.width / 2) * 0.3);
            yTo((e.clientY - r.top - r.height / 2) * 0.4);
        });
        el.addEventListener('pointerleave', () => { xTo(0); yTo(0); });
    });
}

function intro(view) {
    const split = SplitText.create('.js-split', { type: 'chars', mask: 'chars' });
    gsap.timeline({ defaults: { ease: 'expo.out' }, delay: 0.15 })
        .from('.ex-brand', { y: -30, opacity: 0, duration: 1 })
        .from('.ex-eyebrow', { opacity: 0, letterSpacing: '0.6em', duration: 1.2 }, '<.1')
        .from(split.chars, { yPercent: 110, duration: 1.1, stagger: 0.025 }, '<.1')
        .from('.ex-line-inner', { yPercent: 110, duration: 1.2 }, '-=.8')
        .from('.ex-sub', { opacity: 0, y: 16, duration: 0.8 }, '-=.7')
        .from('.ex-choice', { y: 120, rotationX: -18, opacity: 0, duration: 1.4, stagger: 0.15, transformOrigin: '50% 100%' }, '-=.8')
        .from('.ex-choice__models', { scale: 1.15, y: 40, duration: 1.6, stagger: 0.15 }, '<')
        .from('.ex-perks li', { opacity: 0, y: 12, stagger: 0.06, duration: 0.6 }, '-=1');

    // Tilt 3D + parallax interno de cada tarjeta, solo con mouse.
    if (!window.matchMedia('(hover: hover)').matches) return;
    $$('.ex-choice', view).forEach((card) => {
        const rx = gsap.quickTo(card, 'rotationX', { duration: 0.6, ease: 'power3' });
        const ry = gsap.quickTo(card, 'rotationY', { duration: 0.6, ease: 'power3' });
        const models = $('.ex-choice__models', card);
        const sea = $('.ex-choice__sea', card);
        const mx = gsap.quickTo(models, 'x', { duration: 0.8, ease: 'power3' });
        const sx = gsap.quickTo(sea, 'x', { duration: 1, ease: 'power3' });
        card.addEventListener('pointermove', (e) => {
            const r = card.getBoundingClientRect();
            const px = (e.clientX - r.left) / r.width - 0.5;
            const py = (e.clientY - r.top) / r.height - 0.5;
            ry(px * 10);
            rx(-py * 8);
            mx(px * -18);
            sx(px * 24);
            gsap.to(models, { scale: 1.04, duration: 0.6, overwrite: 'auto' });
        });
        card.addEventListener('pointerleave', () => {
            rx(0); ry(0); mx(0); sx(0);
            gsap.to(models, { scale: 1, duration: 0.6 });
        });
    });
}

// Cortina con el color de la colección y luego navega al enlace real.
function wipeTo(card) {
    const wipe = $('.ex-wipe');
    const href = card.getAttribute('href');
    if (reduce || !wipe) {
        window.location.href = href;
        return;
    }
    // La home de destino abre la cortina al cargar (resources/js/line-transition.js).
    try { sessionStorage.setItem('os-line-wipe', card.dataset.collection); } catch { /* sin storage: entra sin cortina */ }
    const color = card.dataset.color || '#5E9BEA';
    wipe.style.setProperty('--wipe-a', color);
    wipe.style.setProperty('--wipe-b', card.dataset.collection === 'mujer' ? '#3A1229' : '#03111E');
    gsap.timeline({ onComplete: () => { window.location.href = href; } })
        .to('.ex-wipe__layer--a', { clipPath: 'inset(0% 0 0 0)', duration: 0.55, ease: 'expo.inOut' })
        .to('.ex-wipe__layer--b', { clipPath: 'inset(0% 0 0 0)', duration: 0.6, ease: 'expo.inOut' }, '-=.35')
        .to('.ex-wipe__logo', { opacity: 1, letterSpacing: '0.2em', duration: 0.4 }, '-=.2');
}

function init() {
    const view = $('.ex-view');
    if (!view) return;

    bubbles($('.ex-bubbles', view));

    if (!reduce) {
        rays(view);
        magnetic(view);
        // SplitText mide las letras: con la fuente aún sin cargar, el corte queda mal.
        // Mientras tanto la vista se oculta para que no "salte" el contenido.
        gsap.set('.ex-selector', { autoAlpha: 0 });
        const empezar = () => { gsap.set('.ex-selector', { autoAlpha: 1 }); intro(view); };
        Promise.race([document.fonts.ready, new Promise((r) => setTimeout(r, 1500))]).then(empezar);
    }

    $$('.ex-choice', view).forEach((card) => {
        card.addEventListener('click', (e) => {
            // Ctrl/Cmd + clic o clic medio: que abra en otra pestaña sin cortina.
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            wipeTo(card);
        });
    });

    // Al volver con el botón "atrás" (bfcache) la cortina no debe quedar cerrada.
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) gsap.set('.ex-wipe__layer', { clipPath: 'inset(100% 0 0 0)' });
    });
}

init();
