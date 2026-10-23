<div class="card">
    <div class="card-head"><h2 class="card-title">Next actions &amp; follow-ups</h2><a href="{{ route('leads.index') }}" class="text-sm font-medium text-brand-700 hover:underline">All leads</a></div>
    <ul class="divide-y divide-slate-100 text-sm">
        @forelse ($followUps as $l)
            @php($overdue = \Carbon\Carbon::parse($l->next_action->due)->isPast() && ! \Carbon\Carbon::parse($l->next_action->due)->isToday())
            <li><a href="{{ route('leads.show', $l->id) }}" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                <span class="min-w-0"><span class="block truncate font-medium text-slate-900">{{ $l->customer->business_name }}</span><span class="block truncate text-xs text-slate-500">{{ $l->next_action->label }}</span></span>
                <span class="shrink-0 text-xs {{ $overdue ? 'font-semibold text-red-600' : 'text-slate-500' }}">{{ $overdue ? 'Overdue · ' : '' }}{{ fdate($l->next_action->due, 'd M') }}</span></a></li>
        @empty
            <li class="px-5 py-6 text-center text-slate-500">No follow-ups due. Nice work.</li>
        @endforelse
    </ul>
</div>
