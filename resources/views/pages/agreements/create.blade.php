@php
    $cfg = ['lead' => $lead?->id, 'services' => $services, 'clauses' => $clauses, 'contacts' => $contacts, 'leadServices' => $leadServices];
@endphp
<x-app-layout title="New agreement" :crumbs="[['Finance'], ['Agreements', route('agreements.index')], ['New']]" subtitle="Built from a Closed Won opportunity. Prices and clause versions are snapshotted into the agreement.">
    <form method="POST" action="{{ route('agreements.store') }}" x-data="agreementBuilder(@js($cfg))" @submit="submit($event)" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]" novalidate>
        @csrf
        <div class="min-w-0 space-y-5">
            @if ($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="card card-pad">
                <h2 class="card-title mb-4">Opportunity &amp; customer</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Closed Won opportunity" name="lead_id" :required="true" class="sm:col-span-2"><select id="lead_id" name="lead_id" x-model="lead_id" class="select" :class="{ 'input-error': touched && errors.lead_id }"><option value="">Select…</option>@foreach ($pool as $l)<option value="{{ $l->id }}">{{ $l->customer->business_name }} — {{ $l->id }} (closed {{ fdate($l->closed_won_at, 'd M') }})</option>@endforeach</select><p class="err" x-show="touched && errors.lead_id" x-text="errors.lead_id" x-cloak></p></x-field>
                    <x-field label="Customer signer name" name="signer_name" :required="true"><input id="signer_name" name="signer_name" x-model="signer_name" class="input" :class="{ 'input-error': touched && errors.signer_name }"><p class="err" x-show="touched && errors.signer_name" x-text="errors.signer_name" x-cloak></p></x-field>
                    <x-field label="Signer email" name="signer_email" :required="true" help="Receives the secure link and one-time code."><input id="signer_email" name="signer_email" type="email" x-model="signer_email" class="input" :class="{ 'input-error': touched && errors.signer_email }"><p class="err" x-show="touched && errors.signer_email" x-text="errors.signer_email" x-cloak></p></x-field>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Service lines</h2><button type="button" class="btn btn-secondary btn-sm" @click="addLine()"><x-ico name="plus" class="size-4" /> Add line</button></div>
                <div class="space-y-3 p-5">
                    <p class="err" x-show="touched && errors.lines" x-text="errors.lines" x-cloak></p><p class="err" x-show="touched && errors.price" x-text="errors.price" x-cloak></p>
                    <template x-for="(l, i) in lines" :key="i">
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-[minmax(0,1fr)_160px_90px_70px_auto]">
                            <div><label class="label" :for="'ls' + i">Service</label><select :id="'ls' + i" :name="'lines[' + i + '][service_id]'" x-model="l.service_id" @change="changeService(l)" class="select"><template x-for="s in cfg.services" :key="s.id"><option :value="s.id" x-text="s.name"></option></template></select></div>
                            <div><label class="label" :for="'lb' + i">Billing</label><select :id="'lb' + i" :name="'lines[' + i + '][billing]'" x-model="l.billing" class="select"><option value="recurring">Monthly recurring</option><option value="one_time">One-time</option></select></div>
                            <div><label class="label" :for="'lp' + i">Price</label><input :id="'lp' + i" type="number" min="0" step="1" :name="'lines[' + i + '][price]'" x-model="l.price" class="input"></div>
                            <div><label class="label" :for="'lq' + i">Qty</label><input :id="'lq' + i" type="number" min="1" :name="'lines[' + i + '][qty]'" x-model="l.qty" class="input"></div>
                            <div class="flex items-end"><button type="button" class="btn btn-ghost btn-icon text-red-600" @click="removeLine(i)" aria-label="Remove line"><x-ico name="trash" class="size-4" /></button></div>
                        </div>
                    </template>
                    <p class="text-xs text-slate-500"><span class="tag tag-synthetic">Synthetic</span> Default prices are sample values from the services catalogue — edit them per deal.</p>
                </div>
            </div>

            <div class="card card-pad">
                <h2 class="card-title mb-4">Term, billing &amp; renewal</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="Term (months)" name="term_months" :required="true"><input id="term_months" name="term_months" type="number" min="1" max="60" x-model.number="term_months" class="input" :class="{ 'input-error': touched && errors.term }"></x-field>
                    <x-field label="Start date" name="start_date" help="Optional — set on signature if empty."><input id="start_date" name="start_date" type="date" x-model="start_date" class="input"></x-field>
                    <x-field label="Currency" name="currency"><select id="currency" name="currency" x-model="currency" class="select"><option>USD</option><option>GBP</option><option>EUR</option><option>CAD</option><option>AUD</option></select></x-field>
                    <x-field label="Payment schedule" name="payment_schedule" class="sm:col-span-3"><input id="payment_schedule" name="payment_schedule" x-model="payment_schedule" class="input"></x-field>
                    <x-field label="Renewal / expiry rule" name="renewal" class="sm:col-span-3"><textarea id="renewal" name="renewal" x-model="renewal" rows="2" class="textarea"></textarea></x-field>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title">Reusable terms &amp; clauses</h2><span class="text-xs text-slate-500">Service-specific clauses are pre-selected</span></div>
                <ul class="divide-y divide-slate-100">
                    <template x-for="c in cfg.clauses" :key="c.id"><li class="px-5 py-3"><label class="flex items-start gap-3 text-sm"><input type="checkbox" class="check mt-0.5" name="clause_ids[]" :value="c.id" x-model="clause_ids"><span><span class="font-medium" x-text="c.title"></span> <span class="tag ml-1" x-text="c.scope"></span> <span class="tag" x-text="'v' + c.version"></span><span class="mt-0.5 block text-xs text-slate-500" x-text="c.body"></span></span></label></li></template>
                </ul>
            </div>

            <div class="flex justify-end gap-2"><a href="{{ route('finance.handoff') }}" class="btn btn-secondary">Cancel</a><button class="btn btn-primary">Create draft agreement</button></div>
        </div>

        {{-- Live preview --}}
        <aside class="xl:sticky xl:top-20 xl:self-start">
            <div class="card overflow-hidden"><div class="card-head"><h2 class="card-title">Live summary</h2><x-demo-tag label="Preview" /></div>
                <div class="space-y-4 p-5 text-sm">
                    <div class="grid grid-cols-2 gap-3"><div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Recurring / month</p><p class="text-lg font-semibold" x-text="money(mrr)"></p></div><div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">One-time</p><p class="text-lg font-semibold" x-text="money(once)"></p></div></div>
                    <p class="text-xs text-slate-500">Term <strong x-text="term_months + ' months'"></strong>. Contract value is not cash received.</p>
                    <div><p class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Included clauses (<span x-text="clause_ids.length"></span>)</p><ul class="space-y-1"><template x-for="id in clause_ids" :key="id"><li class="flex items-center justify-between rounded bg-slate-50 px-2 py-1 text-xs"><span x-text="clause(id)?.title"></span><span class="text-slate-400" x-text="'v' + clause(id)?.version"></span></li></template></ul></div>
                    <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-900">After creating the draft you can preview the full document, send the secure signing link (simulated) or collect an offline signed copy.</p>
                </div>
            </div>
        </aside>
    </form>
</x-app-layout>
