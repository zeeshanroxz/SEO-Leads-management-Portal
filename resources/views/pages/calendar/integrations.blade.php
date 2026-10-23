@php
    $providers = [
        'google' => ['Google Calendar', 'calendar-days', 'Portal events are pushed to your Google Calendar. One-way (portal → Google) by default; two-way is planned.', ['See and edit events on all your calendars (calendar.events)', 'See your calendar list (calendar.readonly)']],
        'zoom' => ['Zoom', 'video-camera', 'Create Zoom meeting links for portal events and keep them in sync. Scope depends on Zoom\'s supported APIs — to be confirmed in Phase 2.', ['View and manage your meetings (meeting:write)', 'View your user profile (user:read)']],
    ];
@endphp
<x-app-layout title="Calendar connections" :crumbs="[['Calendar', route('calendar.index')], ['Connections']]" subtitle="Optional per-user connections. The portal calendar keeps working when a sync fails.">
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4 text-sm text-fuchsia-900">
        <x-ico name="beaker" class="mt-0.5 size-5" />
        <p><strong>Everything on this page is simulated.</strong> No Google or Zoom account is contacted, no OAuth consent is requested and no tokens exist. “One click” in production means starting the provider's consent flow, then saving the connection you approved.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($providers as $key => [$label, $icon, $blurb, $scopes])
            @php($state = $conn->{$key} ?? 'not_connected')
            <div class="card">
                <div class="card-head"><div class="flex items-center gap-3"><span class="flex size-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600"><x-ico :name="$icon" class="size-6" /></span><div><h2 class="card-title">{{ $label }}</h2><p class="text-xs text-slate-500">Your personal connection</p></div></div>
                    <x-status dim="misc" :value="$state" /></div>
                <div class="space-y-4 p-5 text-sm">
                    <p class="text-slate-600">{{ $blurb }}</p>
                    <dl class="dl"><div><dt>Direction</dt><dd>Portal → {{ $label }} (one-way)</dd></div><div><dt>Last sync</dt><dd>{{ ($conn->last_sync ?? null) ? ago($conn->last_sync) : '—' }}</dd></div></dl>
                    @if ($state === 'failed')
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-red-800"><p class="font-semibold">Sync failed</p><p class="mt-0.5">{{ $conn->error ?? 'The provider rejected the request (simulated).' }}</p><p class="mt-1 text-xs">Your portal events are safe — nothing was deleted.</p></div>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        @if ($state === 'connected')
                            <form method="POST" action="{{ route('integrations.calendar.update') }}">@csrf<input type="hidden" name="provider" value="{{ $key }}"><input type="hidden" name="op" value="sync"><button class="btn btn-secondary"><x-ico name="arrow-path" class="size-4" /> Sync now</button></form>
                            <button type="button" class="btn btn-secondary text-red-700" @click="$store.modal.confirm({ title: 'Disconnect {{ $label }}?', body: 'Future events stop syncing. Existing portal events are unaffected.', action: '{{ route('integrations.calendar.update') }}', fields: { provider: '{{ $key }}', op: 'disconnect' }, confirm: 'Disconnect', danger: true })">Disconnect</button>
                        @elseif ($state === 'failed')
                            <form method="POST" action="{{ route('integrations.calendar.update') }}">@csrf<input type="hidden" name="provider" value="{{ $key }}"><input type="hidden" name="op" value="reconnect"><button class="btn btn-primary">Reconnect</button></form>
                        @else
                            <button type="button" class="btn btn-primary" @click="$store.modal.open('consent-{{ $key }}')">Connect {{ $label }}</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($team->count())
        <div class="card mt-6"><div class="card-head"><h2 class="card-title">Team connection status (Admin)</h2><x-demo-tag label="Simulated" /></div>
            <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>User</th><th>Google Calendar</th><th>Zoom</th><th>Last sync</th><th>Error</th></tr></thead><tbody>
                @foreach ($team as $c)<tr><td class="font-medium">{{ $c->user?->name }}</td><td><x-status dim="misc" :value="$c->google" /></td><td><x-status dim="misc" :value="$c->zoom" /></td><td class="text-slate-500">{{ ($c->last_sync ?? null) ? ago($c->last_sync) : '—' }}</td><td class="max-w-xs text-xs text-red-700">{{ $c->error ?? '' }}</td></tr>@endforeach
            </tbody></table></div></div>
    @endif

    <div class="card mt-6 card-pad text-sm text-slate-600"><p class="font-medium text-slate-900">How sync will work (Phase 2 design)</p>
        <ul class="mt-2 list-disc space-y-1 pl-5"><li>Portal → provider first; provider event IDs are stored so a retry never creates duplicates.</li><li>Sync status, last sync time, retries and disconnect are visible per user.</li><li>A failed sync never deletes or loses the internal portal event.</li></ul></div>

    <x-slot:modals>
        @foreach ($providers as $key => [$label, $icon, $blurb, $scopes])
            <x-modal :name="'consent-'.$key" :title="'Connect '.$label" description="Simulated consent screen — nothing is sent to the provider.">
                <form method="POST" action="{{ route('integrations.calendar.update') }}">@csrf<input type="hidden" name="provider" value="{{ $key }}"><input type="hidden" name="op" value="connect">
                    <div class="rounded-xl border border-slate-200 p-4 text-sm"><p class="font-semibold">Call Centre Portal wants to access your {{ $label }}:</p><ul class="mt-2 space-y-1.5">@foreach ($scopes as $s)<li class="flex gap-2"><x-ico name="check-circle" class="mt-0.5 size-4 text-emerald-500" /> {{ $s }}</li>@endforeach</ul></div>
                    <p class="mt-3 text-xs text-slate-500">Demo only: choosing “Allow” just flips this prototype's connection state.</p>
                    <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Deny</button><button class="btn btn-primary">Allow (demo)</button></div>
                </form>
            </x-modal>
        @endforeach
    </x-slot:modals>
</x-app-layout>
