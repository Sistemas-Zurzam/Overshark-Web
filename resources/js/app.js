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

const bannerForm = document.querySelector('[data-banner-form]');

document.querySelectorAll('[data-banner-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        bannerForm?.classList.toggle('hidden');
        bannerForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

const comboForm = document.querySelector('[data-combo-form]');

document.querySelectorAll('[data-combo-form-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        comboForm?.classList.toggle('hidden');
        comboForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
