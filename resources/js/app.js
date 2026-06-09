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

const cartDrawer = document.querySelector('[data-cart-drawer]');
const cartOverlay = document.querySelector('[data-cart-overlay]');
const cartOpenButtons = document.querySelectorAll('[data-cart-open]');
const cartCloseButtons = document.querySelectorAll('[data-cart-close]');

const openCart = () => {
    cartDrawer?.classList.remove('translate-x-full');
    cartOverlay?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
};

const closeCart = () => {
    cartDrawer?.classList.add('translate-x-full');
    cartOverlay?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

cartOpenButtons.forEach((button) => button.addEventListener('click', openCart));
cartCloseButtons.forEach((button) => button.addEventListener('click', closeCart));
cartOverlay?.addEventListener('click', closeCart);

if (cartDrawer?.dataset.open === 'true') {
    openCart();
}

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

const setProductImage = (article, src) => {
    const image = article?.querySelector('[data-product-card-image]');

    if (image && src) {
        image.setAttribute('src', src);
        image.style.transform = '';
        image.style.transformOrigin = '';
    }
};

const bindProductThumbnails = (article) => {
    article?.querySelectorAll('[data-product-thumbnail]').forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => setProductImage(article, thumbnail.dataset.image));
    });
};

const parseProductImages = (button) => {
    try {
        return JSON.parse(button.dataset.images || '[]');
    } catch {
        return [];
    }
};

const renderProductThumbnails = (article, images) => {
    const thumbnails = article?.querySelector('[data-product-thumbnails]');

    if (!thumbnails || !images.length) {
        return;
    }

    thumbnails.innerHTML = images.slice(0, 5).map((src) => `
        <button type="button" data-product-thumbnail data-image="${src}" class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-[#F7F7F7] p-1 transition hover:border-cyan-600 sm:h-28 sm:w-full">
            <img src="${src}" alt="" class="h-full w-full rounded-lg object-cover">
        </button>
    `).join('');
    bindProductThumbnails(article);
};

document.querySelectorAll('article').forEach(bindProductThumbnails);

document.querySelectorAll('[data-product-color]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('article');

        setProductImage(card, button.dataset.image);

        const selectedColor = card?.querySelector('[data-product-selected-color]');

        if (selectedColor && button.dataset.colorName) {
            selectedColor.textContent = button.dataset.colorName;
        }

        if (button.dataset.colorName) {
            const cartColor = card?.querySelector('[data-cart-color]');
            if (cartColor) {
                cartColor.value = button.dataset.colorName;
            }

            card?.querySelectorAll('[data-product-color][data-color-name]').forEach((swatch) => {
                swatch.classList.remove('ring-2', 'ring-slate-950');
            });

            button.classList.add('ring-2', 'ring-slate-950');
            renderProductThumbnails(card, parseProductImages(button));
        }
    });
});

document.querySelectorAll('[data-product-colors-expand]').forEach((button) => {
    button.addEventListener('click', () => {
        const card = button.closest('article');

        card?.querySelectorAll('[data-extra-product-color]').forEach((swatch) => {
            swatch.classList.remove('hidden');
        });

        button.classList.add('hidden');
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

        const cartSize = article.querySelector('[data-cart-size]');
        if (cartSize) {
            cartSize.value = button.dataset.productSize;
        }

        updateColorsForSize(article, button.dataset.productSize);
    });
});

document.querySelectorAll('article').forEach((article) => {
    const selectedSize = article.querySelector('[data-product-size].bg-slate-950') || article.querySelector('[data-product-size]');

    if (selectedSize) {
        updateColorsForSize(article, selectedSize.dataset.productSize);
    }
});

document.querySelectorAll('[data-product-info-tabs]').forEach((tabs) => {
    const buttons = Array.from(tabs.querySelectorAll('[data-product-info-tab]'));
    const panels = Array.from(tabs.querySelectorAll('[data-product-info-panel]'));

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((tabButton) => {
                const isActive = tabButton === button;
                tabButton.classList.toggle('border-slate-950', isActive);
                tabButton.classList.toggle('text-slate-950', isActive);
                tabButton.classList.toggle('border-transparent', !isActive);
                tabButton.classList.toggle('text-slate-500', !isActive);
            });

            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.productInfoPanel !== button.dataset.productInfoTab);
            });
        });
    });
});

