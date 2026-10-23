@php
    $won = \Carbon\Carbon::parse($c->closed_won_at);
@endphp
<x-app-layout :title="'Commission '.$c->id" :crumbs="[['Commissions', route('commissions.index')], [$c->id]]">
    <x-slot:actions><x-status dim="commission" :value="$c->status" class="!text-sm" /><span class="tag tag-synthetic">Synthetic</span></x-slot:actions>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <div class="card"><div class="card-head"><h2 class="card-title">Details</h2></div>
                <dl class="dl p-5"><div><dt>Recipient</dt><dd>{{ $c->recipient?->name }} <span class="text-slate-500">· {{ $c->role_at_sale }} at time of sale</span></dd></div><div><dt>Customer</dt><dd>{{ $c->customer?->business_name }} <span class="text-slate-500">({{ $c->lead_id }})</span></dd></div>
                    <div><dt>Type</dt><dd>{{ $c->type === 'residual' ? 'Residual · '.$c->recurring_period : 'One-time' }}</dd></div><div><dt>Calculation basis</dt><dd>{{ $c->basis_label }} — {{ money($c->basis_amount) }}</dd></div>
                    <div><dt>Rate</dt><dd>{{ $c->rate_type === 'fixed' ? 'Fixed amount' : $c->rate.'%' }}</dd></div><div><dt>Amount</dt><dd class="font-semibold">{{ money($c->amount, null, 2) }}@if (count($c->adjustments)) <span class="font-normal text-slate-500">→ {{ money($c->current_amount, null, 2) }} after adjustments</span>@endif</dd></div>
                    <div><dt>Approved</dt><dd>{{ $c->approved_at ? fdt($c->approved_at).' by '.$dir->userName($c->approved_by) : '—' }}</dd></div><div><dt>Due / paid</dt><dd>{{ $c->paid_date ? 'Paid '.fdate($c->paid_date).' · ref '.$c->payout_ref : ($c->due_date ? 'Due '.fdate($c->due_date) : '—') }}</dd></div></dl></div>

            <div class="card"><div class="card-head"><h2 class="card-title">Why is it {{ $c->status === 'pending' ? 'not eligible yet' : 'eligible when it is' }}?</h2></div>
                <div class="space-y-3 p-5 text-sm text-slate-700">
                    <ol class="space-y-2">
                        <li class="flex gap-2"><span class="mt-0.5 size-5 shrink-0 rounded-full bg-emerald-500 text-center text-xs font-bold leading-5 text-white">1</span> Closed Won on <strong>{{ fdate($c->closed_won_at) }}</strong>.</li>
                        <li class="flex gap-2"><span class="mt-0.5 size-5 shrink-0 rounded-full {{ $c->days_to_eligible <= 0 ? 'bg-emerald-500' : 'bg-amber-500' }} text-center text-xs font-bold leading-5 text-white">2</span> + <strong>{{ $days }} {{ $dayMode }} days</strong> = eligible from <strong>{{ fdate($c->eligibility_date) }}</strong> ({{ $c->days_to_eligible <= 0 ? 'reached '.abs($c->days_to_eligible).' day(s) ago' : $c->days_to_eligible.' day(s) to go' }}).</li>
                        <li class="flex gap-2"><span class="mt-0.5 size-5 shrink-0 rounded-full {{ in_array($c->status, ['not_eligible', 'on_hold']) ? 'bg-red-500' : 'bg-emerald-500' }} text-center text-xs font-bold leading-5 text-white">3</span> Prerequisite (prototype): the agreement is fully signed. {{ in_array($c->status, ['not_eligible', 'on_hold']) ? 'Not met right now.' : 'Met.' }}</li>
                        <li class="flex gap-2"><span class="mt-0.5 size-5 shrink-0 rounded-full {{ in_array($c->status, ['approved', 'due', 'paid']) ? 'bg-emerald-500' : 'bg-slate-300' }} text-center text-xs font-bold leading-5 text-white">4</span> Approved by Finance, then due, then paid. Eligibility does not mean paid.</li>
                    </ol>
                    <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">The {{ $days }}-day rule, calendar vs business days, the trigger event and prerequisites are prototype assumptions awaiting a business decision (see Settings & assumptions).</p>
                </div></div>

            @if (count($c->ledger))<div class="card"><div class="card-head"><h2 class="card-title">Residual ledger</h2></div><table class="tbl tbl-compact"><thead><tr><th>Period</th><th>Date</th><th>Status</th><th>Reference</th><th class="text-right">Amount</th></tr></thead><tbody>@foreach ($c->ledger as $l)<tr><td>{{ $l->period }}</td><td>{{ fdate($l->date) }}</td><td><x-status dim="commission" :value="$l->status" /></td><td class="text-slate-500">{{ $l->ref ?? '—' }}</td><td class="text-right">{{ money($l->amount, null, 2) }}</td></tr>@endforeach</tbody></table></div>@endif

            @if (count($c->adjustments))<div class="card"><div class="card-head"><h2 class="card-title">Adjustments &amp; reversals</h2><span class="text-xs text-slate-500">Linked entries — originals are never edited</span></div><table class="tbl tbl-compact"><thead><tr><th>Entry</th><th>When</th><th>By</th><th>Reason</th><th class="text-right">Amount</th></tr></thead><tbody>@foreach ($c->adjustments as $a)<tr><td>{{ $a->id }} → {{ $a->linked_to }}</td><td>{{ fdt($a->at) }}</td><td>{{ $dir->userName($a->by) }}</td><td class="text-slate-600">{{ $a->reason }}</td><td class="text-right font-medium {{ $a->delta < 0 ? 'text-red-600' : '' }}">{{ money($a->delta, null, 2) }}</td></tr>@endforeach</tbody></table></div>@endif
        </div>
        <aside class="space-y-5">
            <div class="card"><div class="card-head"><h3 class="card-title">Ledger history</h3></div>
                <ol class="space-y-3 p-5 text-sm">@forelse (collect($c->history)->sortByDesc('at') as $h)<li class="flex gap-3"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-400"></span><div><p><span class="font-medium">{{ $h->field }}</span>@if ($h->old) {{ $h->old }} →@endif {{ $h->new }}</p>@if ($h->reason)<p class="text-xs text-slate-600">“{{ $h->reason }}”</p>@endif<p class="text-xs text-slate-400">{{ $dir->userName($h->by) }} · {{ fdt($h->at) }}</p></div></li>@empty<li class="text-slate-500">No changes recorded since creation.</li>@endforelse</ol></div>
            <div class="card"><div class="card-head"><h3 class="card-title">Rule snapshot</h3></div><div class="p-5 text-sm text-slate-600">@if ($rule)<p class="font-medium text-slate-900">{{ $rule->name }}</p><p class="mt-1">Version used: v{{ $c->rule_version }} (current v{{ $rule->version }}). Past commissions keep the rule they were calculated with.</p><p class="mt-1 text-xs"><span class="tag tag-synthetic">Synthetic sample rate</span></p>@else<p>No rule linked.</p>@endif</div></div>
        </aside>
    </div>
</x-app-layout>
