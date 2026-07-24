import './bootstrap';
import html2canvas from 'html2canvas';

import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js';

// Render an element to a PNG and trigger a download (report image export).
window.exportReportImage = async (selector, filename = 'report.png') => {
    const el = document.querySelector(selector);
    if (!el) return;
    const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#ffffff', useCORS: true });
    const link = document.createElement('a');
    link.download = filename;
    link.href = canvas.toDataURL('image/png');
    link.click();
};

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
