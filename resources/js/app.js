import './bootstrap';

import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js';

// Persian (Jalali) date picker — attaches to any input with [data-jdp].
// Uses event delegation on document.body, so Livewire-added modal inputs
// work without re-initialisation. startWatch is idempotent.
const startJalaliDatepicker = () => {
    if (!window.jalaliDatepicker) {
        return;
    }

    window.jalaliDatepicker.startWatch({
        time: false,
        persianDigits: true,
        showTodayBtn: true,
        showEmptyBtn: true,
        autoShow: true,
        autoHide: true,
        hideAfterChange: true,
    });
};

document.addEventListener('DOMContentLoaded', startJalaliDatepicker);
document.addEventListener('livewire:navigated', startJalaliDatepicker);
