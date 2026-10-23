@php($t = $d['totals'])
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Awaiting handoff / agreement" :value="$d['queue']->count()" icon="inbox-arrow-down" tone="amber" :href="route('finance.handoff')" hint="Closed Won queue" />
    <x-stat label="Agreements out for signing" :value="$d['unsigned']" icon="document-text" tone="teal" :href="route('agreements.index')" :hint="$d['signed'].' fully signed'" />
    <x-stat label="Contracted MRR/MRC (signed)" :value="money($t['contracted_mrr'])" icon="arrow-path" tone="brand" hint="Contract value — not cash received" />
    <x-stat label="Cash received (30 days)" :value="money($t['received_30d'])" icon="banknotes" tone="green" hint="Actual payments only" />
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Cash in vs cash out</h2><span class="tag tag-synthetic">Mock data · commissions counted only when paid</span></div>
        <div class="p-5"><x-chart type="bar" xTitle="Month" yTitle="Amount (USD)" unit="$" caption="Monthly cash in and cash out" :labels="$d['series']['labels']" height="h-72"
            :datasets="[['label' => 'Cash in', 'data' => $d['series']['cash_in'], 'color' => 'green'], ['label' => 'Commissions paid', 'data' => $d['series']['commission'], 'color' => 'violet'], ['label' => 'Refunds', 'data' => $d['series']['refunds'], 'color' => 'red'], ['label' => 'Other cash out', 'data' => $d['series']['other_out'], 'color' => 'slate']]" /></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Commissions</h2><a href="{{ route('commissions.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Open</a></div>
        <dl class="divide-y divide-slate-100 text-sm">
            @foreach ([['Pending / eligible', $t['commission_pending'] + $t['commission_eligible'], 'amber'], ['Approved', $t['commission_approved'], 'indigo'], ['Due', $t['commission_due'], 'orange'], ['On hold', $t['commission_hold'], 'violet'], ['Paid (all time)', $t['commission_paid'], 'green']] as [$l, $v, $c])
                <div class="flex items-center justify-between px-5 py-3"><dt class="text-slate-600">{{ $l }}</dt><dd class="font-semibold">{{ money($v, null, 2) }}</dd></div>
            @endforeach
        </dl>
        <p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">Synthetic sample amounts. Eligibility = Closed Won + {{ config('portal.defaults.commission_eligibility_days') }} days (prototype assumption).</p>
    </div>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Closed Won handoff queue</h2><a href="{{ route('finance.handoff') }}" class="text-sm font-medium text-brand-700 hover:underline">Open queue</a></div>
        <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Customer</th><th>Closed Won</th><th>Handoff</th><th></th></tr></thead><tbody>
            @forelse ($d['queue'] as $l)
                <tr><td class="font-medium">{{ $l->customer->business_name }}<p class="text-xs font-normal text-slate-500">{{ $l->id }} · {{ $l->project->name }}</p></td><td class="text-slate-600">{{ ago($l->closed_won_at) }}</td>
                    <td><span class="chip {{ ($l->handoff?->state ?? 'new') === 'new' ? 'chip-amber' : 'chip-blue' }}">{{ ($l->handoff?->state ?? 'new') === 'new' ? 'New' : 'In progress' }}</span></td>
                    <td class="text-right"><a href="{{ route('agreements.create', ['lead' => $l->id]) }}" class="btn btn-secondary btn-xs">Build agreement</a></td></tr>
            @empty<tr><td colspan="4" class="py-6 text-center text-slate-500">The queue is empty.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="space-y-5">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Square &amp; subscriptions</h2><x-demo-tag label="Sandbox · simulated" /></div>
            <div class="grid grid-cols-2 gap-3 p-5 text-sm">
                <div><p class="text-xs text-slate-500">Customers mapped</p><p class="text-lg font-semibold">{{ $d['square']['mapped'] }}</p></div>
                <div><p class="text-xs text-slate-500">Not mapped</p><p class="text-lg font-semibold">{{ $d['square']['unmapped'] }}</p></div>
                @foreach ($d['subs'] as $s => $n)<div><x-status dim="billing" :value="$s" /> <span class="ml-1 font-semibold">{{ $n }}</span></div>@endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Reconciliation exceptions</h2><span class="chip {{ $d['recon']->count() ? 'chip-amber' : 'chip-green' }}">{{ $d['recon']->count() }}</span></div>
            <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['recon'] as $r)<li class="px-5 py-3"><p class="font-medium">{{ $r->customer?->business_name }} · {{ $r->period }}</p><p class="text-xs text-slate-500">Expected {{ money($r->expected) }} · received {{ money($r->received) }}</p></li>@empty<li class="px-5 py-4 text-sm text-slate-500">Everything is reconciled.</li>@endforelse</ul>
        </div>
    </div>
</div>
