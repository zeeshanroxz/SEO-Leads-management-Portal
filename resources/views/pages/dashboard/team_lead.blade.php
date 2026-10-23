<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Your leads awaiting review" :value="$d['review']->count()" icon="clipboard-document-check" tone="amber" :href="route('review.index')" hint="You can accept your own leads" />
    <x-stat label="Open opportunities" :value="collect($d['pipeline'])->except('closed_won')->sum()" icon="user-group" tone="brand" :href="route('leads.index')" hint="Your team's pipeline" />
    <x-stat label="Commission due" :value="money($d['myCommission']['due'], null, 2)" icon="banknotes" tone="orange" :href="route('commissions.index')" hint="Your own · approved or due" />
    <x-stat label="Commission paid" :value="money($d['myCommission']['paid'], null, 2)" icon="check-badge" tone="green" :href="route('commissions.index')" hint="Your own · synthetic sample" />
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Pipeline by stage</h2></div>
        <div class="p-5"><x-chart type="bar" xTitle="Pipeline stage" yTitle="Opportunities (count)" caption="Pipeline by stage" :labels="collect($d['pipeline'])->keys()->map(fn ($k) => status_label('sales', $k))->all()" :datasets="[['label' => 'Opportunities', 'data' => array_values($d['pipeline']), 'color' => 'indigo']]" height="h-72" /></div>
    </div>
    @include('pages.dashboard._events', ['upcoming' => $d['upcoming']])
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    @include('pages.dashboard._followups', ['followUps' => $d['followUps']])
    <div class="card">
        <div class="card-head"><h2 class="card-title">Audit &amp; quote progress</h2><a href="{{ route('audits.index') }}" class="text-sm font-medium text-brand-700 hover:underline">All requests</a></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['audits'] as $a)<li><a href="{{ route('audits.show', $a->id) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50"><span><span class="block font-medium">{{ $a->customer?->business_name }}</span><span class="text-xs text-slate-500">{{ ucfirst($a->type) }} · {{ $a->auditor?->name ?? 'unassigned' }}</span></span><x-status dim="audit" :value="$a->status" /></a></li>@empty<li class="px-5 py-6 text-center text-slate-500">No requests.</li>@endforelse</ul>
    </div>
</div>
<div class="card mt-5">
    <div class="card-head"><h2 class="card-title">Agent activity</h2></div>
    <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Agent</th><th class="text-right">Leads</th><th class="text-right">Open</th><th class="text-right">Pending review</th><th class="text-right">Closed Won</th><th>Last activity</th></tr></thead><tbody>
        @foreach ($d['agents'] as $r)<tr><td><div class="flex items-center gap-2"><x-avatar :name="$r['owner']?->name" size="xs" />{{ $r['owner']?->name }}</div></td><td class="text-right">{{ $r['total'] }}</td><td class="text-right">{{ $r['open'] }}</td><td class="text-right">{{ $r['pending'] }}</td><td class="text-right">{{ $r['won'] }}</td><td class="text-slate-500">{{ ago($r['last']) }}</td></tr>@endforeach
    </tbody></table></div>
</div>
