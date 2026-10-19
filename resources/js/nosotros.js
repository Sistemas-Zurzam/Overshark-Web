// Nosotros (web/nosotros.blade.php): aparición suave de las secciones al entrar en pantalla.
// Solo se animan las que empiezan fuera de la vista; nada queda oculto si se hace scroll rápido,
// si el navegador no soporta IntersectionObserver o si el usuario pidió reducir el movimiento.
const items = [...document.querySelectorAll('[data-nos-reveal]')];
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (items.length && !reduce && 'IntersectionObserver' in window) {
    const show = (el) => el.classList.add('is-visible');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting || entry.boundingClientRect.top < 0) {
                show(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    items
        .filter((el) => el.getBoundingClientRect().top > window.innerHeight)
        .forEach((el) => {
            el.classList.add('os-nos-reveal');
            observer.observe(el);
        });

    // Respaldo: si algo quedó sin mostrarse (scroll muy rápido, impresión), se muestra.
    window.addEventListener('scroll', () => {
        document.querySelectorAll('.os-nos-reveal:not(.is-visible)').forEach((el) => {
            if (el.getBoundingClientRect().top < window.innerHeight) show(el);
        });
    }, { passive: true });
    window.addEventListener('beforeprint', () => items.forEach(show));
}
