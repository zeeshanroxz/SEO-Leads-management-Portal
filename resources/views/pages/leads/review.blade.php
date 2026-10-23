<x-app-layout title="Lead review queue" :crumbs="[['Leads', route('leads.index')], ['Review queue']]" subtitle="Accept or reject submitted leads. A rejection always needs a reason and a note, visible to the Agent.">
    @if (demo()::is('team_lead'))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900">
            <x-ico name="information-circle" class="mt-0.5 size-5" />
            <p>Team Leads can accept <strong>their own</strong> leads. Reviewing your agents' leads is {{ $tlTeamReview ? 'enabled' : 'off' }} by Admin policy (prototype default: off — see Settings & assumptions).</p>
        </div>
    @endif

    <div class="card" x-data="dataTable({ perPage: 10, sortKey: 'submitted', sortDir: 'asc' })">
        <div class="card-head"><h2 class="card-title">Pending review <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $queue->count() }}</span></h2>
            <div class="relative"><input type="search" x-model="q" class="input h-8 w-60 pl-8 text-[13px]" placeholder="Search queue…" aria-label="Search review queue"><x-ico name="magnifying-glass" class="pointer-events-none absolute left-2.5 top-2 size-4 text-slate-400" /></div></div>
        @if ($queue->isEmpty())
            <x-empty-state icon="clipboard-document-check" title="Nothing waiting for review" text="New submissions from your scope will appear here." />
        @else
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th>Business</th><th>Project</th><th>Submitted by</th><th class="th-sort" @click="sort('submitted')">Submitted <span x-text="arrow('submitted')"></span></th><th>Answers</th><th class="text-right">Decision</th></tr></thead>
                <tbody>
                    @foreach ($queue as $l)
                        <tr data-row data-search="{{ strtolower($l->customer->business_name.' '.$l->owner?->name.' '.$l->project->name) }}" data-s-submitted="{{ $l->submitted_at }}">
                            <td><a href="{{ route('leads.show', $l->id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $l->customer->business_name }}</a><p class="text-xs text-slate-500">{{ $l->id }} · {{ $l->customer->business_email }}</p></td>
                            <td>{{ $l->project->name }}</td>
                            <td><div class="flex items-center gap-2"><x-avatar :name="$l->owner?->name" size="xs" />{{ $l->owner?->name }}</div></td>
                            <td class="whitespace-nowrap text-slate-600">{{ ago($l->submitted_at) }}</td>
                            <td><span class="tag">{{ count($l->answers) }} answers · {{ $l->form_version_id }}</span></td>
                            <td class="text-right"><div class="flex justify-end gap-1.5">
                                <a href="{{ route('leads.show', $l->id) }}" class="btn btn-secondary btn-xs">Review</a>
                                <button type="button" class="btn btn-success btn-xs" @click="$store.modal.confirm({ title: 'Accept {{ addslashes($l->customer->business_name) }}?', body: 'The lead moves to the sales pipeline and the original submitted fields become read-only for the Agent.', action: '{{ route('leads.accept', $l->id) }}', confirm: 'Accept lead' })"><x-ico name="check" class="size-3.5" /> Accept</button>
                                <button type="button" class="btn btn-danger btn-xs" @click="$store.modal.open('reject', { id: '{{ $l->id }}', name: '{{ addslashes($l->customer->business_name) }}', action: '{{ route('leads.reject', $l->id) }}' })"><x-ico name="x-mark" class="size-3.5" /> Reject</button>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <x-table-footer />
        @endif
    </div>

    <div class="card mt-6">
        <div class="card-head"><h2 class="card-title">Recently reviewed</h2><span class="text-xs text-slate-500">Rejection reasons stay visible to authorised users</span></div>
        <div class="overflow-x-auto"><table class="tbl tbl-compact">
            <thead><tr><th>Business</th><th>Decision</th><th>By</th><th>When</th><th>Reason / note</th></tr></thead>
            <tbody>
                @forelse ($recent as $l)
                    <tr>
                        <td><a href="{{ route('leads.show', $l->id) }}" class="font-medium hover:text-brand-700">{{ $l->customer->business_name }}</a></td>
                        <td><x-status dim="lead_review" :value="$l->review_status" /></td>
                        <td>{{ app(\App\Mock\Repositories\DirectoryRepository::class)->userName($l->reviewed_by) }}</td>
                        <td class="whitespace-nowrap text-slate-600">{{ ago($l->reviewed_at) }}</td>
                        <td class="max-w-md text-slate-600">{{ $l->review_status === 'rejected' ? $l->rejection_reason.' — '.$l->rejection_notes : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-500">No recent decisions.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>

    <x-slot:modals><x-reject-modal :reasons="$reasons" /></x-slot:modals>
</x-app-layout>
