<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Pending your review" :value="$d['pending']" icon="clipboard-document-check" tone="amber" :href="route('review.index')" hint="Accept or reject" />
    <x-stat label="Lead → Closed Won" :value="$d['conversion'].'%'" icon="arrow-trending-up" tone="green" :hint="$d['accepted'].' accepted · '.$d['rejectRate'].'% of reviewed rejected'" />
    <x-stat label="Recurring revenue (signed)" :value="money($d['mrr'])" icon="arrow-path" tone="brand" :href="route('revenue.index')" :hint="$d['signedCustomers'].' fully signed customers · MRR/MRC only'" />
    <x-stat label="Team commission due" :value="money($d['commissionTeam']['due'] + $d['commissionTeam']['approved'], null, 2)" icon="banknotes" tone="violet" :href="route('commissions.index')" hint="Synthetic sample amounts" />
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Team pipeline</h2></div>
        <div class="p-5"><x-chart type="bar" xTitle="Pipeline stage" yTitle="Opportunities (count)" caption="Team pipeline by stage" :labels="collect($d['pipeline'])->keys()->map(fn ($k) => status_label('sales', $k))->all()" :datasets="[['label' => 'Opportunities', 'data' => array_values($d['pipeline']), 'color' => 'brand']]" height="h-72" /></div>
    </div>
    @include('pages.dashboard._events', ['upcoming' => $d['upcoming']])
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    @include('pages.dashboard._followups', ['followUps' => $d['followUps']])
    <div class="card">
        <div class="card-head"><h2 class="card-title">Stale opportunities</h2><span class="tag">No activity for 6+ days</span></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['stale'] as $l)<li><a href="{{ route('leads.show', $l->id) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50"><span><span class="block font-medium">{{ $l->customer->business_name }}</span><span class="text-xs text-slate-500">{{ $l->owner?->name }} · {{ status_label('sales', $l->sales_status) }}</span></span><span class="text-xs text-slate-500">{{ ago(collect($l->activities)->max('at')) }}</span></a></li>@empty<li class="px-5 py-6 text-center text-slate-500">No stale opportunities.</li>@endforelse</ul>
    </div>
</div>
<div class="card mt-5">
    <div class="card-head"><h2 class="card-title">Team activity</h2></div>
    <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Agent / Team Lead</th><th class="text-right">Leads</th><th class="text-right">Open</th><th class="text-right">Pending review</th><th class="text-right">Closed Won</th><th>Last activity</th></tr></thead><tbody>
        @foreach ($d['agents'] as $r)<tr><td><div class="flex items-center gap-2"><x-avatar :name="$r['owner']?->name" size="xs" />{{ $r['owner']?->name }}</div></td><td class="text-right">{{ $r['total'] }}</td><td class="text-right">{{ $r['open'] }}</td><td class="text-right">{{ $r['pending'] }}</td><td class="text-right">{{ $r['won'] }}</td><td class="text-slate-500">{{ ago($r['last']) }}</td></tr>@endforeach
    </tbody></table></div>
</div>
