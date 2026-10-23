import Alpine from 'alpinejs';

const TYPES = [
    ['text', 'Text (multi-line)'], ['short_answer', 'Short answer'], ['radio', 'Radio buttons (single choice)'],
    ['dropdown', 'Dropdown (single choice)'], ['checkbox', 'Multiple checkboxes'], ['number', 'Number'], ['percentage', 'Percentage'],
];
const HAS_OPTIONS = ['radio', 'dropdown', 'checkbox'];

/*
 * Admin form builder. Edits a DRAFT copy; publishing creates a new immutable
 * version, so earlier lead submissions keep the questions/answers they used.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('formBuilder', (initial, urls, publishedKeys) => ({
        types: TYPES,
        sections: JSON.parse(JSON.stringify(initial.sections)),
        sel: { s: 0, i: 0 },
        dirty: false,
        saving: false,
        drag: null,
        previewAnswers: {},
        errors: [],
        publishedKeys,
        init() {
            this.$watch('sections', () => { this.dirty = true; }, { deep: true });
            this.$nextTick(() => { this.dirty = false; });
            window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
        },
        get field() { return this.sections[this.sel.s]?.fields[this.sel.i] ?? null; },
        get allFields() { return this.sections.flatMap((s) => s.fields); },
        get count() { return this.allFields.length; },
        isPublished(f) { return this.publishedKeys.includes(f.key); },
        select(s, i) { this.sel = { s, i }; },
        hasOptions(f) { return f && HAS_OPTIONS.includes(f.type); },
        isNumeric(f) { return f && ['number', 'percentage'].includes(f.type); },
        typeLabel(t) { return TYPES.find((x) => x[0] === t)?.[1] ?? t; },
        slug(label) { return 'q_' + label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 30); },
        uniqueKey(base) {
            let k = base, n = 2;
            const keys = this.allFields.map((f) => f.key);
            while (keys.includes(k)) k = base + '_' + n++;
            return k;
        },
        newField(type = 'short_answer') {
            return { key: this.uniqueKey('q_new_question'), label: 'New question', type, help: '', required: false, options: HAS_OPTIONS.includes(type) ? ['Option 1', 'Option 2'] : [], min: null, max: null, active: true, default: null, condition: null, _new: true };
        },
        addField(s, type) {
            const f = this.newField(type);
            this.sections[s].fields.push(f);
            this.select(s, this.sections[s].fields.length - 1);
        },
        addSection() {
            this.sections.push({ key: 'sec_' + Date.now().toString(36), title: 'New section', fields: [] });
            this.addField(this.sections.length - 1, 'short_answer');
        },
        removeSection(s) {
            if (this.sections[s].fields.some((f) => this.isPublished(f))) { window.Alpine.store('toast').push('Sections with published questions cannot be deleted — disable the questions instead so old answers keep their meaning.', 'error'); return; }
            this.sections.splice(s, 1);
            this.sel = { s: 0, i: 0 };
        },
        move(s, i, dir) {
            const arr = this.sections[s].fields, j = i + dir;
            if (j < 0 || j >= arr.length) return;
            [arr[i], arr[j]] = [arr[j], arr[i]];
            this.select(s, j);
        },
        moveSection(s, dir) {
            const j = s + dir;
            if (j < 0 || j >= this.sections.length) return;
            [this.sections[s], this.sections[j]] = [this.sections[j], this.sections[s]];
            this.sel = { s: j, i: 0 };
        },
        duplicate(s, i) {
            const copy = JSON.parse(JSON.stringify(this.sections[s].fields[i]));
            copy.key = this.uniqueKey(copy.key + '_copy');
            copy.label += ' (copy)';
            copy._new = true;
            this.sections[s].fields.splice(i + 1, 0, copy);
            this.select(s, i + 1);
        },
        remove(s, i) {
            const f = this.sections[s].fields[i];
            if (this.isPublished(f)) { f.active = false; window.Alpine.store('toast').push('Published questions are disabled, not deleted — old answers stay intact.', 'info'); return; }
            this.sections[s].fields.splice(i, 1);
            this.sel = { s, i: Math.max(0, i - 1) };
        },
        setType(f, t) {
            f.type = t;
            if (HAS_OPTIONS.includes(t) && !f.options.length) f.options = ['Option 1', 'Option 2'];
            if (['number', 'percentage'].includes(t) && t === 'percentage') { f.min = 0; f.max = 100; }
        },
        get optionsText() { return (this.field?.options ?? []).join('\n'); },
        set optionsText(v) { if (this.field) this.field.options = v.split('\n').map((x) => x.trim()).filter(Boolean); },
        autoKey(f) { if (f._new) f.key = this.uniqueKey(this.slug(f.label)); },
        // drag & drop (HTML5)
        dragStart(s, i, e) { this.drag = { s, i }; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', 'q'); },
        dropOn(s, i) {
            if (!this.drag) return;
            const [f] = this.sections[this.drag.s].fields.splice(this.drag.i, 1);
            const idx = this.drag.s === s && this.drag.i < i ? i - 1 : i;
            this.sections[s].fields.splice(idx, 0, f);
            this.select(s, idx);
            this.drag = null;
        },
        dropEnd(s) {
            if (!this.drag) return;
            const [f] = this.sections[this.drag.s].fields.splice(this.drag.i, 1);
            this.sections[s].fields.push(f);
            this.select(s, this.sections[s].fields.length - 1);
            this.drag = null;
        },
        // validation
        validate() {
            const errs = [];
            const keys = new Set();
            this.sections.forEach((s) => {
                if (!s.title.trim()) errs.push('Every section needs a title.');
                s.fields.forEach((f) => {
                    if (!f.label.trim()) errs.push('Every question needs a label.');
                    if (keys.has(f.key)) errs.push(`Duplicate internal key "${f.key}".`);
                    keys.add(f.key);
                    if (HAS_OPTIONS.includes(f.type) && f.options.length < 2) errs.push(`"${f.label}" needs at least two options.`);
                    if (['number', 'percentage'].includes(f.type) && f.min != null && f.max != null && f.min !== '' && f.max !== '' && Number(f.min) > Number(f.max)) errs.push(`"${f.label}": minimum is above maximum.`);
                });
            });
            this.errors = [...new Set(errs)];
            return !this.errors.length;
        },
        payload() { return this.sections.map((s) => ({ ...s, fields: s.fields.map(({ _new, ...f }) => f) })); },
        async saveDraft() {
            if (!this.validate()) { window.Alpine.store('toast').push(this.errors[0], 'error'); return; }
            this.saving = true;
            try {
                const r = await window.postJson(urls.draft, { sections: this.payload() });
                this.dirty = false;
                window.Alpine.store('toast').push(r.message ?? 'Draft saved.', 'success');
            } catch (e) { window.Alpine.store('toast').push('Could not save the draft.', 'error'); }
            this.saving = false;
        },
        openPublish() {
            if (!this.validate()) { window.Alpine.store('toast').push(this.errors[0], 'error'); return; }
            window.Alpine.store('modal').open('publish');
        },
        // preview helpers
        visiblePreview(f) { return f.active && (!f.condition || this.previewAnswers[f.condition.key] === f.condition.equals); },
    }));
});
