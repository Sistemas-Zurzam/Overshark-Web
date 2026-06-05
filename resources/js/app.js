import './bootstrap';

const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const toggle = document.querySelector('[data-sidebar-toggle]');

const closeSidebar = () => {
    sidebar?.classList.add('-translate-x-full');
    overlay?.classList.add('hidden');
};

toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('-translate-x-full');
    overlay?.classList.toggle('hidden');
});

overlay?.addEventListener('click', closeSidebar);

const webMenu = document.querySelector('[data-web-menu]');
const webMenuToggle = document.querySelector('[data-web-menu-toggle]');

webMenuToggle?.addEventListener('click', () => {
    const isHidden = webMenu?.classList.toggle('hidden');
    webMenuToggle.setAttribute('aria-expanded', String(!isHidden));
});

webMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        webMenu.classList.add('hidden');
        webMenuToggle?.setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('[data-carousel="slide"]').forEach((carousel) => {
    const items = Array.from(carousel.querySelectorAll('[data-carousel-item]'));
    const indicators = Array.from(carousel.querySelectorAll('[data-carousel-slide-to]'));
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    let activeIndex = Math.max(0, items.findIndex((item) => !item.classList.contains('hidden')));

    const showSlide = (index) => {
        if (!items.length) {
            return;
        }

        activeIndex = (index + items.length) % items.length;

        items.forEach((item, itemIndex) => {
            item.classList.toggle('hidden', itemIndex !== activeIndex);
            item.classList.toggle('block', itemIndex === activeIndex);
        });

        indicators.forEach((indicator, indicatorIndex) => {
            const isActive = indicatorIndex === activeIndex;
            indicator.classList.toggle('is-active', isActive);
            indicator.setAttribute('aria-current', String(isActive));
        });
    };

    indicators.forEach((indicator) => {
        indicator.addEventListener('click', () => showSlide(Number(indicator.dataset.carouselSlideTo)));
    });

    previous?.addEventListener('click', () => showSlide(activeIndex - 1));
    next?.addEventListener('click', () => showSlide(activeIndex + 1));

    if (items.length > 1) {
        window.setInterval(() => showSlide(activeIndex + 1), 6000);
    }

    showSlide(activeIndex);
});

document.querySelectorAll('[data-product-color]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('article');
        const image = card?.querySelector('[data-product-card-image]');

        if (image && button.dataset.image) {
            image.setAttribute('src', button.dataset.image);
        }

        const selectedColor = card?.querySelector('[data-product-selected-color]');

        if (selectedColor && button.dataset.colorName) {
            selectedColor.textContent = button.dataset.colorName;
        }

        card?.querySelectorAll('[data-product-color]').forEach((swatch) => {
            swatch.classList.remove('ring-2', 'ring-slate-950');
        });

        button.classList.add('ring-2', 'ring-slate-950');
    });
});

const parseAvailableSizes = (button) => {
    try {
        return JSON.parse(button.dataset.sizes || '[]');
    } catch {
        return [];
    }
};

const updateColorsForSize = (article, size) => {
    const colorButtons = Array.from(article.querySelectorAll('[data-product-color][data-sizes]'));

    colorButtons.forEach((button) => {
        const availableSizes = parseAvailableSizes(button);
        button.classList.toggle('hidden', !availableSizes.includes(size));
    });

    const activeColor = colorButtons.find((button) => button.classList.contains('ring-2') && !button.classList.contains('hidden'));

    if (!activeColor) {
        colorButtons.find((button) => !button.classList.contains('hidden'))?.click();
    }
};

document.querySelectorAll('[data-product-size]').forEach((button) => {
    button.addEventListener('click', () => {
        const article = button.closest('article');

        if (!article) {
            return;
        }

        article.querySelectorAll('[data-product-size]').forEach((sizeButton) => {
            sizeButton.classList.remove('bg-slate-950', 'text-white');
            sizeButton.classList.add('bg-white', 'text-slate-950');
        });

        button.classList.add('bg-slate-950', 'text-white');
        button.classList.remove('bg-white', 'text-slate-950');

        updateColorsForSize(article, button.dataset.productSize);
    });
});

document.querySelectorAll('article').forEach((article) => {
    const selectedSize = article.querySelector('[data-product-size].bg-slate-950') || article.querySelector('[data-product-size]');

    if (selectedSize) {
        updateColorsForSize(article, selectedSize.dataset.productSize);
    }
});

const productZoomModal = document.querySelector('[data-product-zoom-modal]');
const productZoomImage = document.querySelector('[data-product-zoom-image]');
const productZoomOpen = document.querySelector('[data-product-zoom-open]');
const productZoomClose = document.querySelector('[data-product-zoom-close]');

const closeProductZoom = () => {
    productZoomModal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

productZoomOpen?.addEventListener('click', () => {
    const currentImage = document.querySelector('[data-product-card-image]');

    if (productZoomImage && currentImage?.getAttribute('src')) {
        productZoomImage.setAttribute('src', currentImage.getAttribute('src'));
    }

    productZoomModal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
});

productZoomClose?.addEventListener('click', closeProductZoom);

productZoomModal?.addEventListener('click', (event) => {
    if (event.target === productZoomModal) {
        closeProductZoom();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeProductZoom();
    }
});

document.querySelectorAll('[data-qty-input]').forEach((input) => {
    const wrapper = input.closest('div');
    const decrement = wrapper?.querySelector('[data-qty-dec]');
    const increment = wrapper?.querySelector('[data-qty-inc]');

    decrement?.addEventListener('click', () => {
        input.value = String(Math.max(1, Number(input.value || 1) - 1));
    });

    increment?.addEventListener('click', () => {
        input.value = String(Number(input.value || 1) + 1);
    });
});

const bannerForm = document.querySelector('[data-banner-form]');

document.querySelectorAll('[data-banner-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        bannerForm?.classList.toggle('hidden');
        bannerForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

document.querySelectorAll('[data-banner-edit-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById(button.dataset.bannerEditToggle);
        form?.classList.toggle('hidden');
        form?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
});

const comboForm = document.querySelector('[data-combo-form]');

document.querySelectorAll('[data-combo-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        comboForm?.classList.toggle('hidden');
        comboForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

const paymentForm = document.querySelector('[data-payment-form]');

document.querySelectorAll('[data-payment-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        paymentForm?.classList.toggle('hidden');
        paymentForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

const combosMenu = document.querySelector('[data-combos-menu]');
const combosToggles = document.querySelectorAll('[data-combos-toggle]');
const combosChevron = document.querySelector('[data-combos-chevron]');

const closeCombosMenu = () => {
    if (combosMenu) {
        combosMenu.dataset.state = 'closed';
    }
    combosChevron?.classList.remove('rotate-180');
    combosToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'false'));
};

const openCombosMenu = () => {
    if (combosMenu) {
        combosMenu.dataset.state = 'open';
    }
    combosChevron?.classList.add('rotate-180');
    combosToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', 'true'));
};

combosToggles.forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        isOpen ? closeCombosMenu() : openCombosMenu();
    });
});

combosMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeCombosMenu));
combosMenu?.addEventListener('click', (event) => event.stopPropagation());
document.addEventListener('click', closeCombosMenu);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeCombosMenu();
    }
});
