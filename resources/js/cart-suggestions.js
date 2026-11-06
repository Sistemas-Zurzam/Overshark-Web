// "Te puede interesar": en cada tarjeta la talla solo ofrece las tallas del color elegido, y el modal
// que se abre al agregar una prenda se cierra con la X, "Seguir comprando", el fondo o Escape.
document.querySelectorAll('[data-suggest-card]').forEach((card) => {
    let variants = {};

    try {
        variants = JSON.parse(card.dataset.variants || '{}');
    } catch {
        return;
    }

    const color = card.querySelector('[data-suggest-color]');
    const size = card.querySelector('[data-suggest-size]');

    color?.addEventListener('change', () => {
        const sizes = variants[color.value] || [];

        size.replaceChildren(...sizes.map((value) => new Option(value, value)));
    });
});

const suggestModal = document.querySelector('[data-suggest-modal]');

if (suggestModal) {
    const closeSuggest = () => {
        suggestModal.remove();

        // "Ver carrito" abre el carrito lateral en el mismo clic: ahí el scroll de la página debe seguir bloqueado.
        if (document.querySelector('[data-cart-drawer]')?.classList.contains('translate-x-full') !== false) {
            document.body.classList.remove('overflow-hidden');
        }
    };

    document.body.classList.add('overflow-hidden');
    suggestModal.querySelectorAll('[data-suggest-close]').forEach((element) => element.addEventListener('click', closeSuggest));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.contains(suggestModal)) {
            closeSuggest();
        }
    });
    suggestModal.querySelector('button[data-suggest-close]')?.focus();
}
