<x-app-layout title="Closed Won queue" :crumbs="[['Finance'], ['Closed Won queue']]" subtitle="Deals handed over by sales. Closed Won does not mean the agreement is signed or any money has been received.">
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <x-stat label="New — not started" :value="$counts['new'] ?? 0" icon="inbox-arrow-down" tone="amber" />
        <x-stat label="Agreement in progress" :value="$counts['in_progress'] ?? 0" icon="pencil-square" tone="blue" />
        <x-stat label="Handled" :value="$counts['done'] ?? 0" icon="check-circle" tone="green" />
    </div>
    <div class="card" x-data="dataTable({ perPage: 10, sortKey: 'won', sortDir: 'desc', filters: { state: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-56 flex-1"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input type="search" x-model="q" class="input pl-9" placeholder="Search customer, owner, ID…" aria-label="Search queue"></div>
            <select x-model="filters.state" class="select w-48" aria-label="Handoff state"><option value="">All states</option><option value="new">New</option><option value="in_progress">In progress</option><option value="done">Handled</option></select>
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Customer</th><th>Services</th><th>Sold by</th><th class="th-sort" @click="sort('won')">Closed Won <span x-text="arrow('won')"></span></th><th>Handoff</th><th>Agreement</th><th class="text-right">Next step</th></tr></thead>
            <tbody>
                @foreach ($rows as $l)
                    @php($state = $l->handoff?->state ?? 'new')
                    <tr data-row data-search="{{ strtolower($l->customer->business_name.' '.$l->id.' '.$l->owner?->name) }}" data-f-state="{{ $state }}" data-s-won="{{ $l->closed_won_at }}">
                        <td><a href="{{ route('leads.show', $l->id) }}" class="font-medium hover:text-brand-700">{{ $l->customer->business_name }}</a><p class="text-xs text-slate-500">{{ $l->id }} · {{ $l->project->name }}@if ($l->parent_id) · <span class="font-medium text-violet-700">Upsell</span>@endif</p></td>
                        <td class="max-w-56 text-slate-600">{{ collect($l->services)->pluck('name')->implode(', ') }}</td>
                        <td>{{ $l->owner?->name }}<p class="text-xs text-slate-500">TL {{ $l->team_lead?->name }}</p></td>
                        <td class="whitespace-nowrap text-slate-600">{{ fdate($l->closed_won_at) }}<p class="text-xs text-slate-400">{{ $l->days_since }} day(s) ago</p></td>
                        <td><span class="chip {{ ['new' => 'chip-amber', 'in_progress' => 'chip-blue', 'done' => 'chip-green'][$state] }}">{{ ['new' => 'New', 'in_progress' => 'In progress', 'done' => 'Handled'][$state] }}</span>@if ($l->handoff?->assigned_to)<p class="mt-0.5 text-xs text-slate-500">{{ app(\App\Mock\Repositories\DirectoryRepository::class)->userName($l->handoff->assigned_to) }}</p>@endif</td>
                        <td>@if ($l->agreement)<a href="{{ route('agreements.show', $l->agreement->id) }}"><x-status dim="agreement" :value="$l->agreement->status" /></a>@else<span class="chip chip-slate">None yet</span>@endif</td>
                        <td class="text-right"><div class="flex justify-end gap-1.5">
                            @if (! $l->agreement)
                                @if ($state === 'new')<form method="POST" action="{{ route('finance.handoff.claim', $l->id) }}">@csrf<button class="btn btn-secondary btn-xs">Take it</button></form>@endif
                                <a href="{{ route('agreements.create', ['lead' => $l->id]) }}" class="btn btn-primary btn-xs">Build agreement</a>
                            @else<a href="{{ route('agreements.show', $l->agreement->id) }}" class="btn btn-secondary btn-xs">Open agreement</a>@endif
                        </div></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        <x-table-footer />
    </div>
</x-app-layout>
