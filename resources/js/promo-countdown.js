// Cuenta regresiva de las promociones por horas: <time data-countdown="ISO-8601"> muestra "HH:MM:SS" (o "Nd HH:MM:SS").
// Al llegar a cero recarga la página una vez para que los precios vuelvan a su valor regular (el servidor ya no aplica la oferta).
const timers = [...document.querySelectorAll('[data-countdown]')];

if (timers.length > 0) {
    const pad = (n) => String(n).padStart(2, '0');
    let reloaded = false;

    const tick = () => {
        const now = Date.now();

        timers.forEach((el) => {
            const left = Math.floor((new Date(el.dataset.countdown).getTime() - now) / 1000);

            if (left <= 0) {
                el.textContent = '00:00:00';

                if (!reloaded) {
                    reloaded = true;
                    setTimeout(() => window.location.reload(), 1500);
                }

                return;
            }

            const days = Math.floor(left / 86400);
            const rest = left % 86400;
            const clock = `${pad(Math.floor(rest / 3600))}:${pad(Math.floor((rest % 3600) / 60))}:${pad(rest % 60)}`;

            el.textContent = days > 0 ? `${days}d ${clock}` : clock;
        });
    };

    tick();
    setInterval(tick, 1000);
}
