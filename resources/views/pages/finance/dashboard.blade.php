@php($t = $totals)
<x-app-layout title="Finance dashboard" :crumbs="[['Finance'], ['Dashboard']]" subtitle="Cash in and out, contracted recurring revenue, subscriptions and commission liability. All figures are mock data.">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2"><label for="range" class="text-sm text-slate-500">Range</label><select id="range" name="range" class="select h-9 w-36" onchange="this.form.submit()"><option value="3" @selected($months === 3)>Last 3 months</option><option value="6" @selected($months === 6)>Last 6 months</option></select></form>
        <button type="button" class="btn btn-secondary" x-data @click="(() => { const rows = [['Month','Cash in','Refunds','Commissions paid','Other cash out'], ...@js(collect($series['labels'])->map(fn ($l, $i) => [$l, $series['cash_in'][$i], $series['refunds'][$i], $series['commission'][$i], $series['other_out'][$i]])->all())]; const blob = new Blob([rows.map(r => r.join(',')).join('\n')], { type: 'text/csv' }); const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'cash-summary-mock.csv'; a.click(); $store.toast.push('Exported the on-screen mock figures as CSV (synthetic data).', 'info', { demo: true }) })()"><x-ico name="arrow-down-tray" class="size-4" /> Export CSV</button>
    </x-slot:actions>

    <div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><x-ico name="exclamation-triangle" class="mt-0.5 size-5" /><p><strong>Mock data.</strong> Contracted recurring revenue is a promise in a signed agreement; <strong>cash received</strong> only counts completed payments. Commissions appear as cash out only when a payout is recorded as paid.</p></div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Cash in (selected range)" :value="money($cashIn)" icon="arrow-down-left" tone="green" hint="Completed payments" />
        <x-stat label="Cash out (selected range)" :value="money($cashOut)" icon="arrow-up-right" tone="red" :hint="'incl. '.money($commissionPaid).' commissions paid'" />
        <x-stat label="Contracted MRR / MRC" :value="money($t['contracted_mrr'])" icon="arrow-path" tone="brand" hint="Signed agreements · not cash" />
        <x-stat label="Outstanding / failed" :value="money($t['outstanding'])" icon="exclamation-circle" tone="orange" :hint="money($t['failed']).' failed'" />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <div class="card xl:col-span-2"><div class="card-head"><h2 class="card-title">Cash in and cash out by month</h2><span class="tag tag-synthetic">Mock</span></div>
            <div class="p-5"><x-chart type="bar" :stacked="false" xTitle="Month" yTitle="Amount (USD)" unit="$" caption="Cash in and out by month" :labels="$series['labels']" height="h-72" :datasets="[['label' => 'Cash in', 'data' => $series['cash_in'], 'color' => 'green'], ['label' => 'Commissions paid', 'data' => $series['commission'], 'color' => 'violet'], ['label' => 'Refunds', 'data' => $series['refunds'], 'color' => 'red'], ['label' => 'Other cash out', 'data' => $series['other_out'], 'color' => 'slate'], ['label' => 'Net', 'data' => $series['net'], 'color' => 'brand', 'type' => 'line']]" /></div></div>
        <div class="card"><div class="card-head"><h2 class="card-title">Commission liability</h2></div>
            <div class="p-5"><x-chart type="doughnut" caption="Commission liability by status" :labels="['Pending/eligible', 'Approved', 'Due', 'On hold', 'Paid']" :datasets="[['label' => 'USD', 'data' => [round($t['commission_pending'] + $t['commission_eligible'], 2), $t['commission_approved'], $t['commission_due'], $t['commission_hold'], $t['commission_paid']], 'colors' => ['amber', 'indigo', 'orange', 'violet', 'green']]]" height="h-64" /></div></div>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <div class="card"><div class="card-head"><h2 class="card-title">Revenue vs cash (definitions)</h2></div>
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between px-5 py-3"><dt class="text-slate-600">Signed contract value</dt><dd class="font-semibold">{{ money($t['signed_value']) }}</dd></div>
                <div class="flex items-center justify-between px-5 py-3"><dt class="text-slate-600">Contracted MRR/MRC</dt><dd class="font-semibold">{{ money($t['contracted_mrr']) }}</dd></div>
                <div class="flex items-center justify-between px-5 py-3"><dt class="text-slate-600">Expected billing (30 days)</dt><dd class="font-semibold">{{ money($t['expected_billing_30d']) }}</dd></div>
                <div class="flex items-center justify-between px-5 py-3"><dt class="text-slate-600">Actual receipts (30 days)</dt><dd class="font-semibold text-emerald-700">{{ money($t['received_30d']) }}</dd></div>
                <div class="flex items-center justify-between px-5 py-3"><dt class="text-slate-600">Active subscriptions</dt><dd class="font-semibold">{{ $t['active_subscriptions'] }}</dd></div>
            </dl><p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">Formulas are placeholders until the business confirms its accounting definitions.</p></div>
        <div class="card xl:col-span-2" x-data="dataTable({ perPage: 6, sortKey: 'date', sortDir: 'desc', filters: { status: '' } })">
            <div class="card-head"><h2 class="card-title">Payments</h2><div class="flex items-center gap-2"><select x-model="filters.status" class="select h-8 w-36 text-[13px]" aria-label="Payment status"><option value="">All statuses</option><option value="completed">Completed</option><option value="failed">Failed</option><option value="pending">Pending</option><option value="refunded">Refunded</option></select></div></div>
            <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th class="th-sort" @click="sort('date')">Date <span x-text="arrow('date')"></span></th><th>Customer</th><th>Type</th><th>Status</th><th class="text-right">Amount</th></tr></thead><tbody>
                @foreach ($payments as $p)<tr data-row data-f-status="{{ $p->status }}" data-s-date="{{ $p->date }}" data-search="{{ strtolower($p->customer?->business_name.' '.$p->id) }}"><td class="whitespace-nowrap text-slate-600">{{ fdate($p->date) }}</td><td>{{ $p->customer?->business_name }}<p class="text-xs text-slate-400">{{ $p->id }}</p></td><td class="capitalize text-slate-600">{{ str_replace('_', ' ', $p->type) }}</td><td><span class="chip {{ ['completed' => 'chip-green', 'failed' => 'chip-red', 'pending' => 'chip-amber', 'refunded' => 'chip-stone'][$p->status] }}">{{ ucfirst($p->status) }}</span></td><td class="text-right font-medium {{ $p->amount < 0 ? 'text-red-600' : '' }}">{{ money($p->amount) }}</td></tr>@endforeach
            </tbody></table></div><x-table-footer />
        </div>
    </div>
</x-app-layout>