const productZoomModal = document.querySelector('[data-product-zoom-modal]');
const productZoomImage = document.querySelector('[data-product-zoom-image]');
const productZoomOpen = document.querySelector('[data-product-zoom-open]');
const productZoomClose = document.querySelector('[data-product-zoom-close]');
const productLensFrame = document.querySelector('[data-product-zoom-frame]');
const productLensToggle = document.querySelector('[data-product-lens-toggle]');
const sizeGuideModal = document.querySelector('[data-size-guide-modal]');
const sizeGuideOpen = document.querySelector('[data-size-guide-open]');
const sizeGuideClose = document.querySelector('[data-size-guide-close]');
let isProductLensActive = false;

const closeProductZoom = () => {
    productZoomModal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
};

const closeSizeGuide = () => {
    sizeGuideModal?.classList.add('hidden');
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
productLensToggle?.addEventListener('click', () => {
    const image = productLensFrame?.querySelector('[data-product-card-image]');
    isProductLensActive = !isProductLensActive;
    productLensToggle.setAttribute('aria-pressed', String(isProductLensActive));
    productLensToggle.classList.toggle('ring-2', isProductLensActive);
    productLensToggle.classList.toggle('ring-slate-950', isProductLensActive);
    productLensFrame?.classList.toggle('cursor-zoom-in', !isProductLensActive);
    productLensFrame?.classList.toggle('cursor-zoom-out', isProductLensActive);

    if (!isProductLensActive && image) {
        image.style.transform = '';
        image.style.transformOrigin = '';
    }
});

productLensFrame?.addEventListener('mousemove', (event) => {
    if (!isProductLensActive) {
        return;
    }

    const image = productLensFrame.querySelector('[data-product-card-image]');
    const rect = productLensFrame.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    if (image) {
        image.style.transformOrigin = `${x}% ${y}%`;
        image.style.transform = 'scale(2.15)';
    }
});

productLensFrame?.addEventListener('mouseleave', () => {
    const image = productLensFrame.querySelector('[data-product-card-image]');

    if (image) {
        image.style.transform = '';
        image.style.transformOrigin = '';
    }
});
sizeGuideOpen?.addEventListener('click', () => {
    sizeGuideModal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
});
sizeGuideClose?.addEventListener('click', closeSizeGuide);

productZoomModal?.addEventListener('click', (event) => {
    if (event.target === productZoomModal) {
        closeProductZoom();
    }
});

sizeGuideModal?.addEventListener('click', (event) => {
    if (event.target === sizeGuideModal) {
        closeSizeGuide();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeProductZoom();
        closeSizeGuide();
        closeCart();
    }
});

document.querySelectorAll('[data-qty-input]').forEach((input) => {
    const wrapper = input.closest('div');
    const decrement = wrapper?.querySelector('[data-qty-dec]');
    const increment = wrapper?.querySelector('[data-qty-inc]');

    decrement?.addEventListener('click', () => {
        input.value = String(Math.max(1, Number(input.value || 1) - 1));
        const cartQty = input.closest('article')?.querySelector('[data-cart-qty]');
        if (cartQty) {
            cartQty.value = input.value;
        }
    });

    increment?.addEventListener('click', () => {
        input.value = String(Number(input.value || 1) + 1);
        const cartQty = input.closest('article')?.querySelector('[data-cart-qty]');
        if (cartQty) {
            cartQty.value = input.value;
        }
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

document.querySelectorAll('[data-banner-button-preview]').forEach((preview) => {
    preview.querySelectorAll('[data-banner-button-drag]').forEach((button) => {
        const form = preview.closest('article')?.querySelector('form[id^="banner-edit-"]');
        const index = button.dataset.bannerButtonDrag;
        const inputX = form?.querySelector(`[data-banner-button-x="${index}"]`);
        const inputY = form?.querySelector(`[data-banner-button-y="${index}"]`);

        const moveButton = (event) => {
            const rect = preview.getBoundingClientRect();
            const x = Math.min(100, Math.max(0, ((event.clientX - rect.left) / rect.width) * 100));
            const y = Math.min(100, Math.max(0, ((event.clientY - rect.top) / rect.height) * 100));

            button.style.left = `${x}%`;
            button.style.top = `${y}%`;

            if (inputX) {
                inputX.value = x.toFixed(2);
            }

            if (inputY) {
                inputY.value = y.toFixed(2);
            }
        };

        button.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            button.setPointerCapture(event.pointerId);
            moveButton(event);
        });

        button.addEventListener('pointermove', (event) => {
            if (button.hasPointerCapture(event.pointerId)) {
                moveButton(event);
            }
        });

        button.addEventListener('pointerup', (event) => {
            if (button.hasPointerCapture(event.pointerId)) {
                button.releasePointerCapture(event.pointerId);
            }
        });
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
