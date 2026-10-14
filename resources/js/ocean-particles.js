// Puntitos de luz que flotan hacia arriba sobre el fondo marino (hero de la home hombre).
// Se dibujan en un <canvas data-ocean-particles>; se pausan fuera de pantalla y con movimiento reducido.
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function particles(canvas, { density = 0.00011, color = '190,225,255' } = {}) {
    const ctx = canvas.getContext('2d');
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    let w = 0;
    let h = 0;
    let parts = [];
    let raf = 0;
    let visible = true;

    const make = (y) => ({
        x: Math.random() * w,
        y: y ?? Math.random() * h,
        r: 0.8 + Math.random() * 2.2,
        s: 0.1 + Math.random() * 0.35,
        a: 0.45 + Math.random() * 0.55,
        p: Math.random() * Math.PI * 2,
    });

    const resize = () => {
        w = canvas.clientWidth;
        h = canvas.clientHeight;
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        parts = Array.from({ length: Math.round(w * h * density) + 20 }, () => make());
    };

    const draw = () => {
        ctx.clearRect(0, 0, w, h);
        for (const d of parts) {
            if (!reduce) {
                d.y -= d.s;
                d.p += 0.012;
                d.x += Math.sin(d.p) * 0.2;
                if (d.y < -10) Object.assign(d, make(h + 10));
            }
            const twinkle = 0.6 + Math.sin(d.p * 2) * 0.4;
            ctx.beginPath();
            ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${color},${d.a * twinkle})`;
            ctx.shadowColor = `rgba(${color},.8)`;
            ctx.shadowBlur = d.r * 3;
            ctx.fill();
        }
        if (!reduce && visible) raf = requestAnimationFrame(draw);
    };

    resize();
    draw();
    window.addEventListener('resize', () => {
        resize();
        if (reduce) draw();
    });

    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        cancelAnimationFrame(raf);
        if (visible && !reduce) raf = requestAnimationFrame(draw);
    }).observe(canvas);
}

document.querySelectorAll('[data-ocean-particles]').forEach((canvas) => particles(canvas));
