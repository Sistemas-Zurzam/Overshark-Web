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
