<x-app-layout title="Billing & Square" :crumbs="[['Finance'], ['Billing & Square']]" subtitle="Customer mapping, subscriptions, payments, webhook events and reconciliation. Every Square object here is simulated.">
    <x-slot:actions>
        <span class="chip {{ $square->environment === 'live' ? 'chip-red' : 'chip-amber' }}">{{ ucfirst($square->environment) }} mode (mock)</span>
        @perm('admin.integrations')<a href="{{ route('integrations.square') }}" class="btn btn-secondary"><x-ico name="cog-6-tooth" class="size-4" /> Square settings</a>@endperm
    </x-slot:actions>
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4 text-sm text-fuchsia-900"><x-ico name="beaker" class="mt-0.5 size-5" /><p><strong>Simulation.</strong> No Square API call is made and no customer is charged. Customers, subscriptions, payments and webhook events below are fake sample records. Signed contract value is never treated as cash received.</p></div>

    <div x-data="tabs('customers')">
        <div class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
            @foreach (['customers' => 'Customer mapping', 'subscriptions' => 'Subscriptions & billing', 'payments' => 'Payments', 'webhooks' => 'Webhook events', 'log' => 'Sync log', 'reconciliation' => 'Reconciliation'] as $k => $l)<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $k }}' }" @click="set('{{ $k }}')">{{ $l }}@if ($k === 'reconciliation' && $recon->where('resolved', false)->count())<span class="ml-1.5 rounded-full bg-red-100 px-1.5 text-[11px] text-red-700">{{ $recon->where('resolved', false)->count() }}</span>@endif @if ($k === 'webhooks' && $webhooks->where('status', 'failed')->count())<span class="ml-1.5 rounded-full bg-red-100 px-1.5 text-[11px] text-red-700">{{ $webhooks->where('status', 'failed')->count() }}</span>@endif</button>@endforeach
        </div>

        {{-- CUSTOMERS --}}
        <div x-show="tab === 'customers'" class="card">
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Portal customer</th><th>Square customer ID</th><th>Card on file</th><th>Linked</th><th>Mapping</th><th class="text-right">Action</th></tr></thead><tbody>
                @foreach ($customers as $c)<tr><td><a href="{{ route('customers.show', $c->customer_id) }}" class="font-medium hover:text-brand-700">{{ $c->customer?->business_name }}</a><p class="text-xs text-slate-500">{{ $c->email }}</p></td><td class="font-mono text-xs">{{ $c->square_customer_id ?? '—' }}</td><td>{{ $c->card_on_file ?? '—' }}</td><td class="text-slate-500">{{ $c->linked_at ? fdate($c->linked_at) : '—' }}</td>
                    <td>@if ($c->status === 'linked')<span class="chip chip-green">Mapped</span>@else<span class="chip chip-amber">Not mapped</span>@endif</td>
                    <td class="text-right">@if ($c->status !== 'linked')<div class="flex justify-end gap-1.5"><form method="POST" action="{{ route('billing.map', $c->id) }}">@csrf<input type="hidden" name="mode" value="create"><button class="btn btn-secondary btn-xs">Create in Square</button></form><form method="POST" action="{{ route('billing.map', $c->id) }}">@csrf<input type="hidden" name="mode" value="link"><button class="btn btn-secondary btn-xs">Link existing</button></form></div>@else<span class="text-xs text-slate-400">Duplicate mapping blocked</span>@endif</td></tr>@endforeach
            </tbody></table></div>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">One portal customer maps to one Square customer by default; a second mapping needs a documented business reason.</p>
        </div>

        {{-- SUBSCRIPTIONS --}}
        <div x-show="tab === 'subscriptions'" x-cloak class="card">
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Customer</th><th>Plan</th><th class="text-right">Amount</th><th>Cadence</th><th>Billing status</th><th>Next charge</th><th>Authorisation</th><th class="text-right">Action</th></tr></thead><tbody>
                @foreach ($subs as $s)<tr><td class="font-medium">{{ $s->customer?->business_name }}<p class="text-xs font-normal text-slate-500">{{ $s->agreement_id }}</p></td><td>{{ $s->plan }}</td><td class="text-right">{{ money($s->amount) }}</td><td>{{ $s->cadence }}</td><td><x-status dim="billing" :value="$s->status" /></td><td class="text-slate-600">{{ $s->next_charge ? fdate($s->next_charge) : '—' }}</td><td class="max-w-48 text-xs text-slate-500">{{ $s->authorisation }}</td>
                    <td class="text-right">@if ($s->status === 'not_set_up')<form method="POST" action="{{ route('billing.subscription', $s->id) }}">@csrf<button class="btn btn-primary btn-xs" @disabled(! $s->can_activate) title="{{ $s->can_activate ? '' : $s->block_reason }}">Set up (sandbox)</button></form>@if (! $s->can_activate)<p class="mt-1 text-[11px] text-red-600">{{ $s->block_reason }}</p>@endif @else<span class="text-xs text-slate-400">—</span>@endif</td></tr>@endforeach
            </tbody></table></div>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">A customer is never charged without a signed agreement carrying billing authorisation and a mapped Square customer.</p>
            <div class="border-t border-slate-200"><div class="card-head"><h3 class="card-title">One-time charges</h3><span class="text-xs text-slate-500">From agreement one-time lines</span></div>
                <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Customer</th><th>Agreement</th><th class="text-right">Amount</th><th>Payment</th></tr></thead><tbody>
                    @foreach ($payments->where('type', 'one_time') as $p)<tr><td>{{ $p->customer?->business_name }}</td><td>{{ $p->agreement_id }}</td><td class="text-right">{{ money($p->amount) }}</td><td><span class="chip {{ ['completed' => 'chip-green', 'failed' => 'chip-red', 'pending' => 'chip-amber', 'refunded' => 'chip-stone'][$p->status] }}">{{ ucfirst($p->status) }}</span> <span class="text-xs text-slate-500">{{ $p->note }}</span></td></tr>@endforeach
                </tbody></table></div></div>
        </div>

        {{-- PAYMENTS --}}
        <div x-show="tab === 'payments'" x-cloak class="card" x-data="dataTable({ perPage: 8, sortKey: 'date', sortDir: 'desc', filters: { status: '' } })">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4"><div class="relative min-w-56 flex-1"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input type="search" x-model="q" class="input pl-9" placeholder="Search customer or payment ID…" aria-label="Search payments"></div>
                <select x-model="filters.status" class="select w-40" aria-label="Status"><option value="">All statuses</option><option value="completed">Completed</option><option value="pending">Pending</option><option value="failed">Failed</option><option value="refunded">Refunded</option></select></div>
            <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th class="th-sort" @click="sort('date')">Date <span x-text="arrow('date')"></span></th><th>Payment</th><th>Customer</th><th>Type</th><th>Method</th><th>Status</th><th class="text-right">Amount</th></tr></thead><tbody>
                @foreach ($payments as $p)<tr data-row data-search="{{ strtolower($p->id.' '.$p->customer?->business_name.' '.$p->square_payment_id) }}" data-f-status="{{ $p->status }}" data-s-date="{{ $p->date }}"><td class="whitespace-nowrap text-slate-600">{{ fdate($p->date) }}</td><td><span class="font-medium">{{ $p->id }}</span><p class="font-mono text-[11px] text-slate-400">{{ $p->square_payment_id }}</p></td><td>{{ $p->customer?->business_name }}</td><td class="capitalize">{{ str_replace('_', ' ', $p->type) }}</td><td class="text-slate-600">{{ $p->method }}</td><td><span class="chip {{ ['completed' => 'chip-green', 'failed' => 'chip-red', 'pending' => 'chip-amber', 'refunded' => 'chip-stone'][$p->status] }}">{{ ucfirst($p->status) }}</span>@if ($p->note)<p class="mt-0.5 text-[11px] text-slate-500">{{ $p->note }}</p>@endif</td><td class="text-right font-medium {{ $p->amount < 0 ? 'text-red-600' : '' }}">{{ money($p->amount) }}</td></tr>@endforeach
            </tbody></table></div><x-table-footer />
        </div>

        {{-- WEBHOOKS --}}
        <div x-show="tab === 'webhooks'" x-cloak class="space-y-4">
            <div class="card card-pad flex flex-wrap items-end justify-between gap-3"><div class="text-sm text-slate-600"><p class="font-medium text-slate-900">Simulate an incoming event</p><p class="text-xs">In production Square calls a verified webhook; duplicates are ignored via the event ID.</p></div>
                <form method="POST" action="{{ route('billing.simulate') }}" class="flex items-end gap-2">@csrf<div><label class="label" for="evt">Event type</label><select id="evt" name="type" class="select w-52"><option>payment.updated</option><option>subscription.updated</option><option>refund.updated</option></select></div><button class="btn btn-secondary">Send simulated event</button></form></div>
            <div class="card"><div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Received</th><th>Event</th><th>Type</th><th>Signature</th><th>Result</th><th>Attempts</th><th>Detail</th><th class="text-right">Action</th></tr></thead><tbody>
                @foreach ($webhooks as $w)<tr><td class="whitespace-nowrap text-slate-500">{{ fdt($w->received_at) }}</td><td class="font-mono text-[11px]">{{ $w->event_id }}</td><td>{{ $w->type }}</td><td><span class="chip {{ $w->signature === 'verified' ? 'chip-green' : 'chip-red' }}">{{ ucfirst($w->signature) }}</span></td><td><x-status dim="misc" :value="$w->status" /></td><td class="text-center">{{ $w->attempts }}</td><td class="max-w-sm text-slate-600">{{ $w->summary }}@if ($w->error)<p class="text-xs text-red-700">{{ $w->error }}</p>@endif</td><td class="text-right">@if ($w->status === 'failed' && $w->signature === 'verified')<form method="POST" action="{{ route('billing.retry', $w->id) }}">@csrf<button class="btn btn-secondary btn-xs">Retry</button></form>@elseif ($w->signature === 'invalid')<span class="text-xs text-slate-400">Rejected — not retryable</span>@else<span class="text-xs text-slate-400">—</span>@endif</td></tr>@endforeach
            </tbody></table></div></div>
        </div>

        {{-- SYNC LOG --}}
        <div x-show="tab === 'log'" x-cloak class="card"><div class="card-head"><h2 class="card-title">Safe sync log</h2><span class="text-xs text-slate-500">No raw secrets or card data are ever logged</span></div>
            <ul class="divide-y divide-slate-100 text-sm">@forelse ($syncLog as $e)<li class="flex items-start justify-between gap-3 px-5 py-3"><div><p class="text-slate-800">{{ $e->message }}</p><p class="text-xs text-slate-400">{{ fdt($e->at) }} · ref {{ $e->reference }}</p></div><span class="chip {{ ['open' => 'chip-amber', 'acknowledged' => 'chip-blue', 'resolved' => 'chip-green'][$e->status] }}">{{ ucfirst($e->status) }}</span></li>@empty<li class="px-5 py-6 text-center text-slate-500">No Square log entries.</li>@endforelse</ul></div>

        {{-- RECONCILIATION --}}
        <div x-show="tab === 'reconciliation'" x-cloak class="card">
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Customer</th><th>Agreement</th><th>Period</th><th class="text-right">Expected</th><th class="text-right">Received</th><th class="text-right">Difference</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody>
                @foreach ($recon as $r)<tr><td class="font-medium">{{ $r->customer?->business_name }}</td><td>{{ $r->agreement_id }}</td><td>{{ $r->period }}</td><td class="text-right">{{ money($r->expected) }}</td><td class="text-right">{{ money($r->received) }}</td><td class="text-right font-medium {{ $r->expected - $r->received ? 'text-red-600' : 'text-emerald-600' }}">{{ money($r->received - $r->expected) }}</td><td><x-status dim="misc" :value="$r->status" />@if ($r->note)<p class="mt-0.5 max-w-xs text-[11px] text-slate-500">{{ $r->note }}</p>@endif</td>
                    <td class="text-right">@if (! $r->resolved)<button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.open('reconcile', { action: '{{ route('billing.reconcile', $r->id) }}', name: '{{ addslashes($r->customer?->business_name) }}' })">Reconcile…</button>@else<span class="text-xs text-slate-400">Resolved</span>@endif</td></tr>@endforeach
            </tbody></table></div>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Mismatches are flagged, never silently fixed. Manual reconciliation records who resolved it and why.</p>
        </div>
    </div>

    <x-slot:modals>
        <x-modal name="reconcile" title="Manual reconciliation" description="Record why this mismatch is acceptable or how it was resolved.">
            <form method="POST" :action="$store.modal.payload.action" x-data="{ note: '', touched: false }" @submit="touched = true; if (note.trim().length < 5) $event.preventDefault()">@csrf
                <p class="mb-3 text-sm text-slate-600">Customer: <strong x-text="$store.modal.payload.name"></strong></p>
                <x-field label="Resolution note" name="note" :required="true"><textarea id="note" name="note" x-model="note" rows="3" class="textarea" :class="{ 'input-error': touched && note.trim().length < 5 }"></textarea><p class="err" x-show="touched && note.trim().length < 5" x-cloak>Add a note (min. 5 characters).</p></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Mark reconciled</button></div></form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
