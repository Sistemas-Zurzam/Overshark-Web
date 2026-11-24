// Portada de Overshark Hombre (web/partials/landing-hombre.blade.php), del prototipo "overshark-landing":
// intro del logo (una vez por sesión; ?intro la fuerza), entrada del hero, burbujas, parallax, cinta,
// revelado al hacer scroll, carrusel de combos y texturas que cambian. Respeta "reducir movimiento".
const root = document.querySelector('[data-landing-hombre]');

if (root) {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $ = (selector, scope = root) => scope.querySelector(selector);
    const $$ = (selector, scope = root) => [...scope.querySelectorAll(selector)];
    const store = {
        get: (key) => { try { return sessionStorage.getItem(key); } catch { return null; } },
        set: (key, value) => { try { sessionStorage.setItem(key, value); } catch { /* sin almacenamiento: la intro se repite */ } },
    };
    const ease = 'cubic-bezier(.16,1,.3,1)';

    const heroIn = () => {
        if (reduce) {
            $$('.lh-hero [data-in], .lh-tag-in, .lh-title .ln > span').forEach((el) => { el.style.opacity = 1; el.style.transform = 'none'; });
            return;
        }
        $$('.lh-title .ln > span').forEach((el, i) => el.animate([{ transform: 'translateY(110%)' }, { transform: 'translateY(0)' }], { duration: 1100, delay: 120 + i * 130, easing: ease, fill: 'forwards' }));
        $$('.lh-hero [data-in]').forEach((el, i) => el.animate([{ opacity: 0, transform: 'translateY(18px)' }, { opacity: 1, transform: 'none' }], { duration: 900, delay: 520 + i * 120, easing: ease, fill: 'forwards' }));
        $$('.lh-model').forEach((el, i) => el.animate([{ opacity: 0, transform: 'translateY(18%) scale(.96)', filter: 'blur(10px)' }, { opacity: 1, transform: 'none', filter: 'blur(0)' }], { duration: 1500, delay: 250 + (1 - i) * 180, easing: ease, fill: 'backwards' }));
        $$('.lh-tag-in').forEach((el, i) => el.animate([{ opacity: 0, transform: 'scale(.8) translateY(14px)' }, { opacity: 1, transform: 'none' }], { duration: 800, delay: 1300 + i * 220, easing: 'cubic-bezier(.34,1.56,.64,1)', fill: 'forwards' }));
    };

    // Intro: el logo emerge del agua.
    const intro = document.querySelector('[data-lh-intro]');
    const forceIntro = /[?&]intro/.test(window.location.search);
    if (!intro || reduce || (!forceIntro && store.get('os-intro'))) {
        intro?.remove();
        heroIn();
    } else {
        intro.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        const end = () => {
            if (!intro.isConnected || intro.classList.contains('is-out')) {
                return;
            }
            intro.classList.add('is-out');
            store.set('os-intro', '1');
            document.documentElement.style.overflow = '';
            setTimeout(heroIn, 250);
            setTimeout(() => intro.remove(), 950);
        };
        setTimeout(end, 2300);
        intro.addEventListener('click', end);
    }

    // Burbujas.
    const bubbles = $('[data-lh-bubbles]');
    if (bubbles && !reduce) {
        for (let i = 0; i < 22; i += 1) {
            const bubble = document.createElement('i');
            const size = 4 + Math.random() * 12;
            Object.assign(bubble.style, { left: `${Math.random() * 100}%`, width: `${size}px`, height: `${size}px`, animationDuration: `${9 + Math.random() * 12}s`, animationDelay: `${-Math.random() * 20}s` });
            bubble.style.setProperty('--dx', `${Math.random() * 80 - 40}px`);
            bubbles.appendChild(bubble);
        }
    }

    // Parallax con el mouse en el hero.
    const hero = $('.lh-hero');
    const layers = hero ? $$('[data-depth]', hero) : [];
    if (hero && !reduce && window.matchMedia('(pointer: fine)').matches) {
        let frame = 0;
        hero.addEventListener('pointermove', (event) => {
            const rect = hero.getBoundingClientRect();
            const mx = (event.clientX - rect.left) / rect.width - 0.5;
            const my = (event.clientY - rect.top) / rect.height - 0.5;
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => layers.forEach((layer) => {
                const depth = Number(layer.dataset.depth);
                layer.style.translate = `${-mx * depth}px ${-my * depth * 0.6}px`;
            }));
        });
        hero.addEventListener('pointerleave', () => layers.forEach((layer) => { layer.style.translate = '0 0'; }));
    }

    // Cinta: se duplica para un bucle continuo.
    const ribbon = $('[data-lh-ribbon]');
    if (ribbon) {
        ribbon.innerHTML += ribbon.innerHTML;
        ribbon.innerHTML += ribbon.innerHTML;
    }

    // Revelado al hacer scroll (una máscara recortada no "intersecta": se observa su contenedor).
    const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
        if (entry.isIntersecting) {
            (entry.target.lhReveal || entry.target).classList.add('is-in');
            observer.unobserve(entry.target);
        }
    }), { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    $$('.rv').forEach((el) => observer.observe(el));
    $$('.rv-mask').forEach((el) => { el.parentElement.lhReveal = el; observer.observe(el.parentElement); });

    // Parallax de la foto editorial.
    const editorial = $('[data-lh-parallax]');
    if (editorial && !reduce) {
        const onScroll = () => {
            const rect = editorial.parentElement.getBoundingClientRect();
            editorial.style.transform = `translateY(${((rect.top + rect.height / 2 - window.innerHeight / 2) / window.innerHeight) * -6}%)`;
        };
        window.addEventListener('scroll', () => requestAnimationFrame(onScroll), { passive: true });
        onScroll();
    }

    // Carrusel de combos: avanza solo cada 5 s, se pausa con el mouse encima.
    const slider = $('[data-lh-slider]');
    const track = slider ? $('[data-lh-slides]', slider) : null;
    if (slider && track) {
        const slides = $$('.lh-slide', track);
        const dotsBox = $('[data-lh-dots]', slider);
        const duration = 5000;
        let index = 0;
        let timer = 0;

        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.setAttribute('aria-label', `Ir al combo ${i + 1}`);
            dot.addEventListener('click', () => go(i, true));
            dotsBox.appendChild(dot);
        });
        const dots = $$('button', dotsBox);
        const mark = (i) => {
            index = i;
            slides.forEach((slide, k) => slide.classList.toggle('is-active', k === i));
            dots.forEach((dot, k) => {
                dot.classList.remove('is-on');
                if (k === i) {
                    void dot.offsetWidth;
                    dot.classList.add('is-on');
                }
            });
        };
        const restart = () => {
            clearInterval(timer);
            if (!reduce) {
                timer = setInterval(() => { if (!slider.classList.contains('is-paused')) { go(index + 1); } }, duration);
            }
        };
        function go(i, byUser = false) {
            const target = (i + slides.length) % slides.length;
            track.scrollTo({ left: slides[target].offsetLeft - track.offsetLeft, behavior: reduce ? 'auto' : 'smooth' });
            mark(target);
            if (byUser) {
                restart();
            }
        }

        dotsBox.style.setProperty('--dur', `${duration}ms`);
        $('[data-lh-prev]', slider).addEventListener('click', () => go(index - 1, true));
        $('[data-lh-next]', slider).addEventListener('click', () => go(index + 1, true));
        slider.addEventListener('pointerenter', () => slider.classList.add('is-paused'));
        slider.addEventListener('pointerleave', () => slider.classList.remove('is-paused'));
        track.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') { go(index + 1, true); }
            if (event.key === 'ArrowLeft') { go(index - 1, true); }
        });
        let settle;
        track.addEventListener('scroll', () => {
            clearTimeout(settle);
            settle = setTimeout(() => {
                const x = track.scrollLeft;
                let best = 0;
                let bestDistance = Infinity;
                slides.forEach((slide, k) => {
                    const distance = Math.abs(slide.offsetLeft - track.offsetLeft - x);
                    if (distance < bestDistance) { bestDistance = distance; best = k; }
                });
                if (best !== index) { mark(best); restart(); }
            }, 120);
        }, { passive: true });
        mark(0);
        restart();
    }

    // Texturas que cambian.
    const fabrics = $$('[data-lh-fabric] [data-lh-fabric-item]');
    if (fabrics.length && !reduce) {
        let current = 0;
        setInterval(() => {
            fabrics[current].classList.remove('is-on');
            current = (current + 1) % fabrics.length;
            fabrics[current].classList.add('is-on');
        }, 1800);
    }
}
