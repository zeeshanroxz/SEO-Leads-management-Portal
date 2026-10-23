<x-app-layout title="Visibility overrides" :crumbs="[['Administration'], ['Visibility overrides']]" subtitle="Grant or restrict access to specific opportunities and field groups. Every override has an audit reason and is reversible.">
    <x-slot:actions><button type="button" class="btn btn-primary" @click="$store.modal.open('override')"><x-ico name="plus" class="size-4" /> New override</button></x-slot:actions>

    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="card card-pad text-sm"><p class="flex items-center gap-2 font-semibold"><span class="chip chip-red">Restricted</span></p><p class="mt-2 text-slate-600">Hides a field group on one record from roles that would normally see it — e.g. upsell details from the original Agent, Team Lead and Manager.</p></div>
        <div class="card card-pad text-sm"><p class="flex items-center gap-2 font-semibold"><span class="chip chip-green">Granted</span></p><p class="mt-2 text-slate-600">Exposes a field group that is normally outside a role's scope — e.g. internal call notes for Finance on one deal.</p></div>
        <div class="card card-pad text-sm"><p class="flex items-center gap-2 font-semibold"><x-ico name="shield-check" class="size-5 text-brand-600" /> Server-side in production</p><p class="mt-2 text-slate-600">Phase 2 omits restricted fields from API responses, not just from the screen.</p></div>
    </div>

    <div class="card" x-data="dataTable({ perPage: 10, filters: { status: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4"><div class="relative min-w-56 flex-1"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input type="search" x-model="q" class="input pl-9" placeholder="Search record, group, reason…" aria-label="Search overrides"></div>
            <select x-model="filters.status" class="select w-40" aria-label="Status"><option value="">Any status</option><option value="active">Active</option><option value="revoked">Revoked</option></select></div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Record</th><th>Field group</th><th>Effect</th><th>Applies to</th><th>Reason</th><th>Set by</th><th>Expires</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @foreach ($overrides as $o)
                    <tr data-row data-search="{{ strtolower($o->lead_id.' '.$o->field_group.' '.$o->reason.' '.$o->applies_to) }}" data-f-status="{{ $o->status }}">
                        <td><a href="{{ route('leads.show', $o->lead_id) }}" class="font-medium hover:text-brand-700">{{ $o->lead_id }}</a></td>
                        <td>{{ $o->field_group }}</td>
                        <td><x-status dim="misc" :value="$o->status === 'active' ? $o->effect : 'revoked'" /></td>
                        <td class="max-w-48 text-slate-600">{{ $o->applies_to }}</td>
                        <td class="max-w-xs text-slate-600">{{ $o->reason }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ $o->by_user?->name }}<p class="text-xs text-slate-400">{{ ago($o->at) }}</p></td>
                        <td class="whitespace-nowrap text-slate-600">{{ $o->expires ? fdate($o->expires) : 'No expiry' }}</td>
                        <td class="text-right">@if ($o->status === 'active')<button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.confirm({ title: 'Revoke this override?', body: 'Default role rules apply to {{ $o->lead_id }} again.', action: '{{ route('admin.visibility.revoke', $o->id) }}', confirm: 'Revoke', danger: true, reason: true })">Revoke</button>@else<span class="text-xs text-slate-400">Revoked</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        <x-table-footer />
    </div>

    <x-slot:modals>
        <x-modal name="override" title="New visibility override" description="Record-level exception to the default role rules.">
            <form method="POST" action="{{ route('admin.visibility.store') }}" x-data="{ reason: '', touched: false }" @submit="touched = true; if (reason.trim().length < 10) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Opportunity" name="lead_id" :required="true" class="sm:col-span-2"><select id="lead_id" name="lead_id" class="select">@foreach ($leads as $l)<option value="{{ $l->id }}">{{ $l->id }} · {{ $l->customer->business_name }}</option>@endforeach</select></x-field>
                    <x-field label="Field group" name="group_key"><select id="group_key" name="group_key" class="select">@foreach ($groups as $k => $g)<option value="{{ $k }}">{{ $g }}</option>@endforeach</select></x-field>
                    <x-field label="Effect" name="effect"><select id="effect" name="effect" class="select"><option value="restricted">Restrict</option><option value="granted">Grant access</option></select></x-field>
                    <x-field label="Applies to" name="applies_to" class="sm:col-span-2"><input id="applies_to" name="applies_to" class="input" value="Original Agent, Team Lead and Manager"></x-field>
                    <x-field label="Expires (optional)" name="expires"><input id="expires" name="expires" type="date" class="input"></x-field>
                    <x-field label="Audit reason" name="reason" :required="true" class="sm:col-span-2"><textarea id="reason" name="reason" x-model="reason" rows="3" class="textarea" :class="{ 'input-error': touched && reason.trim().length < 10 }"></textarea><p class="err" x-show="touched && reason.trim().length < 10" x-cloak>Reason must be at least 10 characters.</p></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Save override</button></div>
            </form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
