@php
    $dir = app(\App\Mock\Repositories\DirectoryRepository::class);
    $tabs = ['overview' => 'Overview', 'contacts' => 'Contacts ('.count($customer->contacts).')', 'opps' => 'Opportunities ('.$opps->count().')'];
    if (demo()::can('agreements.view')) { $tabs['agreements'] = 'Agreements ('.$agreements->count().')'; }
    $tabs['services'] = 'Services';
    $tabs['history'] = 'History';
@endphp
<x-app-layout :title="$customer->business_name" :crumbs="[['Customers', route('customers.index')], [$customer->business_name]]">
    <x-slot:actions>
        <button type="button" class="btn btn-secondary" @click="$store.modal.open('contact')"><x-ico name="user-plus" class="size-4" /> Add contact</button>
        @perm('leads.create')<a href="{{ route('leads.create') }}" class="btn btn-primary"><x-ico name="plus" class="size-4" /> New opportunity</a>@endperm
    </x-slot:actions>

    <div class="mb-5 card card-pad flex flex-wrap items-center gap-4">
        <span class="flex size-12 items-center justify-center rounded-xl bg-brand-50 text-lg font-semibold text-brand-700">{{ initials($customer->business_name) }}</span>
        <div class="min-w-0 flex-1"><p class="text-lg font-semibold">{{ $customer->business_name }}</p><p class="text-sm text-slate-500">{{ $customer->industry }} · {{ $customer->business_address }}</p></div>
        <div class="flex gap-6 text-sm"><div><p class="text-xs text-slate-500">Contacts</p><p class="font-semibold">{{ count($customer->contacts) }}</p></div><div><p class="text-xs text-slate-500">Opportunities</p><p class="font-semibold">{{ $opps->count() }}</p></div>@perm('agreements.view')<div><p class="text-xs text-slate-500">Agreements</p><p class="font-semibold">{{ $agreements->count() }}</p></div>@endperm</div>
    </div>

    <div x-data="tabs('overview')">
        <div class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
            @foreach ($tabs as $k => $l)<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $k }}' }" @click="set('{{ $k }}')">{{ $l }}</button>@endforeach
        </div>

        <div x-show="tab === 'overview'" class="grid gap-5 lg:grid-cols-2">
            <div class="card"><div class="card-head"><h3 class="card-title">Business information</h3></div>
                <dl class="dl p-5"><div><dt>Business email</dt><dd>{{ $customer->business_email }}</dd></div><div><dt>Business phone</dt><dd>{{ $customer->business_phone }}</dd></div><div class="sm:col-span-2"><dt>Address</dt><dd>{{ $customer->business_address }}</dd></div><div><dt>Website</dt><dd>{{ $customer->website }}</dd></div><div><dt>Customer since</dt><dd>{{ fdate($customer->created_at) }}</dd></div></dl></div>
            <div class="card"><div class="card-head"><h3 class="card-title">Primary contact</h3></div>
                @php($p = collect($customer->contacts)->firstWhere('primary', true))
                <dl class="dl p-5"><div><dt>Name</dt><dd>{{ $p?->name }}</dd></div><div><dt>Designation</dt><dd>{{ $p?->designation }}</dd></div><div><dt>Email</dt><dd>{{ $p?->email }}</dd></div><div><dt>Phone</dt><dd>{{ $p?->phone }}</dd></div></dl></div>
            @if ($square)
                <div class="card lg:col-span-2"><div class="card-head"><h3 class="card-title">Square mapping</h3><x-demo-tag label="Sandbox · simulated" /></div>
                    <dl class="dl p-5"><div><dt>Square customer</dt><dd>{{ $square->square_customer_id ?? 'Not mapped' }}</dd></div><div><dt>Card on file</dt><dd>{{ $square->card_on_file ?? '—' }}</dd></div></dl></div>
            @endif
        </div>

        <div x-show="tab === 'contacts'" x-cloak class="card"><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Name</th><th>Designation</th><th>Email</th><th>Phone</th><th>Notes</th></tr></thead><tbody>
            @foreach ($customer->contacts as $k)<tr><td class="font-medium">{{ $k->name }} @if ($k->primary)<span class="chip chip-blue ml-1">Primary</span>@endif</td><td>{{ $k->designation }}</td><td>{{ $k->email }}</td><td>{{ $k->phone }}</td><td class="text-slate-500">{{ $k->notes ?? '—' }}</td></tr>@endforeach
        </tbody></table></div></div>

        <div x-show="tab === 'opps'" x-cloak class="card"><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Opportunity</th><th>Project</th><th>Review</th><th>Sales status</th><th>Owner</th><th>Created</th></tr></thead><tbody>
            @foreach ($opps as $l)<tr><td><a href="{{ route('leads.show', $l->id) }}" class="font-medium hover:text-brand-700">{{ $l->id }}</a> @if ($l->parent_id)<span class="tag">Upsell of {{ $l->parent_id }}</span>@endif</td><td>{{ $l->project->name }}</td><td><x-status dim="lead_review" :value="$l->review_status" /></td><td>@if ($l->review_status === 'accepted')<x-status dim="sales" :value="$l->sales_status" />@else — @endif</td><td>{{ $l->owner?->name }}</td><td class="text-slate-500">{{ fdate($l->created_at) }}</td></tr>@endforeach
        </tbody></table></div><p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Upsells are separate opportunities (and agreements) — earlier deals are never overwritten.</p></div>

        @if (isset($tabs['agreements']))
        <div x-show="tab === 'agreements'" x-cloak class="card"><div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Agreement</th><th>Status</th><th>Services</th><th class="text-right">Recurring</th><th class="text-right">One-time</th><th>Term</th></tr></thead><tbody>
            @forelse ($agreements as $a)<tr><td><a href="{{ route('agreements.show', $a->id) }}" class="font-medium hover:text-brand-700">{{ $a->id }}</a></td><td><x-status dim="agreement" :value="$a->status" /></td><td>{{ collect($a->lines)->pluck('name')->implode(', ') }}</td><td class="text-right">{{ money($a->mrr) }}/mo</td><td class="text-right">{{ money($a->one_time_total) }}</td><td>{{ $a->term_months }} mo</td></tr>@empty<tr><td colspan="6" class="py-6 text-center text-slate-500">No agreements.</td></tr>@endforelse
        </tbody></table></div></div>
        @endif

        <div x-show="tab === 'services'" x-cloak class="card">
            @if ($services->isEmpty())<x-empty-state icon="rectangle-stack" title="No services purchased yet" text="Services appear here once an opportunity is Closed Won." />
            @else<ul class="divide-y divide-slate-100 text-sm">@foreach ($services as $s)<li class="flex items-center justify-between px-5 py-3"><span class="font-medium">{{ $s['name'] }}</span><span class="text-slate-500">via {{ $s['lead'] }} · since {{ fdate($s['since']) }}</span></li>@endforeach</ul>@endif
        </div>

        <div x-show="tab === 'history'" x-cloak class="card">
            <ol class="space-y-4 p-5 text-sm">@forelse ($history as $x)<li class="flex gap-3"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500"></span><div><p><span class="font-medium">{{ $x['h']->field }}</span>: {{ $x['h']->from }} → {{ $x['h']->to }} <span class="tag">{{ $x['lead'] }}</span></p><p class="text-xs text-slate-500">{{ $dir->userName($x['h']->by) }} · {{ fdt($x['h']->at) }}</p></div></li>@empty<li class="text-slate-500">No history yet.</li>@endforelse</ol>
        </div>
    </div>

    <x-slot:modals>
        <x-modal name="contact" title="Add contact" description="Contacts belong to the business and can be marked primary later.">
            <form method="POST" action="{{ route('customers.contacts.store', $customer->id) }}" x-data="{ name: '', touched: false }" @submit="touched = true; if (!name.trim()) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Name" name="name" :required="true"><input id="name" name="name" x-model="name" class="input" :class="{ 'input-error': touched && !name.trim() }"><p class="err" x-show="touched && !name.trim()" x-cloak>Name is required.</p></x-field>
                    <x-field label="Designation" name="designation"><input id="designation" name="designation" class="input"></x-field>
                    <x-field label="Email" name="email"><input id="email" name="email" type="email" class="input"></x-field>
                    <x-field label="Phone" name="phone"><input id="phone" name="phone" class="input"></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Add contact</button></div>
            </form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
