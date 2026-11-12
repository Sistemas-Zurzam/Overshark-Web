// Calendarios del panel (flatpickr): fecha y hora en un solo selector con el estilo del panel. El valor que se envía
// conserva el formato de datetime-local («2026-12-12T16:15»), así el servidor no cambia. Los campos se marcan con
// data-datetime; los botones data-end-hours="3" fijan el fin a N horas del inicio (promociones por horas).
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import '../css/admin-datetime.css';

const pickers = new Map();

document.querySelectorAll('input[data-datetime]').forEach((input) => {
    pickers.set(input.name, flatpickr(input, {
        locale: Spanish,
        enableTime: true,
        time_24hr: true,
        minuteIncrement: 5,
        dateFormat: 'Y-m-d\\TH:i',
        altInput: true,
        altFormat: 'D j M Y · H:i',
        altInputClass: `${input.className} cursor-pointer`,
        disableMobile: true,
        // Al abrir sin valor, empieza en la próxima hora en punto.
        defaultHour: (new Date().getHours() + 1) % 24,
        defaultMinute: 0,
        prevArrow: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m15 5-7 7 7 7"/></svg>',
        nextArrow: '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m9 5 7 7-7 7"/></svg>',
    }));
});

const start = pickers.get('starts_at');
const end = pickers.get('ends_at');

if (start && end) {
    // El fin no puede ser anterior al inicio.
    const sync = () => end.set('minDate', start.selectedDates[0] ?? null);
    start.config.onChange.push(sync);
    sync();

    document.querySelectorAll('[data-end-hours]').forEach((button) => button.addEventListener('click', () => {
        const from = start.selectedDates[0] ?? new Date();
        end.setDate(new Date(from.getTime() + Number(button.dataset.endHours) * 3600 * 1000), true);
    }));
}
