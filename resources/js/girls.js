// Overshark Girls · animaciones de la home mujer (home-mujer.blade.php).
// Igual que el prototipo de Figma (overshark-web/index.html#mujer): header que se
// fija al hacer scroll, burbujas y rayos en el hero, entrada con SplitText,
// revelado de secciones con ScrollTrigger, parallax en looks y swatches de color.
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';

gsap.registerPlugin(ScrollTrigger, SplitText);

const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

// Burbujas rosadas que suben, dibujadas en canvas.
function bubbles(canvas, { count = 26, color = '214,58,120', rise = 0.4 } = {}) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    let w = 0;
    let h = 0;

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
        r: 1.5 + Math.random() * 6,
        s: (0.2 + Math.random() * 0.8) * rise,
        a: 0.12 + Math.random() * 0.3,
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
        if (!reduce) requestAnimationFrame(draw);
    };
    draw();
    window.addEventListener('resize', resize);
}

function stickyHeader() {
    const header = $('.gl-hdr');
    if (!header) return;
    const update = () => header.classList.toggle('is-stuck', window.scrollY > 20);
    update();
    window.addEventListener('scroll', update, { passive: true });
}

// Al elegir un color se cambia la foto de la tarjeta (si la variante tiene imagen).
function swatches() {
    $$('.gl-swatches').forEach((group) => {
        const image = group.closest('.gl-prod')?.querySelector('[data-gl-prod-image]');
        $$('[data-gl-swatch]', group).forEach((button) => {
            button.addEventListener('click', () => {
                $$('[data-gl-swatch]', group).forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
                if (image && button.dataset.image) image.src = button.dataset.image;
            });
        });
    });
}

function intro() {
    const split = $('[data-gl-split]');
    const tl = gsap.timeline({ defaults: { ease: 'power4.out' } });
    if (split) {
        const chars = SplitText.create(split, { type: 'chars' }).chars;
        tl.from(chars, { yPercent: 110, opacity: 0, duration: 0.9, stagger: 0.04 });
    }
    tl.from('.gl-hero .gl-line-inner', { yPercent: 110, duration: 1 }, '-=0.6')
        .from(['.gl-crumbs', '.gl-eyebrow', '.gl-hero__sub', '.gl-hero__ctas', '.gl-count'], { y: 20, opacity: 0, duration: 0.8, stagger: 0.08 }, '-=0.7')
        .from('.gl-hero__models', { y: 60, opacity: 0, duration: 1.2, clearProps: 'transform,opacity' }, '<')
        .from('.gl-acc', { y: 40, opacity: 0, duration: 0.8, stagger: 0.08, clearProps: 'transform,opacity' }, '-=0.8');

    $$('.gl-rays i').forEach((ray, i) => {
        gsap.to(ray, { rotation: `+=${i % 2 ? 5 : -5}`, opacity: 0.55, duration: 4 + i, yoyo: true, repeat: -1, ease: 'sine.inOut' });
    });
}

function reveals() {
    $$('[data-gl-split-sec]').forEach((title) => {
        const words = SplitText.create(title, { type: 'words' }).words;
        gsap.from(words, { yPercent: 60, opacity: 0, duration: 0.8, stagger: 0.06, ease: 'power3.out', scrollTrigger: { trigger: title, start: 'top 85%' } });
    });

    ScrollTrigger.batch('[data-gl-reveal]', {
        start: 'top 88%',
        once: true,
        onEnter: (els) => gsap.from(els, { y: 50, opacity: 0, duration: 0.9, stagger: 0.1, ease: 'power3.out' }),
    });

    ScrollTrigger.batch('[data-gl-reveal-clip]', {
        start: 'top 88%',
        once: true,
        onEnter: (els) => gsap.from(els, { clipPath: 'inset(100% 0 0 0)', duration: 1.1, stagger: 0.12, ease: 'power4.out' }),
    });

    $$('[data-gl-parallax]').forEach((el) => {
        gsap.fromTo(el, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: el.parentElement, scrub: true } });
    });
}

function magnetic() {
    $$('[data-gl-magnetic]').forEach((el) => {
        const xTo = gsap.quickTo(el, 'x', { duration: 0.5, ease: 'power3' });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.5, ease: 'power3' });
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            xTo((e.clientX - r.left - r.width / 2) * 0.3);
            yTo((e.clientY - r.top - r.height / 2) * 0.4);
        });
        el.addEventListener('pointerleave', () => {
            xTo(0);
            yTo(0);
        });
    });
}

stickyHeader();
swatches();
bubbles($('[data-gl-bubbles]'));

if (!reduce) {
    document.fonts.ready.then(() => {
        if ($('.gl-hero')) intro(); // el catálogo no tiene hero
        reveals();
        magnetic();
    });
}
