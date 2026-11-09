// Documentos legales (términos, privacidad) en un modal: un enlace [data-legal-open="clave"] abre el
// <dialog data-legal-modal="clave"> en vez de navegar (el href queda como respaldo sin JavaScript).
// Se cierra con la X, "Entendido", el fondo o Escape.
const modals = new Map([...document.querySelectorAll('[data-legal-modal]')].map((dialog) => [dialog.dataset.legalModal, dialog]));

if (modals.size > 0) {
    const open = (dialog) => {
        if (dialog.open) {
            return;
        }

        dialog.showModal();
        document.body.classList.add('overflow-hidden');

        // Siempre desde el principio del texto, también al reabrirlo (tras el diseño, para que no lo pise el scroll previo).
        const scroller = dialog.querySelector('[data-legal-scroll]');

        if (scroller) {
            scroller.scrollTop = 0;
            requestAnimationFrame(() => {
                scroller.scrollTop = 0;
            });
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-legal-open]');
        const dialog = link && modals.get(link.dataset.legalOpen);

        // Un clic con Ctrl/Cmd/Shift o botón central conserva su significado: abrir la página en otra pestaña.
        if (!dialog || event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
            return;
        }

        event.preventDefault();
        open(dialog);
    });

    modals.forEach((dialog) => {
        dialog.querySelectorAll('[data-legal-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));

        // Clic sobre el fondo oscuro (el propio <dialog>, fuera de su contenido).
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        // Cubre la X, el botón, el fondo y Escape.
        dialog.addEventListener('close', () => {
            if (document.querySelector('[data-cart-drawer]')?.classList.contains('translate-x-full') !== false) {
                document.body.classList.remove('overflow-hidden');
            }
        });
    });
}
