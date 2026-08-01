import './bootstrap';
import html2canvas from 'html2canvas';
import * as jalaali from 'jalaali-js';

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

// ---- Jalali (Persian) date picker — self-contained Alpine component ----
const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
const toFa = (s) => String(s).replace(/\d/g, (d) => FA[+d]);
const toLatin = (s) => String(s).replace(/[۰-۹]/g, (d) => FA.indexOf(d));
const J_MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
const J_WEEK = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

function parseJalali(s) {
    if (!s) return null;
    const m = toLatin(String(s)).match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
    return m ? { jy: +m[1], jm: +m[2], jd: +m[3] } : null;
}
function todayJalali() {
    const n = new Date();
    return jalaali.toJalaali(n.getFullYear(), n.getMonth() + 1, n.getDate());
}
function fmtJalali(jy, jm, jd) {
    return toFa(`${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`);
}

window.jalaliPicker = () => ({
    // `val` (the Jalali string) is provided by @entangle in the x-data literal.
    open: false,
    viewY: 0,
    viewM: 0,
    months: J_MONTHS,
    weekdays: J_WEEK,

    init() {
        this.resetView();
        this.$watch('open', (v) => { if (v) this.resetView(); });
    },
    resetView() {
        const p = parseJalali(this.val) || todayJalali();
        this.viewY = p.jy;
        this.viewM = p.jm;
    },
    label() {
        const p = parseJalali(this.val);
        return p ? fmtJalali(p.jy, p.jm, p.jd) : '';
    },
    heading() {
        return `${J_MONTHS[this.viewM - 1]} ${toFa(this.viewY)}`;
    },
    weeks() {
        const len = jalaali.jalaaliMonthLength(this.viewY, this.viewM);
        const g = jalaali.toGregorian(this.viewY, this.viewM, 1);
        const start = (new Date(g.gy, g.gm - 1, g.gd).getDay() + 1) % 7; // Saturday = 0
        const cells = Array(start).fill(null);
        for (let d = 1; d <= len; d++) cells.push(d);
        while (cells.length % 7) cells.push(null);
        const rows = [];
        for (let i = 0; i < cells.length; i += 7) rows.push(cells.slice(i, i + 7));
        return rows;
    },
    fa: toFa,
    isSelected(d) {
        const p = parseJalali(this.val);
        return !!p && p.jy === this.viewY && p.jm === this.viewM && p.jd === d;
    },
    isToday(d) {
        const t = todayJalali();
        return t.jy === this.viewY && t.jm === this.viewM && t.jd === d;
    },
    pick(d) {
        if (!d) return;
        this.val = fmtJalali(this.viewY, this.viewM, d);
        this.open = false;
    },
    prevMonth() { if (--this.viewM < 1) { this.viewM = 12; this.viewY--; } },
    nextMonth() { if (++this.viewM > 12) { this.viewM = 1; this.viewY++; } },
    goToday() { const t = todayJalali(); this.val = fmtJalali(t.jy, t.jm, t.jd); this.viewY = t.jy; this.viewM = t.jm; this.open = false; },
    clearDate() { this.val = ''; this.open = false; },
});
