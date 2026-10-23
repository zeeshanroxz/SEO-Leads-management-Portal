@php
    $signed = $a->status === 'signed';
    $final = in_array($a->status, ['signed', 'expired', 'cancelled', 'superseded', 'rejected']);
    $off = $a->offline_upload;
    $tabs = ['overview' => 'Overview', 'document' => 'Document', 'evidence' => 'Signing & evidence', 'versions' => 'Versions & history'];
@endphp
<x-app-layout :title="$a->id.' · '.$customer?->business_name" :crumbs="[['Finance'], ['Agreements', route('agreements.index')], [$a->id]]">
    <x-slot:actions>
        <x-status dim="agreement" :value="$a->status" class="!text-sm" />
        @if ($canManage)
            @if ($a->status === 'draft')<form method="POST" action="{{ route('agreements.status', $a->id) }}">@csrf<input type="hidden" name="to" value="ready_to_send"><button class="btn btn-secondary">Mark ready to send</button></form>@endif
            @if (in_array($a->status, ['draft', 'ready_to_send']))<form method="POST" action="{{ route('agreements.status', $a->id) }}">@csrf<input type="hidden" name="to" value="sent"><button class="btn btn-primary"><x-ico name="paper-airplane" class="size-4" /> Send for signature (demo)</button></form>@endif
            @if (in_array($a->status, ['sent', 'viewed', 'partially_signed']))
                <a href="{{ $signUrl }}" target="_blank" class="btn btn-secondary"><x-ico name="arrow-top-right-on-square" class="size-4" /> Customer signing page (demo)</a>
                @if ($a->status === 'sent')<form method="POST" action="{{ route('agreements.status', $a->id) }}">@csrf<input type="hidden" name="to" value="viewed"><button class="btn btn-ghost">Simulate “viewed”</button></form>@endif
                @if (! $a->signers[1]->signed_at && $a->signers[0]->signed_at)<form method="POST" action="{{ route('agreements.status', $a->id) }}">@csrf<input type="hidden" name="to" value="countersign"><button class="btn btn-primary">Counter-sign</button></form>@endif
            @endif
            @if ($a->status === 'expired')<button type="button" class="btn btn-secondary" @click="$store.modal.confirm({ title: 'Request to reopen {{ $a->id }}?', body: 'Reopening an expired agreement needs Admin approval and a reason. It then follows the new-opportunity / new-agreement process.', action: '{{ route('agreements.status', $a->id) }}', fields: { to: 'reopen_request' }, confirm: '{{ demo()::is('admin') ? 'Approve reopen' : 'Request reopen' }}', reason: true })">{{ demo()::is('admin') ? 'Approve reopen' : 'Request reopen' }}</button>@endif
            @if (! $final)<button type="button" class="btn btn-secondary text-red-700" @click="$store.modal.confirm({ title: 'Cancel {{ $a->id }}?', body: 'The agreement is marked Cancelled. Its history and any signatures are kept.', action: '{{ route('agreements.status', $a->id) }}', fields: { to: 'cancelled' }, confirm: 'Cancel agreement', danger: true, reason: true })">Cancel</button>@endif
            @if (in_array($a->status, ['draft', 'ready_to_send', 'sent', 'viewed', 'partially_signed']))<button type="button" class="btn btn-ghost" @click="$store.modal.confirm({ title: 'Create a revised version?', body: 'Creates a new draft and marks this agreement Superseded. Nothing is overwritten.', action: '{{ route('agreements.status', $a->id) }}', fields: { to: 'revise' }, confirm: 'Create revision' })">Revise</button>@endif
        @endif
        @if ($signed)<button type="button" class="btn btn-secondary" @click="$store.toast.push('Final PDF download is simulated - no PDF is generated in Phase 1.', 'info', { demo: true })"><x-ico name="arrow-down-tray" class="size-4" /> Final PDF (demo)</button>@endif
    </x-slot:actions>

    @if ($errors->has('reason'))<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first('reason') }}</div>@endif

    <div x-data="tabs('overview')">
        <div class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">@foreach ($tabs as $k => $l)<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $k }}' }" @click="set('{{ $k }}')">{{ $l }}</button>@endforeach</div>

        {{-- OVERVIEW --}}
        <div x-show="tab === 'overview'" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-5">
                <div class="card"><div class="card-head"><h2 class="card-title">Service lines</h2><span class="text-xs text-slate-500">Prices are a snapshot taken when the version was created</span></div>
                    <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Service</th><th>Billing model</th><th class="text-right">Qty</th><th class="text-right">Price</th></tr></thead><tbody>
                        @foreach ($a->lines as $l)<tr><td class="font-medium">{{ $l->name }}</td><td><span class="chip {{ $l->billing === 'recurring' ? 'chip-blue' : 'chip-slate' }}">{{ $l->billing === 'recurring' ? 'Recurring (MRR/MRC)' : 'One-time' }}</span></td><td class="text-right">{{ $l->qty }}</td><td class="text-right font-medium">{{ money($l->price, $a->currency) }}</td></tr>@endforeach
                    </tbody></table></div></div>
                <div class="card"><div class="card-head"><h2 class="card-title">Terms</h2></div><dl class="dl p-5"><div><dt>Term</dt><dd>{{ $a->term_months }} months</dd></div><div><dt>Start → end</dt><dd>{{ $a->start_date ? fdate($a->start_date).' → '.fdate($a->end_date) : 'Set on signature' }}</dd></div><div class="sm:col-span-2"><dt>Renewal</dt><dd>{{ $a->renewal }}</dd></div><div class="sm:col-span-2"><dt>Payment schedule</dt><dd>{{ $a->payment_schedule }}</dd></div></dl></div>
                <div class="card"><div class="card-head"><h2 class="card-title">Clauses used (snapshot)</h2></div><ul class="divide-y divide-slate-100 text-sm">@foreach ($a->clauses as $c)<li class="flex items-center justify-between px-5 py-2.5"><span class="font-medium">{{ $c->title }}</span><span class="tag">v{{ $c->version }}</span></li>@endforeach</ul><p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">Editing a reusable clause later never changes an existing agreement.</p></div>
            </div>
            <aside class="space-y-5">
                <div class="card card-pad"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Totals</p><p class="mt-2 text-2xl font-semibold">{{ money($a->mrr, $a->currency) }}<span class="text-sm font-normal text-slate-500"> / month</span></p><p class="text-sm text-slate-600">+ {{ money($a->one_time_total, $a->currency) }} one-time</p>
                    <p class="mt-3 text-xs text-slate-500">Contract value is not cash received — see Billing & Square.</p></div>
                <div class="card"><div class="card-head"><h3 class="card-title">Customer</h3></div><div class="p-5 text-sm"><a href="{{ route('customers.show', $customer->id) }}" class="font-semibold text-brand-700 hover:underline">{{ $customer->business_name }}</a><p class="mt-1 text-slate-500">{{ $a->lead_id }} · closed won {{ fdate($lead?->closed_won_at) }}</p><p class="mt-2 text-xs text-slate-500">Billing status</p><x-status dim="billing" :value="$a->billing_status" /></div></div>
                @if ($siblings->count())<div class="card"><div class="card-head"><h3 class="card-title">Other agreements for this customer</h3></div><ul class="divide-y divide-slate-100 text-sm">@foreach ($siblings as $s)<li><a href="{{ route('agreements.show', $s->id) }}" class="flex items-center justify-between px-5 py-2.5 hover:bg-slate-50"><span>{{ $s->id }}</span><x-status dim="agreement" :value="$s->status" /></a></li>@endforeach</ul></div>@endif
            </aside>
        </div>

        {{-- DOCUMENT --}}
        <div x-show="tab === 'document'" x-cloak class="mx-auto max-w-3xl"><x-agreement-doc :a="$a" /></div>

        {{-- EVIDENCE --}}
        <div x-show="tab === 'evidence'" x-cloak class="grid gap-5 xl:grid-cols-2">
            <div class="card"><div class="card-head"><h2 class="card-title">Signers</h2><x-demo-tag label="Demo evidence" /></div>
                <ul class="divide-y divide-slate-100 text-sm">@foreach ($a->signers as $s)<li class="px-5 py-3"><div class="flex items-center justify-between gap-3"><div><p class="font-medium">{{ $s->name }}</p><p class="text-xs text-slate-500">{{ $s->role }} · {{ $s->email }}</p></div>@if ($s->signed_at)<span class="chip chip-green">Signed</span>@else<span class="chip chip-slate">Pending</span>@endif</div>
                    @if ($s->signed_at)<dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600"><dt class="text-slate-400">Signed at</dt><dd>{{ fdt($s->signed_at) }}</dd><dt class="text-slate-400">Method</dt><dd>{{ ucfirst($s->method) }}</dd><dt class="text-slate-400">Consent</dt><dd>{{ $s->consent ? 'Acknowledged' : '—' }}</dd><dt class="text-slate-400">Verification</dt><dd>{{ $s->verification }}</dd></dl>@endif</li>@endforeach</ul>
                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500"><p>Document integrity reference: <span class="font-mono">{{ $a->hash ?? 'Generated when the final version is locked' }}</span></p><p class="mt-1">Native signing is a prototype. Legal/compliance review (jurisdiction, identity proof, consent wording, retention) is required before production. Enforceability is not implied.</p></div></div>

            <div class="space-y-5">
                @if ($signed)<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><p class="font-semibold">Final version locked</p><p class="mt-0.5">Version {{ $a->version }} is the immutable signed record. Further changes require a new agreement or an explicit amendment.</p></div>@endif
                <div class="card"><div class="card-head"><h2 class="card-title">Offline signed copy</h2></div>
                    <div class="space-y-3 p-5 text-sm">
                        <p class="text-slate-600">If the customer signs on paper, they return a signed PDF or image. Finance/Admin uploads it here and reviews it before the agreement is marked signed.</p>
                        @if ($off)
                            <div class="rounded-lg border border-slate-200 p-3"><div class="flex items-center justify-between gap-2"><p class="flex items-center gap-2 font-medium"><x-ico name="paper-clip" class="size-4 text-slate-400" /> {{ $off->name }}</p><span class="chip {{ ['unreviewed' => 'chip-amber', 'approved' => 'chip-green', 'rejected' => 'chip-red'][$off->review] }}">{{ ['unreviewed' => 'Awaiting review', 'approved' => 'Accepted', 'rejected' => 'Rejected'][$off->review] }}</span></div><p class="mt-1 text-xs text-slate-500">Uploaded {{ ago($off->at) }} (demo — no file stored)</p>@if ($off->note ?? false)<p class="mt-1 text-xs text-slate-600">Note: {{ $off->note }}</p>@endif</div>
                            @if ($off->review === 'unreviewed' && $canManage)
                                <div class="flex gap-2"><form method="POST" action="{{ route('agreements.offline.review', $a->id) }}">@csrf<input type="hidden" name="decision" value="approve"><button class="btn btn-success btn-sm">Accept &amp; mark signed</button></form>
                                    <button type="button" class="btn btn-secondary btn-sm text-red-700" @click="$store.modal.open('offline-reject')">Reject</button></div>
                            @endif
                        @endif
                        @if ($canManage && ! $signed && ! $final)
                            <form method="POST" action="{{ route('agreements.offline', $a->id) }}" x-data="{ file: '', touched: false }" @submit="touched = true; if (!file) $event.preventDefault()">@csrf
                                <input type="hidden" name="file_name" :value="file"><label class="label" for="offline">Upload signed copy (PDF / image)</label>
                                <input id="offline" type="file" accept=".pdf,image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium" @change="file = $event.target.files[0]?.name ?? ''"><p class="err" x-show="touched && !file" x-cloak>Choose a file first.</p>
                                <button class="btn btn-secondary btn-sm mt-3">Record upload (demo)</button></form>
                        @endif
                    </div></div>
            </div>
        </div>

        {{-- VERSIONS --}}
        <div x-show="tab === 'versions'" x-cloak class="grid gap-5 xl:grid-cols-2">
            <div class="card"><div class="card-head"><h2 class="card-title">Version history</h2></div><ul class="divide-y divide-slate-100 text-sm">@foreach (collect($a->versions)->sortByDesc('no') as $v)<li class="px-5 py-3"><div class="flex items-center justify-between"><p class="font-medium">Version {{ $v->no }}</p><x-status dim="agreement" :value="$v->status" /></div><p class="text-xs text-slate-500">{{ fdt($v->at) }} · {{ $v->note }}</p></li>@endforeach</ul></div>
            <div class="card"><div class="card-head"><h2 class="card-title">Event log</h2></div><ol class="space-y-3 p-5 text-sm">@foreach (collect($a->events)->sortByDesc('at') as $e)<li class="flex gap-3"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-400"></span><div><p>{{ $e->text }}</p><p class="text-xs text-slate-400">{{ $e->by }} · {{ fdt($e->at) }}</p></div></li>@endforeach</ol></div>
        </div>
    </div>

    <x-slot:modals>
        <x-modal name="offline-reject" title="Reject signed copy" description="The customer will need to send a corrected copy.">
            <form method="POST" action="{{ route('agreements.offline.review', $a->id) }}" x-data="{ note: '', touched: false }" @submit="touched = true; if (note.trim().length < 5) $event.preventDefault()">@csrf<input type="hidden" name="decision" value="reject">
                <x-field label="Reason" name="note" :required="true"><textarea id="note" name="note" x-model="note" rows="3" class="textarea" :class="{ 'input-error': touched && note.trim().length < 5 }"></textarea><p class="err" x-show="touched && note.trim().length < 5" x-cloak>Explain the problem (min. 5 characters).</p></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-danger">Reject copy</button></div></form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
