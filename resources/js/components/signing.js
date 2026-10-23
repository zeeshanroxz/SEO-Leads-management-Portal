import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    /* Customer signing flow MOCKUP: review -> verify code -> sign -> receipt. Nothing is stored. */
    Alpine.data('signingFlow', (cfg) => ({
        step: cfg.signed ? 4 : 1,
        read: false,
        code: '',
        codeError: '',
        verified: false,
        method: 'typed',
        name: cfg.signerName ?? '',
        consent: false,
        hasInk: false,
        touched: false,
        offline: false,
        file: '',
        drawing: false,
        resend: 0,
        next() { this.step = Math.min(4, this.step + 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
        verify() {
            if (this.code.trim() === cfg.demoCode) { this.verified = true; this.codeError = ''; this.next(); }
            else this.codeError = 'That code is not right. In this demo the code is shown on screen.';
        },
        sendCode() { this.resend++; window.Alpine.store('toast').push('A new demo code was “sent” (simulated) — it is still ' + cfg.demoCode + '.', 'info', { demo: true }); },
        // signature pad
        initPad() {
            const c = this.$refs.pad;
            if (!c) return;
            const ratio = window.devicePixelRatio || 1;
            const w = c.clientWidth, h = c.clientHeight;
            c.width = w * ratio; c.height = h * ratio;
            const ctx = c.getContext('2d');
            ctx.scale(ratio, ratio); ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.strokeStyle = '#0f172a';
            const pos = (e) => { const r = c.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; };
            c.onpointerdown = (e) => { this.drawing = true; c.setPointerCapture(e.pointerId); const [x, y] = pos(e); ctx.beginPath(); ctx.moveTo(x, y); };
            c.onpointermove = (e) => { if (!this.drawing) return; const [x, y] = pos(e); ctx.lineTo(x, y); ctx.stroke(); this.hasInk = true; };
            c.onpointerup = () => { this.drawing = false; };
        },
        clearPad() { const c = this.$refs.pad; c.getContext('2d').clearRect(0, 0, c.width, c.height); this.hasInk = false; },
        get canSign() { return this.name.trim().length > 1 && this.consent && (this.method === 'typed' || this.hasInk); },
        submit(e) {
            this.touched = true;
            if (!this.canSign) e.preventDefault();
        },
    }));
});
