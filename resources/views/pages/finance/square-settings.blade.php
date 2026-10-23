<x-app-layout title="Square settings" :crumbs="[['Administration'], ['Square settings']]" subtitle="Connection mode, masked credentials and a test-connection simulation. This prototype never accepts a real API key.">
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4 text-sm text-fuchsia-900"><x-ico name="shield-exclamation" class="mt-0.5 size-5" /><div><p class="font-semibold">Do not enter real credentials anywhere in this prototype.</p><p class="mt-0.5">API keys and webhook secrets will only ever be entered on a server-side settings screen in Phase 2 and stored encrypted — never in the browser, the frontend bundle or the repository. The values below are fake, masked placeholders.</p></div></div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <form method="POST" action="{{ route('integrations.square.save') }}" class="card" x-data="{ env: '{{ $square->environment }}' }">
            @csrf
            <div class="card-head"><h2 class="card-title">Connection</h2><x-status dim="misc" :value="$square->status" /></div>
            <div class="space-y-5 p-5">
                <div><p class="label">Environment</p>
                    <div class="inline-flex rounded-lg border border-slate-300 bg-white p-0.5"><label class="cursor-pointer rounded-md px-4 py-1.5 text-sm font-medium" :class="env === 'sandbox' ? 'bg-amber-500 text-white' : 'text-slate-600'"><input type="radio" name="environment" value="sandbox" x-model="env" class="sr-only"> Sandbox</label><label class="cursor-pointer rounded-md px-4 py-1.5 text-sm font-medium" :class="env === 'live' ? 'bg-red-600 text-white' : 'text-slate-600'"><input type="radio" name="environment" value="live" x-model="env" class="sr-only"> Live</label></div>
                    <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900" x-show="env === 'live'" x-cloak><label class="flex items-start gap-2"><input type="checkbox" name="confirm_live" value="1" class="check mt-0.5"> I understand live mode would process real money. (Mockup — nothing is connected.)</label></div>
                    @error('environment')<p class="err">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Application ID</label><input class="input bg-slate-50 font-mono text-xs" value="{{ $square->application_id }}" readonly aria-readonly="true"></div>
                    <div><label class="label">Access token</label><div class="flex gap-2"><input class="input bg-slate-50 font-mono text-xs" value="{{ $square->access_token_masked }}" readonly aria-readonly="true"><button type="submit" name="action" value="rotate" class="btn btn-secondary shrink-0">Rotate (demo)</button></div><p class="help">Masked. Rotated {{ ago($square->token_rotated_at) }} (simulated).</p></div>
                    <div><label class="label">Webhook signature key</label><input class="input bg-slate-50 font-mono text-xs" value="{{ $square->webhook_signature_key_masked }}" readonly aria-readonly="true"></div>
                    <div><label class="label" for="location_id">Location</label><select id="location_id" name="location_id" class="select"><option value="LOC-DEMO-0001" selected>LOC-DEMO-0001 · Demo Location</option><option value="LOC-DEMO-0002">LOC-DEMO-0002 · Demo Warehouse</option></select></div>
                    <div class="sm:col-span-2"><label class="label">Webhook URL</label><div class="flex gap-2" x-data="copyText('{{ $square->webhook_url }}')"><input class="input bg-slate-50 text-xs" value="{{ $square->webhook_url }}" readonly aria-readonly="true"><button type="button" class="btn btn-secondary shrink-0" @click="copy()" x-text="done ? 'Copied' : 'Copy'"></button></div><p class="help">Register this endpoint in the Square developer dashboard (Phase 2 owner task).</p></div>
                </div>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3"><span class="text-xs text-slate-500">Account: {{ $square->account }} · {{ $square->currency }}</span><button class="btn btn-primary">Save settings</button></div>
        </form>

        <div class="space-y-5">
            <div class="card"><div class="card-head"><h2 class="card-title">Test connection</h2><x-demo-tag /></div><div class="space-y-3 p-5 text-sm">
                <p class="text-slate-600">Last test: <strong>{{ ago($square->last_test_at) }}</strong> — <span class="chip chip-green">Passed</span></p>
                <form method="POST" action="{{ route('integrations.square.test') }}">@csrf<button class="btn btn-secondary w-full"><x-ico name="signal" class="size-4" /> Test connection (simulated)</button></form>
                <p class="text-xs text-slate-500">Returns a canned result. No network request is made.</p></div></div>
            <div class="card"><div class="card-head"><h2 class="card-title">Integration log</h2></div><ul class="divide-y divide-slate-100 text-sm">@forelse ($log as $e)<li class="px-5 py-3"><p class="text-slate-800">{{ $e->message }}</p><p class="text-xs text-slate-400">{{ ago($e->at) }} · {{ ucfirst($e->status) }}</p></li>@empty<li class="px-5 py-4 text-slate-500">No log entries.</li>@endforelse</ul></div>
            <div class="card card-pad text-sm text-slate-600"><p class="font-medium text-slate-900">Owner checklist for Phase 2</p><ul class="mt-2 list-disc space-y-1 pl-5"><li>Create the Square application and sandbox/live credentials.</li><li>Choose the location, currency and subscription plans.</li><li>Register the webhook URL and note the signature key.</li><li>Confirm refund/dispute handling and supported countries.</li></ul></div>
        </div>
    </div>
</x-app-layout>
