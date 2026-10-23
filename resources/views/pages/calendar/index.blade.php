@php
    $cfg = ['start' => $start, 'leads' => $leads, 'me' => $me, 'isAdmin' => demo()::is('admin'), 'cancelUrl' => route('calendar.cancel', '__ID__')];
@endphp
<x-app-layout title="Calendar" :crumbs="[['Calendar']]" subtitle="The internal portal calendar works without any external account. Google and Zoom sync is optional and simulated in this prototype.">
    <div x-data="calendarApp(@js($events), @js($cfg))" class="space-y-4">
        {{-- Toolbar --}}
        <div class="card flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm" @click="today()">Today</button>
                <button type="button" class="btn btn-ghost btn-icon" @click="step(-1)" aria-label="Previous"><x-ico name="chevron-left" class="size-5" /></button>
                <button type="button" class="btn btn-ghost btn-icon" @click="step(1)" aria-label="Next"><x-ico name="chevron-right" class="size-5" /></button>
                <h2 class="ml-1 text-base font-semibold text-slate-900" x-text="title" aria-live="polite"></h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select x-model="typeFilter" class="select h-8 w-40 text-[13px]" aria-label="Filter by type"><option value="">All event types</option>@foreach ($types as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5" role="group" aria-label="View">
                    @foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $k => $l)<button type="button" class="rounded-md px-3 py-1 text-[13px] font-medium" :class="view === '{{ $k }}' ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100'" @click="view = '{{ $k }}'" :aria-pressed="view === '{{ $k }}'">{{ $l }}</button>@endforeach
                </div>
                <a href="{{ route('integrations.calendar') }}" class="btn btn-secondary btn-sm"><x-ico name="link" class="size-4" /> Connections</a>
                <button type="button" class="btn btn-primary btn-sm" @click="openCreate()"><x-ico name="plus" class="size-4" /> New event</button>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4 px-1 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-blue-300"></span>Meeting</span><span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-teal-300"></span>Call</span><span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-amber-300"></span>Follow-up</span><span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-violet-300"></span>Training / internal</span>
            <span class="ml-auto">Times shown in UTC (prototype default timezone). Visibility follows your role: own, invited and supervised events.</span>
        </div>

        {{-- MONTH --}}
        <div class="card overflow-hidden" x-show="view === 'month'">
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">@foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)<div class="py-2">{{ $d }}</div>@endforeach</div>
            <div class="grid grid-cols-7">
                <template x-for="c in monthDays" :key="c.key">
                    <div class="min-h-28 border-b border-r border-slate-100 p-1.5" :class="c.inMonth ? 'bg-white' : 'bg-slate-50/70'">
                        <button type="button" class="mb-1 flex size-6 items-center justify-center rounded-full text-xs font-medium" :class="c.today ? 'bg-brand-600 text-white' : (c.inMonth ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-400')" @click="goDay(c.d)" x-text="c.d.getDate()" :aria-label="'Open ' + c.key"></button>
                        <div class="space-y-0.5">
                            <template x-for="e in c.events.slice(0, 3)" :key="e.id"><button type="button" class="block w-full truncate rounded border px-1.5 py-0.5 text-left text-[11px] leading-tight" :class="chip(e)" @click="openDetail(e)"><span x-text="fmtTime(e.s)" class="font-semibold"></span> <span x-text="e.title"></span></button></template>
                            <button type="button" class="px-1 text-[11px] font-medium text-brand-700 hover:underline" x-show="c.events.length > 3" @click="goDay(c.d)" x-text="'+' + (c.events.length - 3) + ' more'"></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- WEEK / DAY --}}
        <div class="card overflow-hidden" x-show="view !== 'month'" x-cloak>
            <div class="overflow-x-auto">
                <div :style="'min-width:' + (view === 'day' ? 360 : 760) + 'px'">
                    <div class="grid border-b border-slate-200 bg-slate-50" :style="'grid-template-columns: 56px repeat(' + weekDays.length + ', minmax(0, 1fr))'">
                        <div></div>
                        <template x-for="c in weekDays" :key="c.key"><button type="button" class="border-l border-slate-200 py-2 text-center text-xs font-semibold uppercase tracking-wide" :class="c.today ? 'text-brand-700' : 'text-slate-500'" @click="goDay(c.d)"><span x-text="dayLabel(c.d)"></span></button></template>
                    </div>
                    <div class="grid" :style="'grid-template-columns: 56px repeat(' + weekDays.length + ', minmax(0, 1fr))'">
                        <div>
                            <template x-for="h in hours" :key="h"><div class="relative border-t border-slate-100 pr-2 text-right text-[11px] text-slate-400" :style="'height:' + HOUR_PX + 'px'"><span class="relative -top-2 bg-white pl-1" x-text="String(h).padStart(2, '0') + ':00'"></span></div></template>
                        </div>
                        <template x-for="c in weekDays" :key="c.key">
                            <div class="relative border-l border-slate-200" :class="c.today ? 'bg-brand-50/30' : ''" :style="'height:' + (hours.length * HOUR_PX) + 'px'" @dblclick="openCreate(c.d)">
                                <template x-for="h in hours" :key="h"><div class="border-t border-slate-100" :style="'height:' + HOUR_PX + 'px'"></div></template>
                                <div class="pointer-events-none absolute inset-x-0 z-10 border-t-2 border-red-400" x-show="c.today && nowTop() > 0 && nowTop() < hours.length * HOUR_PX" :style="'top:' + nowTop() + 'px'"></div>
                                <template x-for="e in c.events" :key="e.id"><button type="button" class="absolute overflow-hidden rounded-md border px-1.5 py-1 text-left text-[11px] leading-tight shadow-sm hover:z-20 hover:shadow-md" :class="chip(e)" :style="posStyle(e)" @click="openDetail(e)">
                                    <span class="block truncate font-semibold" x-text="e.title"></span><span class="block truncate" x-text="fmtTime(e.s) + '–' + fmtTime(e.e)"></span></button></template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-400">Tip: double-click an empty slot to create an event.</p>
        </div>

        {{-- ============ Event detail ============ --}}
        <x-modal name="event-detail" title="Event" size="lg">
            <template x-if="selected">
                <div class="space-y-4">
                    <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold" :class="selected.status === 'cancelled' ? 'line-through text-slate-400' : ''" x-text="selected.title"></h3>
                        <p class="text-sm text-slate-500" x-text="selected.s.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + fmtTime(selected.s) + '–' + fmtTime(selected.e) + ' ' + selected.timezone"></p></div>
                        <span class="chip" :class="{ 'chip-blue': selected.status === 'scheduled', 'chip-amber': selected.status === 'rescheduled', 'chip-red': selected.status === 'cancelled', 'chip-green': selected.status === 'completed' }" x-text="selected.status.charAt(0).toUpperCase() + selected.status.slice(1)"></span></div>
                    <dl class="dl">
                        <div><dt>Organiser</dt><dd x-text="selected.organizer"></dd></div>
                        <div><dt>Type</dt><dd class="capitalize" x-text="selected.type.replace('_', '-')"></dd></div>
                        <div><dt>Location / link</dt><dd x-text="selected.location || '—'"></dd></div>
                        <div><dt>Linked opportunity</dt><dd><template x-if="selected.lead_id"><a :href="selected.url" class="text-brand-700 underline" x-text="selected.lead_name + ' (' + selected.lead_id + ')'"></a></template><template x-if="!selected.lead_id"><span>—</span></template></dd></div>
                        <div class="sm:col-span-2"><dt>Notes</dt><dd x-text="selected.notes || '—'"></dd></div>
                        <div class="sm:col-span-2" x-show="selected.cancel_reason"><dt>Cancellation reason</dt><dd x-text="selected.cancel_reason"></dd></div>
                    </dl>
                    <div><p class="mb-1.5 text-xs font-medium uppercase tracking-wide text-slate-500">Attendees</p>
                        <ul class="flex flex-wrap gap-2"><li class="flex items-center gap-1.5 rounded-full bg-slate-100 py-1 pl-2.5 pr-2.5 text-sm" x-text="selected.organizer + ' (organiser)'"></li><template x-for="a in selected.attendees" :key="a.user_id"><li class="flex items-center gap-1.5 rounded-full bg-slate-100 py-1 pl-2.5 pr-1.5 text-sm"><span x-text="a.name"></span><span class="chip" :class="respClass(a.response)" x-text="a.response.replace('_', ' ')"></span></li></template></ul></div>
                    <div class="rounded-lg border border-slate-200 p-3 text-sm">
                        <p class="mb-2 flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">External sync <span class="tag tag-demo">Simulated</span></p>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="flex items-center gap-1.5">Google: <span class="chip" :class="{ 'chip-green': selected.sync.google === 'synced', 'chip-red': selected.sync.google === 'failed', 'chip-slate': selected.sync.google === 'not_connected' }" x-text="selected.sync.google.replace('_', ' ')"></span></span>
                            <span class="flex items-center gap-1.5">Zoom: <span class="chip" :class="{ 'chip-green': selected.sync.zoom === 'synced', 'chip-red': selected.sync.zoom === 'failed', 'chip-slate': selected.sync.zoom === 'not_connected' }" x-text="selected.sync.zoom.replace('_', ' ')"></span></span>
                        </div>
                        <p class="mt-2 text-xs text-red-700" x-show="selected.sync.error" x-text="selected.sync.error"></p>
                        <button type="button" class="btn btn-secondary btn-xs mt-2" x-show="selected.sync.google === 'failed'" @click="retrySync()">Retry sync</button>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                        <button type="button" class="btn btn-ghost btn-sm" @click="testReminder(selected)"><x-ico name="bell" class="size-4" /> Preview email reminder</button>
                        <div class="flex gap-2" x-show="canEdit(selected) && selected.status !== 'cancelled'">
                            <button type="button" class="btn btn-secondary" @click="cancelEvent(selected)">Cancel event</button>
                            <button type="button" class="btn btn-secondary" @click="openEdit(selected)">Reschedule / edit</button>
                        </div>
                    </div>
                </div>
            </template>
        </x-modal>

        {{-- ============ Create / edit ============ --}}
        <x-modal name="event-form" title="Event" size="lg" description="Appointments are stored in the portal first. Invitations and email reminders are simulated.">
            <form method="POST" :action="mode === 'edit' ? '{{ route('calendar.update', '__ID__') }}'.replace('__ID__', form.id) : '{{ route('calendar.store') }}'" @submit="submitForm($event)" x-data="{ }">
                @csrf
                <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="label" for="ev-title">Title <span class="text-red-500">*</span></label><input id="ev-title" name="title" x-model="form.title" class="input" :class="{ 'input-error': touched && !form.title.trim() }"><p class="err" x-show="touched && !form.title.trim()" x-cloak>Give the event a title.</p></div>
                    <div><label class="label" for="ev-type">Type</label><select id="ev-type" name="type" x-model="form.type" class="select">@foreach ($types as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div><label class="label" for="ev-lead">Linked opportunity</label><select id="ev-lead" name="lead_id" x-model="form.lead_id" class="select"><option value="">None</option><template x-for="l in leadOptions" :key="l.id"><option :value="l.id" x-text="l.name + ' (' + l.id + ')'"></option></template></select></div>
                    <div><label class="label" for="ev-date">Date</label><input id="ev-date" type="date" name="date" x-model="form.date" class="input"></div>
                    <div class="grid grid-cols-2 gap-3"><div><label class="label" for="ev-start">Start</label><input id="ev-start" type="time" name="start_time" x-model="form.start_time" class="input"></div><div><label class="label" for="ev-end">End</label><input id="ev-end" type="time" name="end_time" x-model="form.end_time" class="input" :class="{ 'input-error': timeInvalid }"></div></div>
                    <p class="err sm:col-span-2" x-show="timeInvalid" x-cloak>The end time must be after the start time.</p>
                    <div><label class="label" for="ev-tz">Timezone</label><select id="ev-tz" name="timezone" x-model="form.timezone" class="select"><option>UTC</option><option>Europe/London</option><option>America/New_York</option><option>Asia/Karachi</option><option>Australia/Sydney</option></select><p class="help">Prototype stores the label only — no conversion is applied.</p></div>
                    <div><label class="label" for="ev-rem">Reminder</label><select id="ev-rem" name="reminder" x-model="form.reminder" class="select"><option value="15">15 minutes before</option><option value="30">30 minutes before</option><option value="60">1 hour before</option><option value="1440">1 day before</option></select><p class="help">In-app + simulated email reminder.</p></div>
                    <div class="sm:col-span-2"><label class="label" for="ev-loc">Location or meeting link</label><input id="ev-loc" name="location" x-model="form.location" class="input" placeholder="Phone, address, or https://zoom.example/… (demo)"></div>
                    <div class="sm:col-span-2"><p class="label">Invite attendees <span class="font-normal text-slate-500">(an Agent may invite a Team Lead or other permitted user)</span></p>
                        <div class="grid max-h-36 grid-cols-1 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                            @foreach ($invitable as $u)<label class="flex items-center gap-2 rounded px-1.5 py-1 text-sm hover:bg-slate-50"><input type="checkbox" class="check" name="attendees[]" value="{{ $u['id'] }}" x-model="form.attendees"> {{ $u['name'] }} <span class="text-xs text-slate-400">{{ $u['role'] }}</span></label>@endforeach
                        </div></div>
                    <div class="sm:col-span-2" x-show="conflicts.length" x-cloak><div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900"><p class="font-semibold">Possible double-booking</p><ul class="mt-1 list-disc pl-5"><template x-for="c in conflicts"><li x-text="c"></li></template></ul><p class="mt-1 text-xs">You can still save — conflicts are a warning.</p></div></div>
                    <div class="sm:col-span-2"><label class="label" for="ev-notes">Notes</label><textarea id="ev-notes" name="notes" x-model="form.notes" rows="3" class="textarea"></textarea></div>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Close</button><button class="btn btn-primary" x-text="mode === 'edit' ? 'Save changes' : 'Create event'"></button></div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
