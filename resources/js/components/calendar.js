import Alpine from 'alpinejs';

const pad = (n) => String(n).padStart(2, '0');
const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const hm = (d) => `${pad(d.getHours())}:${pad(d.getMinutes())}`;
const parse = (s) => { const [d, t] = s.split(' '); const [y, m, da] = d.split('-').map(Number); const [h, mi] = t.split(':').map(Number); return new Date(y, m - 1, da, h, mi); };
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
const startOfWeek = (d) => { const x = new Date(d.getFullYear(), d.getMonth(), d.getDate()); const wd = (x.getDay() + 6) % 7; return addDays(x, -wd); };
const sameDay = (a, b) => ymd(a) === ymd(b);

const HOUR_PX = 52, FIRST_HOUR = 7, LAST_HOUR = 20;

const TYPE_STYLE = {
    meeting: 'bg-blue-50 border-blue-300 text-blue-900',
    call: 'bg-teal-50 border-teal-300 text-teal-900',
    follow_up: 'bg-amber-50 border-amber-300 text-amber-900',
    training: 'bg-violet-50 border-violet-300 text-violet-900',
};

/*
 * Internal portal calendar (works without any external account).
 * Day / week / month views, create / edit / reschedule / cancel through server forms.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('calendarApp', (raw, cfg) => ({
        events: raw.map((e) => ({ ...e, s: parse(e.start), e: parse(e.end) })),
        view: window.innerWidth < 768 ? 'day' : 'week',
        cursor: new Date(cfg.start.date + 'T12:00:00'),
        selected: null,
        mode: 'create',
        form: {},
        conflicts: [],
        typeFilter: '',
        hours: Array.from({ length: LAST_HOUR - FIRST_HOUR }, (_, i) => FIRST_HOUR + i),
        HOUR_PX,
        init() {
            if (cfg.start.event) {
                const ev = this.events.find((x) => x.id === cfg.start.event);
                if (ev) this.openDetail(ev);
            }
            if (cfg.start.new) this.openCreate(null, cfg.start.lead);
            this.$watch('form', () => this.checkConflicts(), { deep: true });
        },
        // ------------------------------------------------------ navigation
        today() { this.cursor = new Date(); },
        step(dir) {
            if (this.view === 'month') { const c = new Date(this.cursor); c.setMonth(c.getMonth() + dir, 1); this.cursor = c; }
            else this.cursor = addDays(this.cursor, (this.view === 'week' ? 7 : 1) * dir);
        },
        get title() {
            const o = { month: 'long', year: 'numeric' };
            if (this.view === 'month') return this.cursor.toLocaleDateString('en-GB', o);
            if (this.view === 'day') return this.cursor.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const a = startOfWeek(this.cursor), b = addDays(a, 6);
            return `${a.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })} – ${b.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`;
        },
        // ------------------------------------------------------ data
        visible() { return this.events.filter((e) => !this.typeFilter || e.type === this.typeFilter); },
        forDay(d) { return this.visible().filter((e) => sameDay(e.s, d)).sort((a, b) => a.s - b.s); },
        get monthDays() {
            const first = new Date(this.cursor.getFullYear(), this.cursor.getMonth(), 1);
            const start = startOfWeek(first);
            return Array.from({ length: 42 }, (_, i) => { const d = addDays(start, i); return { d, key: ymd(d), inMonth: d.getMonth() === this.cursor.getMonth(), today: sameDay(d, new Date()), events: this.forDay(d) }; });
        },
        get weekDays() {
            const start = this.view === 'day' ? new Date(this.cursor.getFullYear(), this.cursor.getMonth(), this.cursor.getDate()) : startOfWeek(this.cursor);
            return Array.from({ length: this.view === 'day' ? 1 : 7 }, (_, i) => { const d = addDays(start, i); return { d, key: ymd(d), today: sameDay(d, new Date()), events: this.layout(this.forDay(d)) }; });
        },
        layout(list) {
            // simple lane assignment so overlapping events sit side by side
            const lanes = [];
            const out = list.map((e) => ({ ...e }));
            out.forEach((e) => {
                let l = lanes.findIndex((end) => end <= e.s);
                if (l === -1) { l = lanes.length; lanes.push(e.e); } else lanes[l] = e.e;
                e.lane = l;
            });
            out.forEach((e) => { e.lanes = Math.max(1, lanes.length); });
            return out;
        },
        posStyle(e) {
            const top = Math.max(0, (e.s.getHours() + e.s.getMinutes() / 60 - FIRST_HOUR) * HOUR_PX);
            const height = Math.max(22, ((e.e - e.s) / 36e5) * HOUR_PX - 2);
            const w = 100 / e.lanes;
            return `top:${top}px;height:${height}px;left:calc(${e.lane * w}% + 2px);width:calc(${w}% - 4px)`;
        },
        chip(e) { return e.status === 'cancelled' ? 'bg-slate-100 border-slate-300 text-slate-500 line-through' : (TYPE_STYLE[e.type] ?? TYPE_STYLE.meeting); },
        fmtTime(d) { return hm(d); },
        dayLabel(d) { return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric' }); },
        nowTop() { const n = new Date(); return (n.getHours() + n.getMinutes() / 60 - FIRST_HOUR) * HOUR_PX; },
        goDay(d) { this.cursor = d; this.view = 'day'; },
        // ------------------------------------------------------ detail
        openDetail(ev) { this.selected = ev; window.Alpine.store('modal').open('event-detail'); },
        // ------------------------------------------------------ create / edit
        openCreate(d = null, lead = null, hour = null) {
            const day = d ?? this.cursor;
            const startH = hour ?? 10;
            this.mode = 'create';
            const leadObj = cfg.leads.find((l) => l.id === lead);
            this.form = {
                id: null, title: leadObj ? `Call - ${leadObj.name}` : '', type: leadObj ? 'meeting' : 'call', lead_id: lead ?? '', date: ymd(day), start_time: `${pad(startH)}:00`, end_time: `${pad(startH)}:30`,
                timezone: 'UTC', attendees: [], location: '', notes: '', reminder: 30,
            };
            window.Alpine.store('modal').open('event-form');
        },
        openEdit(ev) {
            this.mode = 'edit';
            this.form = { id: ev.id, title: ev.title, type: ev.type, lead_id: ev.lead_id ?? '', date: ymd(ev.s), start_time: hm(ev.s), end_time: hm(ev.e), timezone: ev.timezone, attendees: ev.attendees.map((a) => a.user_id), location: ev.location ?? '', notes: ev.notes ?? '', reminder: ev.reminders[0]?.minutes ?? 30 };
            window.Alpine.store('modal').open('event-form');
        },
        get formStart() { return this.form.date ? new Date(`${this.form.date}T${this.form.start_time || '00:00'}:00`) : null; },
        get formEnd() { return this.form.date ? new Date(`${this.form.date}T${this.form.end_time || '00:00'}:00`) : null; },
        get timeInvalid() { return this.formStart && this.formEnd && this.formEnd <= this.formStart; },
        checkConflicts() {
            if (!this.form.date || this.timeInvalid) { this.conflicts = []; return; }
            const people = new Set([cfg.me, ...(this.form.attendees ?? [])]);
            const s = this.formStart, e = this.formEnd;
            this.conflicts = this.events.filter((x) => x.id !== this.form.id && x.status !== 'cancelled' && x.s < e && x.e > s
                && (people.has(x.organizer_id) || x.attendees.some((a) => people.has(a.user_id))))
                .map((x) => `${x.title} (${hm(x.s)}–${hm(x.e)})`);
        },
        submitForm(ev) {
            if (!this.form.title.trim() || this.timeInvalid) { ev.preventDefault(); this.touched = true; }
        },
        touched: false,
        get leadOptions() { return cfg.leads; },
        canEdit(ev) { return ev.mine || cfg.isAdmin; },
        cancelEvent(ev) {
            window.Alpine.store('modal').confirm({
                title: `Cancel “${ev.title}”?`, body: 'The event is marked Cancelled and attendees are notified (simulated). It stays on the calendar for the record.',
                action: cfg.cancelUrl.replace('__ID__', ev.id), confirm: 'Cancel event', danger: true, reason: true,
            });
        },
        retrySync() { window.Alpine.store('toast').push('Sync retry simulated. The portal event was never at risk.', 'info', { demo: true }); },
        testReminder(ev) { window.Alpine.store('toast').push(`Reminder email for “${ev.title}” would be sent ${ev.reminders.find((r) => r.channel === 'email')?.minutes ?? 60} minutes before.`, 'info', { demo: true }); },
        respClass(r) { return { accepted: 'chip-green', tentative: 'chip-amber', declined: 'chip-red' }[r] ?? 'chip-slate'; },
    }));
});
