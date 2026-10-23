<x-app-layout title="Audits & quotes" :crumbs="[['Audits & quotes']]" :subtitle="$mine ? 'Requests assigned to you. Each request is its own record with versions, files and notes.' : 'Audit, guidance and quotation requests in your scope. An opportunity can have many over time.'">
    <div class="card" x-data="dataTable({ perPage: 10, sortKey: 'due', sortDir: 'asc', filters: { type: '', status: '', priority: '' } })">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-56 flex-1"><label for="a-search" class="sr-only">Search requests</label><x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" /><input id="a-search" type="search" x-model="q" class="input pl-9" placeholder="Search customer, request ID, notes…"></div>
            <select x-model="filters.type" class="select w-40" aria-label="Type"><option value="">All types</option><option value="audit">Audit</option><option value="guidance">Guidance</option><option value="quotation">Quotation</option></select>
            <select x-model="filters.status" class="select w-48" aria-label="Status"><option value="">All statuses</option>@foreach (config('statuses.audit.values') as $k => $v)<option value="{{ $k }}">{{ $v[0] }}</option>@endforeach</select>
            <select x-model="filters.priority" class="select w-36" aria-label="Priority"><option value="">Any priority</option><option value="high">High</option><option value="normal">Normal</option><option value="low">Low</option></select>
        </div>
        @if ($state === 'empty' || $rows->isEmpty())
            <x-empty-state icon="document-magnifying-glass" title="No requests in your scope" text="Request an audit, guidance or quotation from an accepted lead." />
        @else
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Request</th><th>Customer</th>@unless ($mine)<th>Requested by</th>@endunless<th>Assigned to</th><th>Priority</th><th>Status</th><th class="text-right">Versions</th><th class="th-sort" @click="sort('due')">Due <span x-text="arrow('due')"></span></th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    @php($late = ! in_array($r->status, ['sent', 'approved', 'cancelled']) && \Carbon\Carbon::parse($r->due_date)->isPast())
                    <tr data-row data-search="{{ strtolower($r->id.' '.$r->customer?->business_name.' '.$r->notes.' '.$r->type) }}" data-f-type="{{ $r->type }}" data-f-status="{{ $r->status }}" data-f-priority="{{ $r->priority }}" data-s-due="{{ $r->due_date }}">
                        <td><a href="{{ route('audits.show', $r->id) }}" class="font-medium hover:text-brand-700">{{ ucfirst($r->type) }}</a><p class="text-xs text-slate-500">{{ $r->id }} · {{ $r->lead_id }}</p></td>
                        <td>{{ $r->customer?->business_name }}</td>
                        @unless ($mine)<td>{{ $r->requester?->name }}</td>@endunless
                        <td>@if ($r->auditor){{ $r->auditor->name }}@else<span class="chip chip-amber">Unassigned</span>@endif</td>
                        <td><span class="chip {{ ['high' => 'chip-red', 'normal' => 'chip-slate', 'low' => 'chip-stone'][$r->priority] }}">{{ ucfirst($r->priority) }}</span></td>
                        <td><x-status dim="audit" :value="$r->status" /></td>
                        <td class="text-right">{{ $r->version_count }}</td>
                        <td class="whitespace-nowrap {{ $late ? 'font-medium text-red-600' : 'text-slate-600' }}">{{ $late ? 'Overdue · ' : '' }}{{ fdate($r->due_date, 'd M') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        <x-table-footer />
        @endif
    </div>
</x-app-layout>
