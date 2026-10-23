<x-app-layout title="Settings & assumptions" :crumbs="[['Administration'], ['Settings & assumptions']]" subtitle="Prototype defaults and the register of business decisions still to be confirmed. Nothing here is real policy.">
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4 text-sm text-fuchsia-900">
        <x-ico name="beaker" class="mt-0.5 size-5" />
        <div><p class="font-semibold">Phase gate: backend not started</p><p class="mt-0.5">This is the Phase 1 frontend prototype. The backend (database, real auth, email, Square, Google/Zoom) is built only after you reply with an explicit approval such as “YES — BUILD THE BACKEND”.</p></div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Assumption register</h2><span class="tag">{{ count($assumptions) }} items</span></div>
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th class="w-44">Area</th><th>Prototype default</th><th>Must be decided before production</th></tr></thead>
                <tbody>@foreach ($assumptions as [$area, $default, $decide])<tr><td class="font-medium align-top">{{ $area }}</td><td class="align-top text-slate-700">{{ $default }}</td><td class="align-top text-slate-600">{{ $decide }}</td></tr>@endforeach</tbody>
            </table></div>
        </div>

        <form method="POST" action="{{ route('admin.settings.save') }}" class="card self-start">
            @csrf
            <div class="card-head"><h2 class="card-title">Business rule settings</h2><span class="tag">Prototype</span></div>
            <div class="space-y-4 p-5">
                <div class="grid grid-cols-2 gap-3">
                    <x-field label="Commission eligibility (days)" name="days" help="After Closed Won. Prototype default: 15."><input id="days" type="number" min="0" max="365" name="days" class="input" value="{{ $values['days'] }}"></x-field>
                    <x-field label="Day counting" name="mode" help="Undecided policy."><select id="mode" name="mode" class="select"><option value="calendar" @selected($values['mode'] === 'calendar')>Calendar days</option><option value="business" @selected($values['mode'] === 'business')>Business days</option></select></x-field>
                </div>
                <x-field label="Reversal policy after cancellation" name="reversal"><select id="reversal" name="reversal" class="select"><option value="manual_review" @selected($values['reversal'] === 'manual_review')>Manual review by Finance</option><option value="auto_reverse" @selected($values['reversal'] === 'auto_reverse')>Auto-reverse unpaid</option><option value="no_reversal" @selected($values['reversal'] === 'no_reversal')>No reversal</option></select></x-field>
                <x-field label="Display currency" name="currency"><select id="currency" name="currency" class="select">@foreach (['USD', 'GBP', 'EUR', 'CAD', 'AUD'] as $c)<option @selected($values['currency'] === $c)>{{ $c }}</option>@endforeach</select></x-field>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="check mt-0.5" name="tl_review_team" value="1" @checked($values['tl_review_team'])> <span><span class="font-medium">Team Leads may review their agents' leads</span><span class="block text-xs text-slate-500">Off by default — Team Leads can always accept their own leads.</span></span></label>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="check mt-0.5" name="send_policy" value="1" @checked($values['send_policy'])> <span><span class="font-medium">Agents may send approved audits to clients</span><span class="block text-xs text-slate-500">"If allowed by policy" in the permission baseline.</span></span></label>
                <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600"><p class="font-medium text-slate-800">Fixed in this prototype</p><ul class="mt-1 list-disc pl-4"><li>Duplicate creation is blocked; Admin override needs a reason.</li><li>Submitted lead fields are read-only for Agents after acceptance.</li><li>Finance Manager = extra permission set inside the Finance role.</li></ul></div>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3"><button type="button" class="btn btn-secondary btn-sm" @click="$store.modal.confirm({ title: 'Reset all demo data?', body: 'Removes everything you created in this session and restores the original mock records.', action: '{{ route('demo.reset') }}', confirm: 'Reset demo data', danger: true })">Reset demo data</button><button class="btn btn-primary btn-sm">Save settings</button></div>
        </form>
    </div>
</x-app-layout>
