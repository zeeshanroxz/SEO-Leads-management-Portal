<x-app-layout title="Commission rules" :crumbs="[['Commissions', route('commissions.index')], ['Rules']]" subtitle="Versioned calculation rules. Every rate below is a synthetic sample — nothing here is a real payout policy.">
    <div class="mb-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="card"><div class="card-head"><h2 class="card-title">Rules</h2><span class="tag tag-synthetic">Synthetic sample rates</span></div>
            <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Rule</th><th>Recipient</th><th>Type</th><th>Basis</th><th class="text-right">Rate</th><th>Version</th><th class="text-right"></th></tr></thead><tbody>
                @foreach ($rules as $r)<tr><td class="font-medium">{{ $r->name }}<p class="text-xs font-normal text-slate-500">{{ $r->id }} · effective {{ fdate($r->effective_from) }}</p></td><td>{{ $r->recipient_role }}</td><td><span class="chip {{ $r->type === 'residual' ? 'chip-violet' : 'chip-slate' }}">{{ $r->type === 'residual' ? 'Residual · '.$r->period : 'One-time' }}</span></td><td class="text-slate-600">{{ $r->basis }}</td><td class="text-right font-medium">{{ $r->rate }}%</td><td>v{{ $r->version }}</td>
                    <td class="text-right"><button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.open('rule', { action: '{{ route('commissions.rules.update', $r->id) }}', name: '{{ addslashes($r->name) }}', rate: {{ $r->rate }}, basis: '{{ addslashes($r->basis) }}', v: {{ $r->version }} })">New version…</button></td></tr>@endforeach
            </tbody></table></div>
            <div class="border-t border-slate-100 p-5"><p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Version history</p><ul class="space-y-1.5 text-sm text-slate-600">@foreach ($rules as $r)@foreach (collect($r->changes)->sortByDesc('version')->take(2) as $ch)<li><span class="font-medium">{{ $r->id }} v{{ $ch->version }}</span> — {{ $ch->note }} <span class="text-xs text-slate-400">({{ $dir->userName($ch->by) }}, {{ fdate($ch->at) }})</span></li>@endforeach @endforeach</ul></div>
        </div>
        <form method="POST" action="{{ route('commissions.settings') }}" class="card self-start" x-data="{ days: {{ $days }}, mode: '{{ $dayMode }}' }">@csrf
            <div class="card-head"><h2 class="card-title">Eligibility rule</h2><span class="tag">Assumption</span></div>
            <div class="space-y-4 p-5"><p class="text-sm text-slate-600">Commission becomes eligible <strong>Closed Won + N days</strong>. The prototype uses 15 calendar days; the real policy is still to be decided.</p>
                <div class="grid grid-cols-2 gap-3"><x-field label="Days" name="days"><input id="days" name="days" type="number" min="0" max="365" x-model.number="days" class="input"></x-field><x-field label="Counting" name="mode"><select id="mode" name="mode" x-model="mode" class="select"><option value="calendar">Calendar days</option><option value="business">Business days</option></select></x-field></div>
                <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600"><p class="font-medium text-slate-800">Still to be decided</p><ul class="mt-1 list-disc pl-4"><li>Trigger event and signed-contract prerequisite</li><li>Residual cadence, caps and split credit</li><li>Reversal policy after cancellation</li></ul></div></div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-3"><button class="btn btn-primary btn-sm">Save eligibility rule</button></div>
        </form>
    </div>
    <x-slot:modals>
        <x-modal name="rule" title="Save rule as new version" description="Past commissions keep the rule version they were calculated with.">
            <form method="POST" :action="$store.modal.payload.action" x-data="{ note: '', touched: false }" @submit="touched = true; if (note.trim().length < 5) $event.preventDefault()">@csrf
                <p class="mb-3 text-sm"><strong x-text="$store.modal.payload.name"></strong> <span class="tag" x-text="'current v' + $store.modal.payload.v"></span></p>
                <div class="grid gap-4 sm:grid-cols-2"><x-field label="Rate (%)" name="rate"><input id="rate" name="rate" type="number" step="0.01" min="0" max="100" class="input" x-effect="$el.value = $store.modal.payload.rate"></x-field><x-field label="Basis" name="basis"><input id="basis" name="basis" class="input" x-effect="$el.value = $store.modal.payload.basis"></x-field>
                    <x-field label="Change note" name="change_note" :required="true" class="sm:col-span-2"><input id="change_note" name="change_note" x-model="note" class="input" :class="{ 'input-error': touched && note.trim().length < 5 }"><p class="err" x-show="touched && note.trim().length < 5" x-cloak>Add a change note (min. 5 characters).</p></x-field></div>
                <p class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-900">Do not enter real payout rates — this prototype uses synthetic sample figures only.</p>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Save new version</button></div></form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
