<x-app-layout title="Recurring revenue (signed)" :crumbs="[['Finance'], ['Recurring revenue']]" subtitle="MRR/MRC for customers whose agreement is fully signed. No other finance records or agreement content are shown here.">
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
        <x-ico name="information-circle" class="mt-0.5 size-5" />
        <p>This is <strong>contracted</strong> recurring revenue from fully signed agreements — not cash received. “MRR” vs “MRC” is a business definition still to be confirmed (see Settings & assumptions).@if (demo()::is('manager')) As a Manager you can see this for all signed customers, but not agreement terms or other finance data.@endif</p>
    </div>
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat label="Contracted MRR / MRC" :value="money($total)" icon="arrow-path" tone="brand" hint="Fully signed agreements only" />
        <x-stat label="Signed customers" :value="$rows->count()" icon="building-office-2" tone="teal" />
        <x-stat label="Average per customer" :value="money($rows->count() ? $total / $rows->count() : 0)" icon="calculator" tone="violet" />
    </div>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="card"><div class="card-head"><h2 class="card-title">Customers</h2><span class="tag tag-synthetic">Sample data</span></div>
            @if ($rows->isEmpty())<x-empty-state icon="arrow-path" title="No fully signed customers yet" text="Figures appear once an agreement is fully signed." />@else
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Customer</th><th>Sold by</th><th>Signed</th><th>Term ends</th><th class="text-right">MRR / MRC</th></tr></thead><tbody>
                @foreach ($rows as $r)<tr><td class="font-medium">{{ $r->customer }}<p class="text-xs font-normal text-slate-400">{{ $r->agreement_ref }}</p></td><td class="text-slate-600">{{ $r->owner }}<p class="text-xs text-slate-400">TL {{ $r->team_lead }}</p></td><td class="whitespace-nowrap text-slate-600">{{ fdate($r->signed_at) }}</td><td class="whitespace-nowrap text-slate-600">{{ fdate($r->end_date) }}</td><td class="text-right font-semibold">{{ money($r->mrr) }}</td></tr>@endforeach
            </tbody><tfoot><tr><td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-slate-600">Total</td><td class="px-4 py-3 text-right font-semibold">{{ money($total) }}</td></tr></tfoot></table></div>@endif
        </div>
        <div class="card"><div class="card-head"><h2 class="card-title">MRR / MRC by customer</h2></div><div class="p-5"><x-chart type="bar" :horizontal="true" xTitle="Customer" yTitle="Monthly recurring (USD)" unit="$" caption="MRR by customer" :labels="$rows->pluck('customer')->all()" :datasets="[['label' => 'MRR / MRC', 'data' => $rows->pluck('mrr')->all(), 'color' => 'brand']]" height="h-64" /></div></div>
    </div>
</x-app-layout>
