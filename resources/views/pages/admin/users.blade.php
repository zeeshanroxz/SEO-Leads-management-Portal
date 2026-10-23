@php
    $matrix = [
        ['Users, roles, global settings', 'Full', 'No', 'No', 'No', 'No', 'No'],
        ['View sales pipeline', 'All', 'Finance scope', 'Assigned audits', 'Team', 'Own / team', 'Own'],
        ['Create lead', 'Yes', 'Optional override', 'No', 'Yes', 'Yes', 'Yes'],
        ['Accept / reject leads', 'Yes', 'Optional override', 'No', 'Yes', 'Own lead / authorised', 'No'],
        ['Edit accepted lead details', 'Override', 'Finance fields', 'Audit fields', 'Permitted sales fields', 'Permitted sales fields', 'No'],
        ['Custom forms / required fields', 'Full', 'No', 'No', 'No', 'No', 'No'],
        ['Audit / quote authoring', 'Override', 'No', 'Assigned requests', 'Request / view', 'Request / view', 'Request / view'],
        ['View / edit agreement terms', 'Full', 'Full', 'No', 'No', 'No', 'No'],
        ['Square and client billing', 'Full', 'Full', 'No', 'No', 'No', 'No'],
        ['Commission setup', 'Full', 'Yes (incl. manager)', 'No', 'Add team commission', 'Optional', 'No'],
        ['View own commission due / paid', 'Yes', 'If applicable', 'No', 'Yes', 'Yes', 'Yes'],
        ['Approve / pay commissions', 'Full', 'Full', 'No', 'No by default', 'No', 'No'],
        ['MRR / MRC after full signature', 'Full', 'Full', 'No', 'All signed customers', 'No by default', 'No by default'],
        ['Visibility override per opportunity', 'Full', 'No', 'No', 'No', 'No', 'No'],
    ];
    $tone = fn ($v) => str_starts_with($v, 'No') ? 'chip-slate' : (in_array($v, ['Full', 'All', 'Yes']) ? 'chip-green' : 'chip-blue');
