@php
    $scopeText = match (demo()::roleKey()) {
        'admin' => 'All leads across every team.',
        'finance' => 'Closed Won opportunities in Finance scope. Other call-centre notes are not shown.',
        'auditor' => 'Leads with audit or quotation requests assigned to you.',
        'manager' => 'Leads supervised by you and your team.',
        'team_lead' => 'Your own leads and your team\'s opportunities.',
        default => 'Leads you created. Fields you submitted become read-only once a lead is accepted.',
    };
    $showOwner = ! demo()::is('agent');
@endphp
<x-app-layout title="Leads" :crumbs="[['Leads']]" :subtitle="$scopeText">
    <x-slot:actions>
        @perm('leads.create')<a href="{{ route('leads.create') }}" class="btn btn-primary"><x-ico name="plus" class="size-4" /> Create lead</a>@endperm
    </x-slot:actions>

    <div class="card" x-data="dataTable({ perPage: 10, sortKey: 'created', sortDir: 'desc', filters: { review: '', sales: '', project: '', owner: '' } })">
        <div class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="relative min-w-56 flex-1">
                <label for="lead-search" class="sr-only">Search leads</label>
                <x-ico name="magnifying-glass" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                <input id="lead-search" type="search" x-model="q" class="input pl-9" placeholder="Search business, contact, ID…">
            </div>
            @unless (demo()::is('finance'))
                <div><label class="sr-only" for="f-review">Lead review</label>
                    <select id="f-review" x-model="filters.review" class="select w-44"><option value="">All review states</option>@foreach (config('statuses.lead_review.values') as $k => $v)<option value="{{ $k }}">{{ $v[0] }}</option>@endforeach</select></div>
            @endunless
            <div><label class="sr-only" for="f-sales">Sales status</label>
                <select id="f-sales" x-model="filters.sales" class="select w-48"><option value="">All sales statuses</option>@foreach (config('statuses.sales.values') as $k => $v)<option value="{{ $k }}">{{ $v[0] }}</option>@endforeach</select></div>
            <div><label class="sr-only" for="f-project">Project</label>
                <select id="f-project" x-model="filters.project" class="select w-48"><option value="">All projects</option>@foreach ($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
            @if ($showOwner && $owners->count() > 1)
                <div><label class="sr-only" for="f-owner">Owner</label>
                    <select id="f-owner" x-model="filters.owner" class="select w-44"><option value="">All owners</option>@foreach ($owners as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach</select></div>
            @endif
            <button type="button" class="btn btn-ghost btn-sm" x-show="isFiltered" x-cloak @click="reset()">Clear</button>
        </div>

        @if ($state === 'loading')
            <div class="space-y-3 p-5" aria-busy="true" aria-label="Loading leads">@for ($i = 0; $i < 6; $i++)<div class="flex gap-4"><div class="skeleton h-5 w-1/4"></div><div class="skeleton h-5 w-1/6"></div><div class="skeleton h-5 w-1/6"></div><div class="skeleton h-5 w-1/5"></div></div>@endfor</div>
        @elseif ($state === 'error')
            <x-empty-state icon="exclamation-triangle" title="We couldn't load leads" text="This is the error state preview. In production a failed API request would show here with a retry.">
                <a href="{{ route('leads.index') }}" class="btn btn-primary btn-sm">Try again</a>
            </x-empty-state>
        @elseif ($state === 'empty' || $leads->isEmpty())
            <x-empty-state icon="user-group" title="No leads yet" text="{{ demo()::can('leads.create') ? 'Create your first lead to start working your pipeline.' : 'No leads are in your scope right now.' }}">
                @perm('leads.create')<a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm"><x-ico name="plus" class="size-4" /> Create lead</a>@endperm
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr>
                        <th class="th-sort" @click="sort('name')">Business <span x-text="arrow('name')"></span></th>
                        <th>Project</th>
                        @if ($showOwner)<th class="th-sort" @click="sort('owner')">Owner <span x-text="arrow('owner')"></span></th>@endif
                        @unless (demo()::is('finance'))<th>Review</th>@endunless
                        <th>Sales status</th>
                        @if (demo()::is('finance'))<th>Agreement</th><th class="th-sort" @click="sort('won')">Closed Won <span x-text="arrow('won')"></span></th>@else<th class="th-sort" @click="sort('next')">Next action <span x-text="arrow('next')"></span></th>@endif
                        <th class="th-sort" @click="sort('created')">Created <span x-text="arrow('created')"></span></th>
                        <th class="w-10"><span class="sr-only">Open</span></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($leads as $l)
                            <tr data-row data-search="{{ strtolower($l->id.' '.$l->customer->business_name.' '.collect($l->customer->contacts)->pluck('name')->implode(' ').' '.$l->owner?->name.' '.$l->source) }}"
                                data-f-review="{{ $l->review_status }}" data-f-sales="{{ $l->sales_status }}" data-f-project="{{ $l->project_id }}" data-f-owner="{{ $l->owner_id }}"
                                data-s-name="{{ strtolower($l->customer->business_name) }}" data-s-owner="{{ strtolower($l->owner?->name) }}" data-s-created="{{ $l->created_at }}" data-s-next="{{ $l->next_action?->due ?? '9999' }}" data-s-won="{{ $l->closed_won_at }}">
                                <td>
                                    <a href="{{ route('leads.show', $l->id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $l->customer->business_name }}</a>
                                    <p class="text-xs text-slate-500">{{ $l->id }} · {{ $l->customer->city ?? 'Location n/a' }} · {{ $l->source }}</p>
                                </td>
                                <td class="text-slate-600">{{ $l->project->name }}</td>
                                @if ($showOwner)<td><div class="flex items-center gap-2"><x-avatar :name="$l->owner?->name" size="xs" /><span class="text-slate-700">{{ $l->owner?->name }}</span></div></td>@endif
                                @unless (demo()::is('finance'))<td><x-status dim="lead_review" :value="$l->review_status" /></td>@endunless
                                <td>@if ($l->review_status === 'accepted')<x-status dim="sales" :value="$l->sales_status" />@else<span class="text-xs text-slate-400">Not in pipeline</span>@endif</td>
                                @if (demo()::is('finance'))
                                    <td>@if ($l->agreement)<x-status dim="agreement" :value="$l->agreement->status" />@else<span class="chip chip-amber">Needs agreement</span>@endif</td>
                                    <td class="whitespace-nowrap text-slate-600">{{ fdate($l->closed_won_at) }}</td>
                                @else
                                    <td>
                                        @if ($l->next_action)
                                            @php($overdue = \Carbon\Carbon::parse($l->next_action->due)->isPast() && ! \Carbon\Carbon::parse($l->next_action->due)->isToday())
                                            <p class="max-w-52 truncate text-slate-700">{{ $l->next_action->label }}</p>
                                            <p class="text-xs {{ $overdue ? 'font-medium text-red-600' : 'text-slate-500' }}">{{ $overdue ? 'Overdue · ' : 'Due ' }}{{ fdate($l->next_action->due, 'd M') }}</p>
                                        @else<span class="text-slate-400">—</span>@endif
                                    </td>
                                @endif
                                <td class="whitespace-nowrap text-slate-600">{{ fdate($l->created_at) }}</td>
                                <td><a href="{{ route('leads.show', $l->id) }}" class="btn btn-ghost btn-icon" aria-label="Open {{ $l->customer->business_name }}"><x-ico name="chevron-right" class="size-4" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-table-footer />
        @endif
    </div>
    <p class="mt-3 text-xs text-slate-400">Preview UI states: <a class="underline" href="{{ route('leads.index', ['state' => 'loading']) }}">loading</a> · <a class="underline" href="{{ route('leads.index', ['state' => 'empty']) }}">empty</a> · <a class="underline" href="{{ route('leads.index', ['state' => 'error']) }}">error</a> · <a class="underline" href="{{ route('leads.index') }}">normal</a></p>
</x-app-layout>
