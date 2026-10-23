<x-app-layout title="Agreements" :crumbs="[['Finance'], ['Agreements']]" subtitle="One customer can have many agreements with different services, prices and terms. Signed versions are locked.">
    <x-slot:actions>@perm('agreements.manage')<a href="{{ route('agreements.create') }}" class="btn btn-primary"><x-ico name="plus" class="size-4" /> New agreement</a>@endperm</x-slot:actions>
    <div x-data="tabs('list')">
    <div class="mb-4 flex gap-1 border-b border-slate-200" role="tablist"><button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === 'list' }" @click="set('list')">Agreements ({{ $rows->count() }})</button><button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === 'clauses' }" @click="set('clauses')">Terms &amp; clause library ({{ $clauses->count() }})</button></div>
    <div x-show="tab === 'list'" class="card" x-data="dataTable({ perPage: 10, sortKey: 'created', sortDir: 'desc', filters: { status: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-56 flex-1"><label for="ag-search" class="sr-only">Search agreements</label><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input id="ag-search" type="search" x-model="q" class="input pl-9" placeholder="Search customer or agreement ID…"></div>
            <select x-model="filters.status" class="select w-48" aria-label="Status"><option value="">All statuses</option>@foreach (config('statuses.agreement.values') as $k => $v)<option value="{{ $k }}">{{ $v[0] }}</option>@endforeach</select>
        </div>
        @if ($rows->isEmpty())<x-empty-state icon="document-text" title="No agreements yet" text="Build one from the Closed Won queue." />@else
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Agreement</th><th>Customer</th><th>Services</th><th class="text-right">Recurring</th><th class="text-right">One-time</th><th>Signatures</th><th>Status</th><th>Billing</th><th class="th-sort" @click="sort('created')">Created <span x-text="arrow('created')"></span></th></tr></thead>
            <tbody>
                @foreach ($rows as $a)
                    <tr data-row data-search="{{ strtolower($a->id.' '.$a->customer?->business_name) }}" data-f-status="{{ $a->status }}" data-s-created="{{ $a->created_at }}">
                        <td><a href="{{ route('agreements.show', $a->id) }}" class="font-medium hover:text-brand-700">{{ $a->id }}</a><p class="text-xs text-slate-500">v{{ $a->version }} · {{ $a->lead_id }}</p></td>
                        <td>{{ $a->customer?->business_name }}</td>
                        <td class="max-w-52 text-slate-600">{{ collect($a->lines)->pluck('name')->implode(', ') }}</td>
                        <td class="text-right">{{ $a->mrr ? money($a->mrr).'/mo' : '—' }}</td><td class="text-right">{{ $a->one_time_total ? money($a->one_time_total) : '—' }}</td>
                        <td class="text-slate-600">{{ collect($a->signers)->whereNotNull('signed_at')->count() }}/{{ count($a->signers) }}</td>
                        <td><x-status dim="agreement" :value="$a->status" /></td><td><x-status dim="billing" :value="$a->billing_status" /></td>
                        <td class="whitespace-nowrap text-slate-600">{{ fdate($a->created_at) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        <x-table-footer />
        @endif
    </div>

    <div x-show="tab === 'clauses'" x-cloak class="card">
        <div class="card-head"><h2 class="card-title">Reusable terms &amp; conditions</h2><span class="text-xs text-slate-500">Editing creates a new version. Existing agreements keep the version they were built with.</span></div>
        <ul class="divide-y divide-slate-100">
            @foreach ($clauses as $c)
                <li class="px-5 py-4"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0 flex-1"><p class="font-medium">{{ $c->title }} <span class="tag ml-1">{{ $c->scope }}</span> <span class="tag">v{{ $c->version }}</span></p><p class="mt-1 text-sm text-slate-600">{{ $c->body }}</p><p class="mt-1 text-xs text-slate-400">Used in {{ $usage[$c->id] ?? 0 }} agreement(s) · updated {{ fdate($c->updated_at) }}</p></div>
                    @perm('agreements.manage')<button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.open('clause', { action: '{{ route('clauses.update', $c->id) }}', title: '{{ addslashes($c->title) }}', body: @js($c->body), v: {{ $c->version }} })">Edit (new version)</button>@endperm</div></li>
            @endforeach
        </ul>
        <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">All clause wording is placeholder text for the prototype — not legal advice and not approved terms.</p>
    </div>
    </div>

    <x-slot:modals>
        <x-modal name="clause" title="Edit clause" description="Saved as a new version; earlier agreements keep their snapshot.">
            <form method="POST" :action="$store.modal.payload.action" x-data="{ touched: false }" @submit="touched = true; if (($refs.body.value || '').trim().length < 10) $event.preventDefault()">@csrf
                <p class="mb-3 text-sm"><strong x-text="$store.modal.payload.title"></strong> <span class="tag" x-text="'current v' + $store.modal.payload.v"></span></p>
                <x-field label="Clause text" name="body" :required="true"><textarea id="body" name="body" x-ref="body" rows="6" class="textarea" x-effect="$el.value = $store.modal.payload.body ?? ''"></textarea><p class="err" x-show="touched && ($refs.body.value || '').trim().length < 10" x-cloak>Enter at least 10 characters.</p></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Save new version</button></div></form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
