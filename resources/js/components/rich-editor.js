import Alpine from 'alpinejs';

const svgUri = (svg) => 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);

const sampleChart = () => svgUri(`<svg xmlns="http://www.w3.org/2000/svg" width="460" height="260" viewBox="0 0 460 260" font-family="Arial" font-size="11">
<rect width="460" height="260" fill="#fff"/><text x="230" y="18" text-anchor="middle" font-size="13" font-weight="bold" fill="#0f172a">Organic visits per month (sample data)</text>
<line x1="50" y1="40" x2="50" y2="205" stroke="#94a3b8"/><line x1="50" y1="205" x2="440" y2="205" stroke="#94a3b8"/>
${[0, 1, 2, 3].map((i) => `<text x="44" y="${208 - i * 50}" text-anchor="end" fill="#64748b">${i * 500}</text><line x1="50" y1="${205 - i * 50}" x2="440" y2="${205 - i * 50}" stroke="#e2e8f0"/>`).join('')}
${[['Jul', 620], ['Aug', 790], ['Sep', 910], ['Oct', 1180]].map(([m, v], i) => `<rect x="${80 + i * 88}" y="${205 - v / 10}" width="52" height="${v / 10}" fill="#3d6af0" rx="3"/><text x="${106 + i * 88}" y="222" text-anchor="middle" fill="#334155">${m}</text><text x="${106 + i * 88}" y="${199 - v / 10}" text-anchor="middle" fill="#334155">${v}</text>`).join('')}
<text x="245" y="248" text-anchor="middle" fill="#64748b">Month</text><text transform="translate(14 125) rotate(-90)" text-anchor="middle" fill="#64748b">Visits (count)</text></svg>`);

const samplePhoto = () => svgUri(`<svg xmlns="http://www.w3.org/2000/svg" width="460" height="220" viewBox="0 0 460 220" font-family="Arial"><rect width="460" height="220" fill="#eef2ff"/><rect x="20" y="20" width="420" height="26" rx="6" fill="#c7d2fe"/><rect x="20" y="62" width="200" height="130" rx="8" fill="#fff" stroke="#c7d2fe"/><rect x="240" y="62" width="200" height="60" rx="8" fill="#fff" stroke="#c7d2fe"/><rect x="240" y="132" width="200" height="60" rx="8" fill="#fff" stroke="#c7d2fe"/><text x="230" y="212" text-anchor="middle" font-size="11" fill="#6366f1">Sample screenshot placeholder</text></svg>`);

document.addEventListener('alpine:init', () => {
    /*
     * Rich-text editor MOCKUP built on contenteditable. Output is sanitised on
     * the server; Phase 2 should use a maintained editor + sanitiser library.
     */
    Alpine.data('richEditor', (opts = {}) => ({
        full: opts.full ?? true,
        range: null,
        linkOpen: false,
        linkUrl: 'https://',
        init() {
            this.$refs.surface.innerHTML = opts.html || '';
            this.sync();
        },
        focus() { this.$refs.surface.focus(); },
        sync() { this.$refs.input.value = this.$refs.surface.innerHTML; },
        exec(cmd, val = null) { this.focus(); document.execCommand(cmd, false, val); this.sync(); },
        block(tag) { this.exec('formatBlock', tag); },
        html(markup) { this.focus(); document.execCommand('insertHTML', false, markup); this.sync(); },
        onPaste(e) {
            e.preventDefault();
            const t = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, t);
            this.sync();
        },
        openLink() {
            const sel = window.getSelection();
            this.range = sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
            this.linkOpen = true;
            this.$nextTick(() => this.$refs.link?.focus());
        },
        applyLink() {
            this.linkOpen = false;
            if (!/^https?:\/\//i.test(this.linkUrl) || this.linkUrl.length < 9) return;
            this.focus();
            if (this.range) { const s = window.getSelection(); s.removeAllRanges(); s.addRange(this.range); }
            document.execCommand('createLink', false, this.linkUrl);
            this.sync();
        },
        table() {
            this.html('<table><thead><tr><th>Item</th><th>Finding</th><th>Priority</th></tr></thead><tbody><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table><p></p>');
        },
        chart() { this.html(`<p><img src="${sampleChart()}" alt="Bar chart: organic visits per month (sample data)" width="460"></p>`); },
        image() { this.html(`<p><img src="${samplePhoto()}" alt="Sample screenshot placeholder" width="460"></p>`); },
        upload(e) {
            const f = e.target.files[0];
            if (!f) return;
            if (!/^image\/(png|jpe?g|gif|webp)$/.test(f.type) || f.size > 300 * 1024) { window.Alpine.store('toast').push('Use a PNG/JPG/GIF/WebP under 300 KB (demo limit).', 'error'); e.target.value = ''; return; }
            const r = new FileReader();
            r.onload = () => this.html(`<p><img src="${r.result}" alt="${f.name.replace(/[^\w. -]/g, '')}" width="460"></p>`);
            r.readAsDataURL(f);
            e.target.value = '';
        },
        internalBlock() {
            this.html('<div data-visibility="internal"><p>Internal note — not sent to the customer.</p></div><p></p>');
        },
        get words() { return (this.$refs.surface?.innerText || '').trim().split(/\s+/).filter(Boolean).length; },
    }));
});
