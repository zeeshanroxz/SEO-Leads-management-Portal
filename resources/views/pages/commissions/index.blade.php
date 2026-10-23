@php
    $title = match ($mode) { 'all' => 'Commissions', 'manager' => 'Team commissions', default => 'My commission' };
    $canAct = $canApprove;
    $next = ['eligible' => ['approved', 'Approve'], 'approved' => ['due', 'Mark due']];
    $money = fn ($v) => money($v, null, 2);
@endphp
<x-app-layout :title="$title" :crumbs="[['Commissions']]" :subtitle="$mode === 'own' ? 'Your own commission status — due and paid. Wider finance data is not shown to your role.' : ($mode === 'manager' ? 'Your own commission and the permitted commissions of your team. No wider finance data.' : 'One-time and residual commissions with eligibility, approval and payout. All amounts are synthetic samples.')">
    <x-slot:actions>
        @perm('commission.manage_rules')<a href="{{ route('commissions.rules') }}" class="btn btn-secondary"><x-ico name="adjustments-horizontal" class="size-4" /> Rules</a>@endperm
        @if ($canAct)<form method="POST" action="{{ route('commissions.recalculate') }}">@csrf<button class="btn btn-secondary"><x-ico name="arrow-path" class="size-4" /> Recalculate eligibility</button></form>@endif
        @perm('commission.create')<button type="button" class="btn btn-primary" @click="$store.modal.open('create')"><x-ico name="plus" class="size-4" /> {{ $mode === 'manager' ? 'Add team commission' : 'Add commission' }}</button>@endperm
    </x-slot:actions>

    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <span class="tag tag-synthetic">Synthetic data</span>
        <span>Eligibility rule (prototype assumption, configurable): <strong>Closed Won + {{ $days }} {{ $dayMode }} days</strong>, then approval, then payout. Rates are samples — no real policy is assumed.</span>
    </div>

    @if ($mode === 'own')
        <div class="mb-5 grid gap-4 sm:grid-cols-3">
            <x-stat label="Due / approved" :value="$money($rows->whereIn('status', ['due', 'approved'])->sum('current_amount'))" icon="banknotes" tone="orange" />
            <x-stat label="Paid" :value="$money($totals['paid'])" icon="check-badge" tone="green" />
            <x-stat label="Not yet eligible" :value="$money($totals['pending'] + $totals['eligible'])" icon="clock" tone="amber" hint="Pending or eligible" />
        </div>
    @else
        <div class="mb-5 grid gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ([['Pending', $totals['pending'], 'amber'], ['Eligible', $totals['eligible'], 'blue'], ['Approved', $totals['approved'], 'indigo'], ['Due', $totals['due'], 'orange'], ['On hold', $totals['hold'], 'violet'], ['Paid', $totals['paid'], 'green']] as [$l, $v, $c])
                <div class="card px-4 py-3"><p class="text-xs font-medium text-slate-500">{{ $l }}</p><p class="mt-0.5 text-lg font-semibold">{{ $money($v) }}</p></div>
            @endforeach
        </div>
    @endif

    <div x-data="{ ...tabs('{{ $mode === 'manager' ? 'team' : 'all' }}', false) }">
        @if ($mode === 'manager')
            <div class="mb-4 flex gap-1 border-b border-slate-200" role="tablist"><button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === 'mine' }" @click="set('mine')">My commission</button><button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === 'team' }" @click="set('team')">Team commissions</button></div>
        @endif
        @foreach (($mode === 'manager' ? ['mine' => $mine, 'team' => $rows->where('recipient_id', '!=', demo()::id())->values()] : ['all' => $rows]) as $key => $list)
        <div class="card" x-show="tab === '{{ $key }}'" @if ($key !== ($mode === 'manager' ? 'team' : 'all')) x-cloak @endif x-data="dataTable({ perPage: 10, sortKey: 'won', sortDir: 'desc', filters: { status: '', type: '' } })">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
                <div class="relative min-w-56 flex-1"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input type="search" x-model="q" class="input pl-9" placeholder="Search customer, recipient, ID…" aria-label="Search commissions"></div>
                <select x-model="filters.status" class="select w-44" aria-label="Status"><option value="">All statuses</option>@foreach (config('statuses.commission.values') as $k => $v)<option value="{{ $k }}">{{ $v[0] }}</option>@endforeach</select>
                <select x-model="filters.type" class="select w-40" aria-label="Type"><option value="">One-time & residual</option><option value="one_time">One-time</option><option value="residual">Residual</option></select>
            </div>
            @if ($list->isEmpty())<x-empty-state icon="banknotes" title="No commission records" text="Records appear once a deal is Closed Won and a commission is set up." />@else
            <div class="overflow-x-auto"><table class="tbl min-w-[1180px]">
                <thead><tr><th>Record</th>@if ($mode !== 'own' && $key !== 'mine')<th>Recipient</th>@endif<th>Customer</th><th>Type</th><th>Basis · rate</th><th>Eligible from</th><th>Status</th><th>Due / paid</th><th class="text-right th-sort" @click="sort('amt')">Amount <span x-text="arrow('amt')"></span></th>@if ($canAct)<th class="text-right">Actions</th>@endif</tr></thead>
                <tbody>
                    @foreach ($list as $c)
                        @php($isMgr = $c->recipient?->role === 'manager')
                        @php($locked = $isMgr && ! $canApproveManager)
                        <tr data-row data-search="{{ strtolower($c->id.' '.$c->customer?->business_name.' '.$c->recipient?->name) }}" data-f-status="{{ $c->status }}" data-f-type="{{ $c->type }}" data-s-won="{{ $c->closed_won_at }}" data-s-amt="{{ $c->current_amount }}">
                            <td class="whitespace-nowrap"><a href="{{ route('commissions.show', $c->id) }}" class="font-medium hover:text-brand-700">{{ $c->id }}</a><p class="text-xs text-slate-500">{{ $c->lead_id }}</p></td>
                            @if ($mode !== 'own' && $key !== 'mine')<td class="whitespace-nowrap">{{ $c->recipient?->name }}<p class="text-xs text-slate-500">{{ $c->role_at_sale }}</p></td>@endif
                            <td class="min-w-40">{{ $c->customer?->business_name }}</td>
                            <td class="whitespace-nowrap"><span class="chip {{ $c->type === 'residual' ? 'chip-violet' : 'chip-slate' }}">{{ $c->type === 'residual' ? 'Residual · '.$c->recurring_period : 'One-time' }}</span></td>
                            <td class="whitespace-nowrap text-slate-600">{{ $c->rate_type === 'fixed' ? 'Fixed' : $c->rate.'%' }} <span class="text-xs text-slate-400">of {{ money($c->basis_amount) }}</span></td>
                            <td class="whitespace-nowrap text-slate-600">{{ fdate($c->eligibility_date, 'd M Y') }}@if ($c->status === 'pending')<p class="text-xs {{ $c->days_to_eligible <= 0 ? 'text-emerald-600' : 'text-amber-700' }}">{{ $c->days_to_eligible <= 0 ? 'Reached — recalculate' : 'in '.$c->days_to_eligible.' day(s)' }}</p>@endif</td>
                            <td><x-status dim="commission" :value="$c->status" /></td>
                            <td class="whitespace-nowrap text-slate-600">{{ $c->paid_date ? 'Paid '.fdate($c->paid_date, 'd M') : ($c->due_date ? 'Due '.fdate($c->due_date, 'd M') : '—') }}</td>
                            <td class="whitespace-nowrap text-right font-medium">{{ $money($c->current_amount) }}@if (count($c->adjustments))<p class="text-[11px] font-normal text-slate-400">adj. {{ $money(collect($c->adjustments)->sum('delta')) }}</p>@endif</td>
                            @if ($canAct)
                                <td class="whitespace-nowrap text-right"><div class="flex justify-end gap-1.5">
                                    @if ($locked)<span class="tag" title="Manager commissions can be approved only by Admin or a Finance Manager"><x-ico name="lock-closed" class="size-3" /> Finance Manager</span>
                                    @else
                                        @if (isset($next[$c->status]))<form method="POST" action="{{ route('commissions.transition', $c->id) }}">@csrf<input type="hidden" name="to" value="{{ $next[$c->status][0] }}"><button class="btn btn-secondary btn-xs">{{ $next[$c->status][1] }}</button></form>@endif
                                        @if (in_array($c->status, ['approved', 'due']))<button type="button" class="btn btn-success btn-xs" @click="$store.modal.open('pay', { action: '{{ route('commissions.transition', $c->id) }}', id: '{{ $c->id }}', who: '{{ addslashes($c->recipient?->name) }}', amount: '{{ $money($c->current_amount) }}' })">Record payout</button>@endif
                                        @if ($c->status === 'on_hold')<form method="POST" action="{{ route('commissions.transition', $c->id) }}">@csrf<input type="hidden" name="to" value="eligible"><button class="btn btn-secondary btn-xs">Release hold</button></form>@endif
                                        @unless (in_array($c->status, ['reversed']))<button type="button" class="btn btn-ghost btn-xs" @click="$store.modal.open('more', { id: '{{ $c->id }}', status: '{{ $c->status }}', hold: '{{ route('commissions.transition', $c->id) }}', adjust: '{{ route('commissions.adjust', $c->id) }}' })" aria-label="More actions for {{ $c->id }}">More</button>@endunless
                                    @endif
                                </div></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <x-table-footer />
            @endif
        </div>
        @endforeach
    </div>

    <x-slot:modals>
        @perm('commission.create')
        <x-modal name="create" title="{{ $mode === 'manager' ? 'Add team commission' : 'Add commission' }}" description="Creates a pending record. Eligibility follows the configured rule. Amounts are synthetic samples." size="lg">
            <form method="POST" action="{{ route('commissions.store') }}" x-data="{ rules: @js($rules->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'type' => $r->type, 'basis' => $r->basis, 'rate' => $r->rate])->values()), rule: '', type: 'one_time', basis_label: 'First invoice value', basis_amount: '', rate_type: 'percentage', rate: '', touched: false,
                pick() { const r = this.rules.find((x) => x.id === this.rule); if (r) { this.type = r.type; this.basis_label = r.basis; this.rate = r.rate; this.rate_type = 'percentage'; } },
                get amount() { const b = Number(this.basis_amount || 0), r = Number(this.rate || 0); return this.rate_type === 'fixed' ? r : Math.round(b * r) / 100; } }"
                  @submit="touched = true; if (!basis_amount || rate === '' || (rate_type === 'percentage' && rate > 100)) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Opportunity (Closed Won)" name="lead_id" :required="true"><select id="lead_id" name="lead_id" class="select">@foreach ($leads as $l)<option value="{{ $l->id }}">{{ $l->customer->business_name }} — {{ $l->id }}</option>@endforeach</select></x-field>
                    <x-field label="Recipient" name="recipient_id" :required="true"><select id="recipient_id" name="recipient_id" class="select">@foreach ($recipients as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ config('portal.roles.'.$u->role.'.label') }}</option>@endforeach</select></x-field>
                    <x-field label="Start from a sample rule (optional)" name="rule_id" class="sm:col-span-2" help="Rules are SYNTHETIC samples, not policy."><select id="rule_id" name="rule_id" x-model="rule" @change="pick()" class="select"><option value="">— none —</option>@foreach ($rules as $r)<option value="{{ $r->id }}">{{ $r->name }} ({{ $r->rate }}% of {{ strtolower($r->basis) }})</option>@endforeach</select></x-field>
                    <x-field label="Commission type" name="type"><select id="type" name="type" x-model="type" class="select"><option value="one_time">One-time</option><option value="residual">Residual (monthly)</option></select></x-field>
                    <x-field label="Calculation basis" name="basis_label"><input id="basis_label" name="basis_label" x-model="basis_label" class="input"></x-field>
                    <x-field label="Basis amount" name="basis_amount" :required="true"><input id="basis_amount" type="number" min="0" step="0.01" name="basis_amount" x-model="basis_amount" class="input" :class="{ 'input-error': touched && !basis_amount }"><p class="err" x-show="touched && !basis_amount" x-cloak>Enter the basis amount.</p></x-field>
                    <x-field label="Rate type" name="rate_type"><select id="rate_type" name="rate_type" x-model="rate_type" class="select"><option value="percentage">Percentage</option><option value="fixed">Fixed amount</option></select></x-field>
                    <x-field label="Rate / amount" name="rate" :required="true"><input id="rate" type="number" min="0" step="0.01" name="rate" x-model="rate" class="input" :class="{ 'input-error': touched && (rate === '' || (rate_type === 'percentage' && rate > 100)) }"><p class="err" x-show="touched && rate === ''" x-cloak>Enter a rate or amount.</p><p class="err" x-show="rate_type === 'percentage' && rate > 100" x-cloak>A percentage cannot exceed 100.</p></x-field>
                    <div class="flex items-end"><div class="w-full rounded-lg bg-slate-50 p-3 text-sm"><p class="text-xs text-slate-500">Calculated (synthetic)</p><p class="text-lg font-semibold" x-text="'$' + amount.toFixed(2)"></p></div></div>
                    <x-field label="Reason / note" name="reason" class="sm:col-span-2"><input id="reason" name="reason" class="input" placeholder="Optional — logged in the commission ledger"></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Create commission</button></div>
            </form>
        </x-modal>
        @endperm

        @if ($canAct)
        <x-modal name="pay" title="Record payout" description="Cash out is recorded only when a payout is actually paid. No money moves in this prototype.">
            <form method="POST" :action="$store.modal.payload.action">@csrf<input type="hidden" name="to" value="paid">
                <p class="mb-3 text-sm text-slate-600"><span x-text="$store.modal.payload.id"></span> · <strong x-text="$store.modal.payload.who"></strong> · <strong x-text="$store.modal.payload.amount"></strong></p>
                <x-field label="Payout reference" name="payout_ref" help="Leave blank to generate a demo reference."><input id="payout_ref" name="payout_ref" class="input" placeholder="e.g. PAYOUT-2026-0042"></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-success">Mark as paid</button></div></form>
        </x-modal>
        <x-modal name="more" title="More actions" description="Paid commissions are never edited or deleted — use an adjustment or a reversal.">
            <div class="space-y-5" x-data="{ aamt: '', areason: '', rreason: '', touched: false }">
                <form method="POST" :action="$store.modal.payload.adjust" @submit="touched = true; if (!aamt || areason.trim().length < 5) $event.preventDefault()" class="space-y-3 rounded-xl border border-slate-200 p-4">@csrf
                    <p class="text-sm font-semibold">Add adjustment <span class="font-normal text-slate-500">(linked ledger entry)</span></p>
                    <div class="grid gap-3 sm:grid-cols-[130px_1fr]"><div><label class="label" for="aamt">Amount (+/−)</label><input id="aamt" name="delta" type="number" step="0.01" x-model="aamt" class="input" :class="{ 'input-error': touched && !aamt }"></div><div><label class="label" for="areason">Reason</label><input id="areason" name="reason" x-model="areason" class="input" :class="{ 'input-error': touched && areason.trim().length < 5 }"></div></div>
                    <p class="err" x-show="touched && (!aamt || areason.trim().length < 5)" x-cloak>Enter an amount and a reason (min. 5 characters).</p><button class="btn btn-secondary btn-sm">Save adjustment</button></form>
                <form method="POST" :action="$store.modal.payload.hold" class="space-y-3 rounded-xl border border-slate-200 p-4">@csrf
                    <p class="text-sm font-semibold">Hold or reverse</p><div><label class="label" for="rreason">Reason (required)</label><input id="rreason" name="reason" x-model="rreason" class="input"></div>
                    <div class="flex gap-2"><button name="to" value="on_hold" class="btn btn-secondary btn-sm" :disabled="rreason.trim().length < 5" x-show="$store.modal.payload.status !== 'paid'">Put on hold</button><button name="to" value="reversed" class="btn btn-danger btn-sm" :disabled="rreason.trim().length < 5">Reverse</button></div></form>
            </div>
        </x-modal>
        @endif
    </x-slot:modals>
</x-app-layout>
