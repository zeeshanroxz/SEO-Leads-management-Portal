import Alpine from 'alpinejs';

/*
 * Multi-step lead form. Questions come from the published form version of the
 * chosen project (admin-defined, no code change). Validation here is for
 * usability; the Laravel controller validates again.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('leadWizard', (cfg, initial, editable, mode) => ({
        cfg,
        mode,
        step: 1,
        steps: ['Project', 'Business', 'Contacts', 'Qualification', 'Review'],
        action: 'draft',
        f: {
            project_id: initial.project_id ?? '',
            source: initial.source ?? 'Cold call',
            service_ids: initial.service_ids ?? [],
            business_name: initial.business_name ?? '',
            business_email: initial.business_email ?? '',
            business_phone: initial.business_phone ?? '',
            business_address: initial.business_address ?? '',
            website: initial.website ?? '',
            industry: initial.industry ?? '',
            primary_contact: initial.primary_contact ?? 0,
            contacts: JSON.parse(JSON.stringify(initial.contacts ?? [{ name: '', designation: '', email: '', phone: '' }])),
            answers: JSON.parse(JSON.stringify(initial.answers ?? {})),
        },
        can: {
            business: editable.includes('business'),
            contacts: editable.includes('contacts'),
            answers: editable.includes('answers'),
        },
        errors: {},
        dup: { matches: [], blocked: false, can_override: cfg.canOverride, checked: false },
        override_reason: '',
        dupTimer: null,
        init() {
            ['business_name', 'business_email', 'business_phone', 'website'].forEach((k) =>
                this.$watch('f.' + k, () => this.queueDupCheck()));
            this.$watch('f.project_id', () => this.ensureAnswerShapes());
            this.ensureAnswerShapes();
            this.queueDupCheck(300);
        },

        // ------------------------------------------------------ questions
        get form() { return this.cfg.forms[this.f.project_id] ?? null; },
        get allQuestions() { return (this.form?.sections ?? []).flatMap((s) => s.fields); },
        get project() { return this.cfg.projects.find((p) => p.id === this.f.project_id) ?? null; },
        get projectServices() { return this.cfg.services.filter((s) => s.project_id === this.f.project_id); },
        ensureAnswerShapes() {
            (this.form?.sections ?? []).forEach((s) => s.fields.forEach((q) => {
                if (q.type === 'checkbox' && !Array.isArray(this.f.answers[q.key])) this.f.answers[q.key] = [];
                if (q.default != null && this.f.answers[q.key] == null && this.mode === 'create') this.f.answers[q.key] = q.default;
                if (this.f.answers[q.key] == null && q.type !== 'checkbox') this.f.answers[q.key] = '';
            }));
        },
        visible(q) {
            if (!q.active) return false;
            if (!q.condition) return true;
            return this.f.answers[q.condition.key] === q.condition.equals;
        },
        isReq(id) { return !!this.cfg.fixed[id]?.required; },

        // ----------------------------------------------------- duplicates
        queueDupCheck(delay = 600) {
            clearTimeout(this.dupTimer);
            this.dupTimer = setTimeout(() => this.checkDuplicates(), delay);
        },
        async checkDuplicates() {
            const { business_name, business_email, business_phone, website } = this.f;
            if (!business_name && !business_email && !business_phone && !website) { this.dup = { ...this.dup, matches: [], blocked: false, checked: false }; return; }
            try {
                const r = await window.postJson(this.cfg.duplicateUrl, { business_name, business_email, business_phone, website });
                this.dup = { ...this.dup, matches: r.matches, blocked: r.blocked, can_override: r.can_override, checked: true };
            } catch (e) { /* demo endpoint unavailable - ignore */ }
        },
        get overrideOk() { return this.dup.blocked && this.dup.can_override && this.override_reason.trim().length >= 10; },

        // ------------------------------------------------------ validation
        emailOk(v) { return !v || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
        validateStep(n, full = true) {
            const e = {};
            if (n === 1 && !this.f.project_id) e.project_id = 'Choose a project.';
            if (n === 2) {
                if (!this.f.business_name.trim()) e.business_name = 'Business name is required.';
                if (full) {
                    if (this.isReq('business_email') && !this.f.business_email.trim()) e.business_email = 'Business email is required.';
                    if (this.isReq('business_phone') && !this.f.business_phone.trim()) e.business_phone = 'Business contact number is required.';
                    if (this.isReq('business_address') && !this.f.business_address.trim()) e.business_address = 'Business address is required.';
                }
                if (!this.emailOk(this.f.business_email)) e.business_email = 'Enter a valid email address.';
            }
            if (n === 3 && full) {
                const c = this.f.contacts[0] ?? {};
                if (this.isReq('contact_name') && !(c.name ?? '').trim()) e['contacts.0.name'] = 'Contact name is required.';
                if (this.isReq('contact_designation') && !(c.designation ?? '').trim()) e['contacts.0.designation'] = 'Contact designation is required.';
                this.f.contacts.forEach((k, i) => { if (!this.emailOk(k.email)) e[`contacts.${i}.email`] = 'Enter a valid email address.'; });
            }
            if (n === 4 && this.can.answers) {
                (this.form?.sections ?? []).forEach((s) => s.fields.forEach((q) => {
                    if (!this.visible(q)) return;
                    const v = this.f.answers[q.key];
                    const empty = v == null || v === '' || (Array.isArray(v) && !v.length);
                    if (full && q.required && empty) { e['answers.' + q.key] = `"${q.label}" is required.`; return; }
                    if (!empty && (q.type === 'number' || q.type === 'percentage')) {
                        const num = Number(v);
                        const min = q.min ?? 0, max = q.max ?? (q.type === 'percentage' ? 100 : null);
                        if (Number.isNaN(num)) e['answers.' + q.key] = 'Enter a number.';
                        else if (num < min) e['answers.' + q.key] = `Minimum is ${min}.`;
                        else if (max != null && num > max) e['answers.' + q.key] = `Maximum is ${max}.`;
                    }
                }));
            }
            return e;
        },
        err(k) { return this.errors[k]; },
        next() {
            const e = this.validateStep(this.step, this.action !== 'draft' || true);
            this.errors = e;
            if (Object.keys(e).length) { this.$nextTick(() => document.querySelector('[data-err]')?.focus()); return; }
            this.step = Math.min(this.steps.length, this.step + 1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        back() { this.errors = {}; this.step = Math.max(1, this.step - 1); },
        goto(n) { if (n <= this.step || this.mode === 'edit') { this.errors = {}; this.step = n; } },
        submit(action) {
            this.action = action;
            if (action === 'draft') {
                const e = { ...this.validateStep(1), ...this.validateStep(2, false), ...this.validateStep(4, false) };
                this.errors = e;
                if (Object.keys(e).length) { this.step = e.project_id ? 1 : (e.business_name ? 2 : 4); return; }
            } else {
                for (let n = 1; n <= 4; n++) {
                    const e = this.validateStep(n);
                    if (Object.keys(e).length) { this.errors = e; this.step = n; window.Alpine.store('toast').push('Please fix the highlighted fields before submitting.', 'error'); return; }
                }
                if (this.dup.blocked && !this.overrideOk) {
                    this.step = 5;
                    window.Alpine.store('toast').push(this.dup.can_override ? 'Duplicate found: enter an override reason (min 10 characters) to continue.' : 'Duplicate lead blocked. Ask an Admin to override with a reason.', 'error');
                    return;
                }
            }
            this.$nextTick(() => this.$root.submit());
        },
        addContact() { this.f.contacts.push({ name: '', designation: '', email: '', phone: '' }); },
        removeContact(i) {
            this.f.contacts.splice(i, 1);
            if (this.f.primary_contact >= this.f.contacts.length) this.f.primary_contact = 0;
        },
        answerText(q) {
            const v = this.f.answers[q.key];
            if (Array.isArray(v)) return v.join(', ') || '—';
            if (v === '' || v == null) return '—';
            return q.type === 'percentage' ? v + '%' : v;
        },
    }));
});
