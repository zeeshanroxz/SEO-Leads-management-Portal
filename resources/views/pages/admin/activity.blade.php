<x-app-layout title="Activity & errors" :crumbs="[['Administration'], ['Activity & errors']]" :subtitle="$scoped ? 'Activity history within your permitted scope.' : 'Append-oriented history of high-impact actions, plus integration error logs.'">
    <div x-data="tabs('trail')">
        <div class="mb-4 flex gap-1 border-b border-slate-200" role="tablist">
            <button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === 'trail' }" @click="set('trail')">Audit trail</button>
            @if ($errors->count())<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === 'errors' }" @click="set('errors')">Integration errors <span class="ml-1 rounded-full bg-red-100 px-1.5 text-[11px] text-red-700">{{ $errors->where('status', 'open')->count() }}</span></button>@endif
        </div>

        <div x-show="tab === 'trail'" class="card" x-data="dataTable({ perPage: 12, sortKey: 'at', sortDir: 'desc', filters: { cat: '' } })">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
                <div class="relative min-w-56 flex-1"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input type="search" x-model="q" class="input pl-9" placeholder="Search actor, action, record, reason…" aria-label="Search audit trail"></div>
                <select x-model="filters.cat" class="select w-44" aria-label="Category"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach</select>
            </div>
            @if ($log->isEmpty())<x-empty-state icon="queue-list" title="No activity in your scope" />@else
            <div class="overflow-x-auto"><table class="tbl tbl-compact">
                <thead><tr><th class="th-sort" @click="sort('at')">When <span x-text="arrow('at')"></span></th><th>Actor</th><th>Action</th><th>Record</th><th>Reason</th><th>Category</th>@unless ($scoped)<th>IP (demo)</th>@endunless</tr></thead>
                <tbody>
                    @foreach ($log as $a)
                        <tr data-row data-search="{{ strtolower($a->actor?->name.' '.$a->action.' '.$a->entity.' '.$a->reason) }}" data-f-cat="{{ $a->category }}" data-s-at="{{ $a->at }}">
                            <td class="whitespace-nowrap text-slate-500" title="{{ fdt($a->at) }}">{{ fdt($a->at) }}</td><td>{{ $a->actor?->name }}</td><td class="font-medium">{{ $a->action }}</td><td>{{ $a->entity }}</td><td class="max-w-xs text-slate-600">{{ $a->reason ?? '—' }}</td><td><span class="tag">{{ $a->category }}</span></td>@unless ($scoped)<td class="font-mono text-xs text-slate-400">{{ $a->ip }}</td>@endunless
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <x-table-footer />
            @endif
        </div>

        @if ($errors->count())
        <div x-show="tab === 'errors'" x-cloak class="card">
            <div class="card-head"><h2 class="card-title">Integration error log</h2><x-demo-tag label="Simulated errors" /></div>
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th>When</th><th>Integration</th><th>Severity</th><th>Message (secrets are never logged)</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($errors as $e)
                        <tr>
                            <td class="whitespace-nowrap text-slate-500">{{ ago($e->at) }}</td><td class="font-medium">{{ $e->integration }}</td>
                            <td><span class="chip {{ ['error' => 'chip-red', 'warning' => 'chip-amber', 'info' => 'chip-slate'][$e->severity] }}">{{ ucfirst($e->severity) }}</span></td>
                            <td class="max-w-md text-slate-600">{{ $e->message }}@if ($e->reference)<span class="tag ml-1">{{ $e->reference }}</span>@endif</td>
                            <td><span class="chip {{ ['open' => 'chip-amber', 'acknowledged' => 'chip-blue', 'resolved' => 'chip-green'][$e->status] }}">{{ ucfirst($e->status) }}</span></td>
                            <td class="text-right">
                                @if ($e->status !== 'resolved')
                                    <form method="POST" action="{{ route('admin.errors.update', $e->id) }}" class="inline-flex gap-1.5">@csrf
                                        @if ($e->status === 'open')<button name="status" value="acknowledged" class="btn btn-secondary btn-xs">Acknowledge</button>@endif
                                        <button name="status" value="resolved" class="btn btn-secondary btn-xs">Retry &amp; resolve</button><input type="hidden" name="retry" value="1">
                                    </form>
                                @else<span class="text-xs text-slate-400">Resolved</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">A failed external sync never deletes an internal record — the portal event or payment record stays safe and can be retried.</p>
        </div>
        @endif
    </div>
</x-app-layout>
