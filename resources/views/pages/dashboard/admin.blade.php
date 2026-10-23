<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($d['kpi'] as [$label, $val, $icon, $tone, $href, $hint])<x-stat :label="$label" :value="$val" :icon="$icon" :tone="$tone" :href="$href" :hint="$hint" />@endforeach
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Opportunities by pipeline stage</h2><span class="tag">Mock data</span></div>
        <div class="p-5"><x-chart type="bar" :horizontal="true" xTitle="Pipeline stage" yTitle="Opportunities (count)" caption="Opportunities by pipeline stage"
            :labels="collect($d['pipeline'])->keys()->map(fn ($k) => status_label('sales', $k))->all()" :datasets="[['label' => 'Opportunities', 'data' => array_values($d['pipeline']), 'color' => 'brand']]" height="h-80" /></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Leads by source</h2></div>
        <div class="p-5"><x-chart type="doughnut" caption="Leads by source" :labels="$d['sources']->keys()->all()" :datasets="[['label' => 'Leads', 'data' => $d['sources']->values()->all()]]" height="h-72" /></div>
    </div>
</div>

<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Commission liability</h2><span class="tag tag-synthetic">Synthetic amounts</span></div>
        <div class="p-5"><x-chart type="bar" xTitle="Commission status" yTitle="Amount (USD)" unit="$" caption="Commission liability by status" :labels="array_keys($d['liability'])" :datasets="[['label' => 'Amount', 'data' => array_values($d['liability']), 'color' => 'violet']]" height="h-64" /></div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Exception queues</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach ($d['exceptions'] as [$label, $count, $href])
                <li><a href="{{ $href }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50"><span class="text-slate-700">{{ $label }}</span><span class="chip {{ $count ? 'chip-amber' : 'chip-green' }}">{{ $count }}</span></a></li>
            @endforeach
        </ul>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="card-title">Integration health</h2><x-demo-tag label="Simulated" /></div>
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach ($d['integrations'] as [$name, $state, $note])
                <li class="flex items-center justify-between gap-3 px-5 py-3"><div><p class="font-medium text-slate-800">{{ $name }}</p><p class="text-xs text-slate-500">{{ $note }}</p></div><x-status dim="misc" :value="$state" /></li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card mt-5">
    <div class="card-head"><h2 class="card-title">Recent activity</h2><a href="{{ route('admin.activity') }}" class="text-sm font-medium text-brand-700 hover:underline">Full audit trail</a></div>
    <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Record</th><th>Reason</th></tr></thead><tbody>
        @foreach ($d['activity'] as $a)<tr><td class="whitespace-nowrap text-slate-500">{{ ago($a->at) }}</td><td>{{ $a->actor?->name }}</td><td class="font-medium">{{ $a->action }}</td><td>{{ $a->entity }}</td><td class="text-slate-500">{{ $a->reason ?? '—' }}</td></tr>@endforeach
    </tbody></table></div>
</div>