@endphp
<x-app-layout title="Users & teams" :crumbs="[['Administration'], ['Users & teams']]" subtitle="Accounts, hierarchy (Admin → Manager → Team Lead → Agent) and the default permission baseline.">
    <x-slot:actions><button type="button" class="btn btn-primary" @click="$store.modal.open('add-user')"><x-ico name="user-plus" class="size-4" /> Add user</button></x-slot:actions>

    <div x-data="tabs('users')">
        <div class="mb-4 flex gap-1 border-b border-slate-200" role="tablist">
            @foreach (['users' => 'Users', 'teams' => 'Teams & hierarchy', 'permissions' => 'Role permissions'] as $k => $l)<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $k }}' }" @click="set('{{ $k }}')">{{ $l }}</button>@endforeach
        </div>

        <div x-show="tab === 'users'" class="card" x-data="dataTable({ perPage: 10, sortKey: 'name', sortDir: 'asc', filters: { role: '', status: '' } })">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
                <div class="relative min-w-56 flex-1"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input type="search" x-model="q" class="input pl-9" placeholder="Search name or email…" aria-label="Search users"></div>
                <select x-model="filters.role" class="select w-44" aria-label="Role"><option value="">All roles</option>@foreach ($roles as $k => $r)<option value="{{ $k }}">{{ $r['label'] }}</option>@endforeach</select>
                <select x-model="filters.status" class="select w-36" aria-label="Status"><option value="">Any status</option><option value="active">Active</option><option value="disabled">Disabled</option></select>
            </div>
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th class="th-sort" @click="sort('name')">User <span x-text="arrow('name')"></span></th><th>Role</th><th>Team</th><th>Reports to</th><th>MFA</th><th>Status</th><th>Last login</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr data-row data-search="{{ strtolower($u->name.' '.$u->email) }}" data-f-role="{{ $u->role }}" data-f-status="{{ $u->status }}" data-s-name="{{ strtolower($u->name) }}">
                            <td><div class="flex items-center gap-3"><x-avatar :name="$u->name" /><div><p class="font-medium {{ $u->status === 'disabled' ? 'text-slate-400 line-through' : '' }}">{{ $u->name }}</p><p class="text-xs text-slate-500">{{ $u->email }}</p></div></div></td>
                            <td>{{ $roles[$u->role]['label'] }}@if (! empty($u->permission_sets) && count($u->permission_sets) > 1)<p class="text-xs text-slate-500">+ {{ $u->permission_sets[1] }} permissions</p>@endif</td>
                            <td class="text-slate-600">{{ $dir->team($u->team_id)?->name ?? '—' }}</td>
                            <td class="text-slate-600">{{ $dir->user($u->team_lead_id ?? $u->manager_id)?->name ?? '—' }}</td>
                            <td>@if ($u->two_factor)<span class="chip chip-green">On</span>@else<span class="chip chip-slate">Off</span>@endif</td>
                            <td><x-status dim="misc" :value="$u->status" /></td>
                            <td class="whitespace-nowrap text-slate-500">{{ $u->last_login ? ago($u->last_login) : 'Never' }}</td>
                            <td class="text-right"><div class="flex justify-end gap-1.5">
                                <button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.open('role', { action: '{{ route('admin.users.role', $u->id) }}', name: '{{ addslashes($u->name) }}', role: '{{ $u->role }}' })">Change role</button>
                                <button type="button" class="btn btn-xs {{ $u->status === 'active' ? 'btn-secondary text-red-700' : 'btn-secondary' }}" @click="$store.modal.confirm({ title: '{{ $u->status === 'active' ? 'Disable' : 'Re-enable' }} {{ addslashes($u->name) }}?', body: '{{ $u->status === 'active' ? 'The account cannot sign in. Records, history and commissions are kept — nothing is deleted.' : 'The account can sign in again.' }}', action: '{{ route('admin.users.toggle', $u->id) }}', confirm: '{{ $u->status === 'active' ? 'Disable user' : 'Re-enable user' }}', danger: {{ $u->status === 'active' ? 'true' : 'false' }}, reason: true })">{{ $u->status === 'active' ? 'Disable' : 'Enable' }}</button>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <x-table-footer />
        </div>

        <div x-show="tab === 'teams'" x-cloak class="grid gap-5 lg:grid-cols-2">
            @foreach ($teams as $t)
                <div class="card">
                    <div class="card-head"><h3 class="card-title">{{ $t->name }}</h3><span class="tag">{{ count($t->agent_ids) }} agents</span></div>
                    <div class="space-y-3 p-5 text-sm">
                        <div class="flex items-center gap-3"><x-avatar :name="$dir->userName($t->manager_id)" /><div><p class="font-medium">{{ $dir->userName($t->manager_id) }}</p><p class="text-xs text-slate-500">Manager</p></div></div>
                        <div class="ml-5 flex items-center gap-3 border-l-2 border-slate-200 pl-5"><x-avatar :name="$dir->userName($t->team_lead_id)" /><div><p class="font-medium">{{ $dir->userName($t->team_lead_id) }}</p><p class="text-xs text-slate-500">Team Lead / closer</p></div></div>
                        @foreach ($t->agent_ids as $aid)
                            @php($au = $dir->user($aid))
                            <div class="ml-12 flex items-center gap-3 border-l-2 border-slate-200 pl-5"><x-avatar :name="$au->name" size="xs" /><div><p class="{{ $au->status === 'disabled' ? 'text-slate-400 line-through' : 'font-medium' }}">{{ $au->name }}</p><p class="text-xs text-slate-500">Agent{{ $au->status === 'disabled' ? ' · disabled' : '' }}</p></div></div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            <div class="card card-pad lg:col-span-2 text-sm text-slate-600"><p class="font-medium text-slate-900">Beyond the sales line</p><p class="mt-1">Finance and Auditor of Project sit outside the sales hierarchy: Finance is a restricted functional role (Finance Manager is an extra permission set), and Auditors only see projects and requests assigned to them.</p></div>
        </div>

        <div x-show="tab === 'permissions'" x-cloak class="card">
            <div class="card-head"><h3 class="card-title">Default permission baseline</h3><span class="tag">Scoped = own / supervised / assigned / granted</span></div>
            <div class="overflow-x-auto"><table class="tbl tbl-compact">
                <thead><tr><th>Capability</th>@foreach ($roles as $r)<th>{{ $r['label'] }}</th>@endforeach</tr></thead>
                <tbody>@foreach ($matrix as $row)<tr><td class="font-medium">{{ $row[0] }}</td>@foreach (array_slice($row, 1) as $v)<td><span class="chip {{ $tone($v) }}">{{ $v }}</span></td>@endforeach</tr>@endforeach</tbody>
            </table></div>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">This is a baseline, not a replacement for configurable rules. Record-level exceptions live under <a class="text-brand-700 underline" href="{{ route('admin.visibility') }}">Visibility overrides</a>. In production sensitive fields are omitted from API responses, not just hidden in the UI.</p>
        </div>
    </div>

    <x-slot:modals>
        <x-modal name="add-user" title="Add user" description="Creates a demo record only — no invitation email is sent.">
            <form method="POST" action="{{ route('admin.users.store') }}" x-data="{ name: '', email: '', touched: false }" @submit="touched = true; if (!name.trim() || !/^\S+@\S+\.\S+$/.test(email)) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Full name" name="name" :required="true"><input id="name" name="name" x-model="name" class="input" :class="{ 'input-error': touched && !name.trim() }"><p class="err" x-show="touched && !name.trim()" x-cloak>Name is required.</p></x-field>
                    <x-field label="Work email" name="email" :required="true"><input id="email" name="email" x-model="email" class="input" :class="{ 'input-error': touched && !/^\S+@\S+\.\S+$/.test(email) }"><p class="err" x-show="touched && !/^\S+@\S+\.\S+$/.test(email)" x-cloak>Enter a valid email.</p></x-field>
                    <x-field label="Role" name="role" :required="true"><select id="role" name="role" class="select">@foreach ($roles as $k => $r)<option value="{{ $k }}" @selected($k === 'agent')>{{ $r['label'] }}</option>@endforeach</select></x-field>
                    <x-field label="Team" name="team_id"><select id="team_id" name="team_id" class="select"><option value="">No team</option>@foreach ($teams as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Add user</button></div>
            </form>
        </x-modal>
        <x-modal name="role" title="Change role" description="Past commission records keep the role the person had at the time of sale.">
            <form method="POST" :action="$store.modal.payload.action" x-data="{ reason: '', touched: false }" @submit="touched = true; if (reason.trim().length < 5) $event.preventDefault()">
                @csrf
                <p class="mb-3 text-sm text-slate-600">Changing role for <strong x-text="$store.modal.payload.name"></strong>.</p>
                <x-field label="New role" name="role" :required="true"><select id="role" name="role" class="select" x-effect="$el.value = $store.modal.payload.role">@foreach ($roles as $k => $r)<option value="{{ $k }}">{{ $r['label'] }}</option>@endforeach</select></x-field>
                <x-field label="Reason (audit trail)" name="reason" :required="true" class="mt-3"><textarea id="reason" name="reason" x-model="reason" rows="3" class="textarea" :class="{ 'input-error': touched && reason.trim().length < 5 }"></textarea><p class="err" x-show="touched && reason.trim().length < 5" x-cloak>Please give a reason (min. 5 characters).</p></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Change role</button></div>
            </form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
