import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('agreementBuilder', (cfg) => ({
        cfg,
        lead_id: cfg.lead ?? '',
        lines: [],
        term_months: 12,
        start_date: '',
        currency: 'USD',
        renewal: 'Auto-renews monthly after the initial term unless cancelled with 30 days notice (prototype default).',
        payment_schedule: 'Monthly in advance',
        clause_ids: [],
        signer_name: '',
        signer_email: '',
        touched: false,
        init() {
            this.$watch('lead_id', () => this.onLead());
            this.$watch('lines', () => this.syncClauses(), { deep: true });
            if (this.lead_id) this.onLead();
        },
        onLead() {
            const ids = this.cfg.leadServices[this.lead_id] ?? [];
            this.lines = ids.map((id) => this.newLine(id));
            const c = (this.cfg.contacts[this.lead_id] ?? []).find((k) => k.primary) ?? (this.cfg.contacts[this.lead_id] ?? [])[0];
            this.signer_name = c?.name ?? '';
            this.signer_email = c?.email ?? '';
            this.syncClauses();
        },
        service(id) { return this.cfg.services.find((s) => s.id === id); },
        newLine(id) { const s = this.service(id) ?? this.cfg.services[0]; return { service_id: s.id, price: s.price, billing: s.billing, qty: 1 }; },
        addLine() { this.lines.push(this.newLine(this.cfg.services[0].id)); },
        removeLine(i) { this.lines.splice(i, 1); },
        changeService(l) { const s = this.service(l.service_id); l.price = s.price; l.billing = s.billing; },
        syncClauses() {
            const wanted = new Set(this.lines.flatMap((l) => this.service(l.service_id)?.clauses ?? []));
            // keep manual picks, add service defaults
            wanted.forEach((id) => { if (!this.clause_ids.includes(id)) this.clause_ids.push(id); });
        },
        clause(id) { return this.cfg.clauses.find((c) => c.id === id); },
        get mrr() { return this.lines.filter((l) => l.billing === 'recurring').reduce((a, l) => a + Number(l.price || 0) * Number(l.qty || 1), 0); },
        get once() { return this.lines.filter((l) => l.billing === 'one_time').reduce((a, l) => a + Number(l.price || 0) * Number(l.qty || 1), 0); },
        money(n) { return '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); },
        get errors() {
            const e = {};
            if (!this.lead_id) e.lead_id = 'Choose a Closed Won opportunity.';
            if (!this.lines.length) e.lines = 'Add at least one service line.';
            if (this.lines.some((l) => !(Number(l.price) >= 0) || l.price === '')) e.price = 'Every line needs a price.';
            if (!this.term_months || this.term_months < 1) e.term = 'Enter the term in months.';
            if (!this.signer_name.trim()) e.signer_name = 'Name the customer signer.';
            if (!/^\S+@\S+\.\S+$/.test(this.signer_email)) e.signer_email = 'Enter a valid signer email (used for the one-time code).';
            return e;
        },
        submit(ev) { this.touched = true; if (Object.keys(this.errors).length) ev.preventDefault(); },
    }));
});
