<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Assigned to you" :value="$d['total']" icon="document-magnifying-glass" tone="violet" :href="route('audits.index')" hint="All requests" />
    <x-stat label="In progress" :value="($d['byStatus']['in_progress'] ?? 0) + ($d['byStatus']['draft'] ?? 0)" icon="pencil-square" tone="blue" hint="Drafts and active work" />
    <x-stat label="Revisions returned" :value="$d['revisions']->count()" icon="arrow-uturn-left" tone="orange" hint="Waiting on you" />
    <x-stat label="Submitted / sent" :value="($d['byStatus']['submitted'] ?? 0) + ($d['byStatus']['approved'] ?? 0) + ($d['byStatus']['sent'] ?? 0)" icon="paper-airplane" tone="green" hint="Completed work" />
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-3">
    <div class="card">
        <div class="card-head"><h2 class="card-title">Requests by status</h2></div>
        <div class="p-5"><x-chart type="bar" xTitle="Status" yTitle="Requests (count)" caption="Assigned requests by status" :labels="$d['byStatus']->keys()->map(fn ($s) => status_label('audit', $s))->all()" :datasets="[['label' => 'Requests', 'data' => $d['byStatus']->values()->all(), 'color' => 'violet']]" height="h-64" /></div>
    </div>
    <div class="card xl:col-span-2">
        <div class="card-head"><h2 class="card-title">Due dates</h2><a href="{{ route('audits.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Open queue</a></div>
        <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>Request</th><th>Customer</th><th>Priority</th><th>Status</th><th>Due</th></tr></thead><tbody>
            @forelse ($d['due'] as $a)
                @php($late = \Carbon\Carbon::parse($a->due_date)->isPast())
                <tr><td><a href="{{ route('audits.show', $a->id) }}" class="font-medium hover:text-brand-700">{{ ucfirst($a->type) }} · {{ $a->id }}</a></td><td>{{ $a->customer?->business_name }}</td>
                    <td><span class="chip {{ $a->priority === 'high' ? 'chip-red' : 'chip-slate' }}">{{ ucfirst($a->priority) }}</span></td><td><x-status dim="audit" :value="$a->status" /></td><td class="{{ $late ? 'font-medium text-red-600' : 'text-slate-600' }}">{{ $late ? 'Overdue · ' : '' }}{{ fdate($a->due_date, 'd M') }}</td></tr>
            @empty<tr><td colspan="5" class="py-6 text-center text-slate-500">Nothing due.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
<div class="mt-5 grid gap-5 xl:grid-cols-2">
    <div class="card"><div class="card-head"><h2 class="card-title">History by opportunity</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['history'] as $h)<li class="flex items-center justify-between px-5 py-3"><div><p class="font-medium">{{ $h['lead']?->customer->business_name }}</p><p class="text-xs text-slate-500">{{ $h['count'] }} request(s) · latest {{ ago($h['last']) }}</p></div><a href="{{ route('leads.show', $h['lead']->id) }}" class="btn btn-secondary btn-xs">Open</a></li>@empty<li class="px-5 py-4 text-slate-500">No history.</li>@endforelse</ul></div>
    <div class="card"><div class="card-head"><h2 class="card-title">Recent uploads</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($d['uploads'] as $u)<li class="flex items-center gap-3 px-5 py-3"><x-ico name="paper-clip" class="size-4 text-slate-400" /><div><p class="font-medium">{{ $u['f']->name }}</p><p class="text-xs text-slate-500">{{ $u['a']->id }} · {{ $u['f']->size }} · {{ ago($u['f']->at) }}</p></div></li>@empty<li class="px-5 py-4 text-slate-500">No recent uploads.</li>@endforelse</ul></div>
</div>
