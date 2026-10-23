<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Pending review" :value="$d['counts']['pending']" icon="clock" tone="amber" :href="route('leads.index')" :hint="$d['counts']['draft'].' draft(s) not submitted'" />
    <x-stat label="Accepted leads" :value="$d['counts']['accepted']" icon="check-circle" tone="green" :href="route('leads.index')" :hint="$d['counts']['rejected'].' rejected'" />
    <x-stat label="Commission due" :value="money($d['myCommission']['due'], null, 2)" icon="banknotes" tone="orange" :href="route('commissions.index')" hint="Your own · approved or due" />
    <x-stat label="Commission paid" :value="money($d['myCommission']['paid'], null, 2)" icon="check-badge" tone="teal" :href="route('commissions.index')" hint="Synthetic sample amounts" />
</div>
@if ($d['rejected']->count())
    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"><p class="font-semibold">{{ $d['rejected']->count() }} lead(s) were rejected</p>
        <ul class="mt-1 list-disc pl-5">@foreach ($d['rejected'] as $l)<li><a class="underline" href="{{ route('leads.show', $l->id) }}">{{ $l->customer->business_name }}</a> — {{ $l->rejection_reason }}</li>@endforeach</ul></div>
@endif
<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Your leads by sales stage</h2></div>
        <div class="p-5"><x-chart type="bar" xTitle="Pipeline stage" yTitle="Your opportunities (count)" caption="Your leads by sales stage" :labels="collect($d['pipeline'])->keys()->map(fn ($k) => status_label('sales', $k))->all()" :datasets="[['label' => 'Opportunities', 'data' => array_values($d['pipeline']), 'color' => 'brand']]" height="h-64" /></div>
    </div>
    @include('pages.dashboard._events', ['upcoming' => $d['upcoming']])
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    @include('pages.dashboard._followups', ['followUps' => $d['followUps']])
    <div class="card">
        <div class="card-head"><h2 class="card-title">Updates on your leads</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['feedback'] as $x)<li><a href="{{ route('leads.show', $x['lead']->id) }}" class="block px-5 py-3 hover:bg-slate-50"><p class="font-medium">{{ $x['lead']->customer->business_name }}</p><p class="text-xs text-slate-500">{{ $x['h']->field }}: {{ $x['h']->from }} → {{ $x['h']->to }} · {{ ago($x['h']->at) }}</p></a></li>@empty<li class="px-5 py-6 text-center text-slate-500">No updates yet.</li>@endforelse</ul>
    </div>
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Audit &amp; quote requests</h2><a href="{{ route('audits.index') }}" class="text-sm font-medium text-brand-700 hover:underline">View all</a></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['audits'] as $a)<li><a href="{{ route('audits.show', $a->id) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50"><span><span class="block font-medium">{{ $a->customer?->business_name }}</span><span class="text-xs text-slate-500">{{ ucfirst($a->type) }} · {{ $a->id }}</span></span><x-status dim="audit" :value="$a->status" /></a></li>@empty<li class="px-5 py-6 text-center text-slate-500">No requests yet.</li>@endforelse</ul>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Learning resources</h2><a href="{{ route('learning.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Library</a></div>
        <ul class="divide-y divide-slate-100 text-sm">@foreach ($d['learning'] as $r)<li class="flex items-center gap-3 px-5 py-3"><x-ico name="academic-cap" class="size-5 text-slate-400" /><div><p class="font-medium">{{ $r->title }}</p><p class="text-xs text-slate-500">{{ $r->category }} · {{ ago($r->published_at) }}</p></div></li>@endforeach</ul>
    </div>
</div>
