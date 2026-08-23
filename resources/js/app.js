import './bootstrap';
import html2canvas from 'html2canvas';
import * as jalaali from 'jalaali-js';
import Resumable from 'resumablejs';

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

// ---- Resumable (chunked) file upload — Alpine component ----
// Drag & drop, paste, click-to-browse, a modern progress loader, and resume.
// On success it writes the stored path into the bound Livewire property.
window.resumableUpload = (opts) => ({
    model: opts.model,
    folder: opts.folder,
    accept: opts.accept || 'image/*,application/pdf',
    target: opts.target,
    // state
    uploading: false,
    progress: 0,
    error: '',
    fileName: '',
    path: '',
    previewUrl: '',
    dragOver: false,
    r: null,

    applyPath(v) {
        this.path = v || '';
        this.fileName = this.path ? this.path.split('/').pop() : '';
        this.previewUrl = this.path && opts.baseUrl ? opts.baseUrl.replace('__PATH__', this.path) : '';
    },

    init() {
        // Existing value (edit mode): show that a file is already attached.
        this.applyPath(this.$wire.get(this.model));

        // The property can change server-side (e.g. opening an edit form) while
        // this component is wire:ignore'd — keep the preview in sync, unless we
        // are the ones mid-upload.
        this.$wire.$watch(this.model, (v) => { if (!this.uploading) this.applyPath(v); });

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const self = this;
        this.r = new Resumable({
            target: this.target,
            testChunks: true,                 // ask the server which chunks exist → resume
            chunkSize: 1 * 1024 * 1024,       // 1 MB
            simultaneousUploads: 3,
            maxFiles: 1,
            fileType: ['jpg', 'jpeg', 'png', 'pdf'],
            query: { folder: this.folder },
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            forceChunkSize: false,
            fileTypeErrorCallback() { self.error = 'فقط تصویر (JPG/PNG) یا PDF مجاز است.'; },
        });

        this.r.assignBrowse(this.$refs.browse);
        this.r.assignDrop(this.$refs.zone);

        this.r.on('fileAdded', () => {
            this.error = '';
            this.progress = 0;
            this.uploading = true;
            this.fileName = this.r.files[this.r.files.length - 1]?.fileName || '';
            this.r.upload();
        });
        this.r.on('fileProgress', (file) => { this.progress = Math.round(file.progress() * 100); });
        this.r.on('fileSuccess', (file, message) => {
            this.uploading = false;
            this.progress = 100;
            let res = {};
            try { res = JSON.parse(message); } catch (e) { /* noop */ }
            if (res.path) {
                this.path = res.path;
                this.previewUrl = res.url || '';
                this.$wire.set(this.model, res.path);
            }
        });
        this.r.on('fileError', (file, message) => {
            this.uploading = false;
            let res = {};
            try { res = JSON.parse(message); } catch (e) { /* noop */ }
            this.error = res.message || 'بارگذاری ناموفق بود. دوباره تلاش کنید.';
        });
    },

    // Paste an image/file directly into the drop zone.
    onPaste(e) {
        const items = e.clipboardData?.items || [];
        for (const it of items) {
            if (it.kind === 'file') {
                const f = it.getAsFile();
                if (f) { this.r.addFile(f); e.preventDefault(); return; }
            }
        }
    },

    remove() {
        this.path = '';
        this.previewUrl = '';
        this.fileName = '';
        this.progress = 0;
        this.error = '';
        if (this.r) this.r.cancel();
        this.$wire.set(this.model, null);
    },

    isImage() {
        return /\.(jpe?g|png)$/i.test(this.fileName || this.path || '');
    },
});
