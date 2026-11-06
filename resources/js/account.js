// Cuenta de cliente: mostrar/ocultar contraseña, menú de cuenta del header y departamento → provincia → distrito
// de la libreta de direcciones (mismo formato de ubigeo que el checkout).
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    const input = document.querySelector(button.dataset.togglePassword);

    button.addEventListener('click', () => {
        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Ocultar' : 'Mostrar';
    });
});

// El menú de cuenta del header es un <details>: se cierra al tocar fuera o con Escape.
const accountMenu = document.querySelector('[data-account-menu]');

if (accountMenu) {
    document.addEventListener('click', (event) => {
        if (!accountMenu.contains(event.target)) {
            accountMenu.removeAttribute('open');
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            accountMenu.removeAttribute('open');
        }
    });
}

const addressForm = document.querySelector('[data-address-form]');

if (addressForm) {
    const ubigeo = JSON.parse(addressForm.dataset.ubigeo || '[]');
    const departamento = addressForm.querySelector('[data-ad-departamento]');
    const provincia = addressForm.querySelector('[data-ad-provincia]');
    const distrito = addressForm.querySelector('[data-ad-distrito]');
    const saved = Number(addressForm.dataset.distrito || 0);

    const options = (items, placeholder, selected) => `<option value="">${placeholder}</option>`
        + items.map((item) => `<option value="${item.id}"${String(item.id) === String(selected) ? ' selected' : ''}>${item.name}</option>`).join('');

    let savedDep = '';
    let savedProv = '';

    ubigeo.forEach((dep) => dep.provincias.forEach((prov) => {
        if (prov.distritos.some((item) => item.id === saved)) {
            savedDep = dep.id;
            savedProv = prov.id;
        }
    }));

    const currentDep = () => ubigeo.find((dep) => String(dep.id) === departamento.value);
    const currentProv = () => currentDep()?.provincias.find((prov) => String(prov.id) === provincia.value);

    const fillProvinces = (selected = '') => {
        provincia.innerHTML = options(currentDep()?.provincias ?? [], 'Elige', selected);
        provincia.disabled = !currentDep();
    };
    const fillDistricts = (selected = '') => {
        distrito.innerHTML = options(currentProv()?.distritos ?? [], 'Elige', selected);
        distrito.disabled = !currentProv();
    };

    departamento.innerHTML = options(ubigeo, 'Elige', savedDep);
    fillProvinces(savedProv);
    fillDistricts(saved || '');

    departamento.addEventListener('change', () => { fillProvinces(); fillDistricts(); });
    provincia.addEventListener('change', () => fillDistricts());
}
