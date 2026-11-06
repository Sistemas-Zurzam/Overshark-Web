// Evita el doble envío de un formulario (doble clic en "Agregar al carrito"). Los botones se desactivan
// en el siguiente tick para no perder el name/value del botón pulsado (p. ej. "Comprar ahora" → checkout=1).
// Si la clienta vuelve con el botón Atrás (página restaurada de la caché), el formulario se puede volver a usar.
export function submitOnce(form) {
    if (!form) return;

    let sending = false;
    let previous = [];
    const buttons = () => [...form.querySelectorAll('button[type="submit"], button:not([type])')];

    form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        if (sending) {
            event.preventDefault();
            return;
        }
        sending = true;
        previous = buttons().map((button) => [button, button.disabled]);
        setTimeout(() => buttons().forEach((button) => { button.disabled = true; }));
    });

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted || !sending) return;
        sending = false;
        previous.forEach(([button, disabled]) => { button.disabled = disabled; });
    });
}
