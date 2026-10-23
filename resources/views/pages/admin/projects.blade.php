<x-app-layout title="Projects & statuses" :crumbs="[['Administration'], ['Projects & statuses']]" subtitle="Projects, services, whether an audit is required before a proposal, auditor assignments and status configuration.">
    <div x-data="tabs('projects')">
        <div class="mb-4 flex gap-1 border-b border-slate-200" role="tablist">
            @foreach (['projects' => 'Projects & audit rules', 'services' => 'Services', 'statuses' => 'Status configuration'] as $k => $l)<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $k }}' }" @click="set('{{ $k }}')">{{ $l }}</button>@endforeach
        </div>

        <div x-show="tab === 'projects'" class="space-y-4">
            @foreach ($projects as $p)
                <form method="POST" action="{{ route('projects.update', $p->id) }}" class="card" x-data="{ policy: '{{ $p->audit_policy }}' }">
                    @csrf
                    <div class="card-head"><div><h2 class="card-title">{{ $p->name }} <span class="tag ml-1">{{ $p->code }}</span></h2><p class="text-xs text-slate-500">{{ $p->description }}</p></div>
                        <select name="status" class="select h-8 w-32 text-[13px]" aria-label="Project status"><option value="active" @selected($p->status === 'active')>Active</option><option value="disabled" @selected($p->status === 'disabled')>Disabled</option></select></div>
                    <div class="grid gap-4 p-5 md:grid-cols-3">
                        <x-field label="Audit before proposal" :name="'audit_'.$p->id" help="Configurable per project / service."><select id="audit_{{ $p->id }}" name="audit_policy" x-model="policy" class="select"><option value="required">Required</option><option value="optional">Optional</option><option value="not_applicable">Not applicable</option></select></x-field>
                        <div x-show="policy !== 'not_applicable'"><p class="label">Assigned auditor(s)</p><div class="space-y-1.5">@foreach ($auditors as $a)<label class="flex items-center gap-2 text-sm"><input type="checkbox" class="check" name="auditor_ids[]" value="{{ $a->id }}" @checked(in_array($a->id, $p->auditor_ids))> {{ $a->name }} <span class="text-xs text-slate-500">· {{ $a->title }}</span></label>@endforeach</div></div>
                        <x-field label="Lead intake form" :name="'form_'.$p->id" help="Published version is used for new leads."><select id="form_{{ $p->id }}" name="form_id" class="select">@foreach ($forms as $f)<option value="{{ $f->id }}" @selected($f->id === $p->form_id)>{{ $f->name }}</option>@endforeach</select></x-field>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3"><p class="text-xs text-slate-500">Status set: {{ $p->status_set }} · Services: {{ collect($p->service_ids)->count() }}</p><button class="btn btn-primary btn-sm">Save project</button></div>
                </form>
            @endforeach
        </div>

        <div x-show="tab === 'services'" x-cloak class="card">
            <div class="card-head"><h2 class="card-title">Services catalogue</h2><span class="tag tag-synthetic">Sample prices are synthetic</span></div>
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Service</th><th>Project</th><th>Billing model</th><th class="text-right">Sample price</th><th>Default clauses</th></tr></thead><tbody>
                @foreach ($services as $s)<tr><td class="font-medium">{{ $s->name }}<p class="text-xs font-normal text-slate-500">{{ $s->id }}</p></td><td>{{ $projects->firstWhere('id', $s->project_id)?->name }}</td><td><span class="chip {{ $s->billing === 'recurring' ? 'chip-blue' : 'chip-slate' }}">{{ $s->billing === 'recurring' ? 'Recurring (MRR/MRC)' : 'One-time' }}</span></td><td class="text-right">{{ money($s->sample_price) }}</td><td class="text-xs text-slate-500">{{ count($s->clause_ids) }} reusable clauses</td></tr>@endforeach
            </tbody></table></div>
        </div>

        <div x-show="tab === 'statuses'" x-cloak class="grid gap-5 lg:grid-cols-2">
            @foreach ($dims as $dim => $info)
                <div class="card" x-data="{ adding: false, label: '', touched: false }">
                    <div class="card-head"><h2 class="card-title">{{ $info['label'] }}</h2><button type="button" class="btn btn-secondary btn-xs" @click="adding = !adding"><x-ico name="plus" class="size-3.5" /> Add status</button></div>
                    <ul class="divide-y divide-slate-100 text-sm">
                        @foreach ($info['values'] as $key => $def)
                            @php($off = in_array($key, $info['disabled'], true))
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <div class="flex flex-wrap items-center gap-2"><span class="chip chip-{{ $def[1] }} {{ $off ? 'opacity-50' : '' }}">{{ $def[0] }}</span>
                                    @if ($def[2]['reason'] ?? false)<span class="tag">reason required</span>@endif @if ($def[2]['terminal'] ?? false)<span class="tag">terminal</span>@endif @if ($def[2]['custom'] ?? false)<span class="tag">custom</span>@endif</div>
                                <form method="POST" action="{{ route('statuses.update') }}">@csrf <input type="hidden" name="dimension" value="{{ $dim }}"><input type="hidden" name="op" value="toggle"><input type="hidden" name="key" value="{{ $key }}">
                                    <button class="btn btn-ghost btn-xs">{{ $off ? 'Activate' : 'Deactivate' }}</button></form>
                            </li>
                        @endforeach
                    </ul>
                    <form x-show="adding" x-cloak method="POST" action="{{ route('statuses.update') }}" class="grid gap-3 border-t border-slate-100 bg-slate-50 p-4 sm:grid-cols-[1fr_120px_auto]" @submit="touched = true; if (!label.trim()) $event.preventDefault()">
                        @csrf <input type="hidden" name="dimension" value="{{ $dim }}">
                        <div><label class="sr-only" for="sl_{{ $dim }}">New status name</label><input id="sl_{{ $dim }}" name="label" x-model="label" class="input" :class="{ 'input-error': touched && !label.trim() }" placeholder="Status name"><p class="err" x-show="touched && !label.trim()" x-cloak>Name required.</p></div>
                        <select name="color" class="select" aria-label="Colour">@foreach ($colors as $c)<option>{{ $c }}</option>@endforeach</select>
                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="reason" value="1" class="check"> Needs reason</label>
                        <div class="sm:col-span-3 flex justify-end"><button class="btn btn-primary btn-sm">Add</button></div>
                    </form>
                    <p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">Deactivating hides a status from new selections; existing records keep it.</p>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
