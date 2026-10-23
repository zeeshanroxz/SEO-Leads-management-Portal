/*
 * Core Alpine helpers: modal + toast stores, tabs, dropdown, data table.
 * Everything here is presentation-only - no network calls except the
 * mock search/duplicate endpoints, which return fictional data.
 */
import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    // ---------------------------------------------------------------- stores
    Alpine.store('modal', {
        current: null,
        payload: {},
        open(name, payload = {}) {
            this.payload = payload;
            this.current = name;
        },
        close() {
            this.current = null;
        },
        is(name) {
            return this.current === name;
        },
        /** Generic confirmation (optionally asking for a mandatory reason). */
        confirm(payload) {
            this.open('confirm', payload);
        },
    });

    Alpine.store('toast', {
        items: [],
        seq: 0,
        push(message, type = 'success', opts = {}) {
            const id = ++this.seq;
            this.items.push({ id, message, type, demo: !!opts.demo });
            setTimeout(() => this.dismiss(id), opts.ttl ?? 6000);
        },
        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });

    // ----------------------------------------------------------------- tabs
    Alpine.data('tabs', (initial = 'overview', useHash = true) => ({
        tab: (useHash && window.location.hash.slice(1)) || initial,
        init() {
            if (!useHash) return;
            window.addEventListener('hashchange', () => { const h = window.location.hash.slice(1); if (h) this.tab = h; });
        },
        set(t) {
            this.tab = t;
            if (useHash) history.replaceState(null, '', '#' + t);
        },
    }));

    // ------------------------------------------------------------ data table
    /*
     * Progressive enhancement over a server-rendered <table>.
     * rows:    <tr data-row data-search="lowercase text" data-f-status="x" data-s-created="2026-01-01">
     * filters: x-model="filters.status" on <select>; matches data-f-status (supports "a|b").
     * sort:    @click="sort('created')" matches data-s-created
     */
    Alpine.data('dataTable', (opts = {}) => ({
        perPage: opts.perPage ?? 10,
        page: 1,
        q: '',
        sortKey: opts.sortKey ?? null,
        sortDir: opts.sortDir ?? 'desc',
        filters: opts.filters ?? {},
        rows: [],
        matched: 0,
        init() {
            this.rows = [...this.$root.querySelectorAll('tbody tr[data-row]')];
            this.$watch('q', () => { this.page = 1; this.apply(); });
            this.$watch('filters', () => { this.page = 1; this.apply(); }, { deep: true });
            this.apply();
        },
        sort(key) {
            if (this.sortKey === key) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            else { this.sortKey = key; this.sortDir = 'asc'; }
            this.page = 1;
            this.apply();
        },
        arrow(key) {
            return this.sortKey === key ? (this.sortDir === 'asc' ? '↑' : '↓') : '';
        },
        apply() {
            const q = this.q.trim().toLowerCase();
            let list = this.rows.filter((r) => {
                if (q && !(r.dataset.search || '').includes(q)) return false;
                for (const [k, v] of Object.entries(this.filters)) {
                    if (v === '' || v == null) continue;
                    const attr = r.getAttribute('data-f-' + k.replace(/_/g, '-')) ?? '';
                    if (!attr.split('|').includes(String(v))) return false;
                }
                return true;
            });
            if (this.sortKey) {
                const key = 'data-s-' + this.sortKey.replace(/_/g, '-');
                const dir = this.sortDir === 'asc' ? 1 : -1;
                list.sort((a, b) => {
                    const x = a.getAttribute(key) ?? '', y = b.getAttribute(key) ?? '';
                    const nx = parseFloat(x), ny = parseFloat(y);
                    if (!isNaN(nx) && !isNaN(ny) && /^-?[\d.]+$/.test(x) && /^-?[\d.]+$/.test(y)) return (nx - ny) * dir;
                    return x.localeCompare(y) * dir;
                });
                const tbody = this.rows[0]?.parentElement;
                if (tbody) list.forEach((r) => tbody.appendChild(r));
            }
            this.matched = list.length;
            if (this.page > this.pages) this.page = this.pages;
            const start = (this.page - 1) * this.perPage;
            const visible = new Set(list.slice(start, start + this.perPage));
            this.rows.forEach((r) => { r.hidden = !visible.has(r); });
        },
        get pages() { return Math.max(1, Math.ceil(this.matched / this.perPage)); },
        get from() { return this.matched ? (this.page - 1) * this.perPage + 1 : 0; },
        get to() { return Math.min(this.page * this.perPage, this.matched); },
        go(p) { this.page = Math.min(Math.max(1, p), this.pages); this.apply(); },
        reset() {
            this.q = '';
            Object.keys(this.filters).forEach((k) => (this.filters[k] = ''));
        },
        get isFiltered() {
            return this.q !== '' || Object.values(this.filters).some((v) => v !== '' && v != null);
        },
    }));

    // ------------------------------------------------------- global search
    Alpine.data('globalSearch', (url) => ({
        q: '',
        open: false,
        results: [],
        loading: false,
        timer: null,
        active: -1,
        onInput() {
            clearTimeout(this.timer);
            if (this.q.trim().length < 2) { this.results = []; this.open = false; return; }
            this.loading = true;
            this.timer = setTimeout(async () => {
                try {
                    const r = await fetch(url + '?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                    this.results = (await r.json()).results;
                } catch (e) { this.results = []; }
                this.loading = false;
                this.open = true;
                this.active = -1;
            }, 180);
        },
        move(d) {
            if (!this.results.length) return;
            this.active = (this.active + d + this.results.length) % this.results.length;
        },
        go() {
            const r = this.results[this.active] ?? this.results[0];
            if (r) window.location.href = r.url;
        },
    }));

    // ------------------------------------------------------- misc helpers
    Alpine.data('copyText', (text) => ({
        done: false,
        copy() {
            navigator.clipboard?.writeText(text);
            this.done = true;
            setTimeout(() => (this.done = false), 1500);
        },
    }));
});

/** POST JSON with the CSRF token (used by the mock endpoints only). */
window.postJson = async (url, data) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const r = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(data),
    });
    return r.json();
};
