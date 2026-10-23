<div class="card">
    <div class="card-head"><h2 class="card-title">Upcoming appointments</h2><a href="{{ route('calendar.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Calendar</a></div>
    <ul class="divide-y divide-slate-100 text-sm">
        @forelse ($upcoming as $e)
            <li><a href="{{ route('calendar.index', ['date' => fdate($e->start, 'Y-m-d'), 'event' => $e->id]) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                <span class="flex w-12 shrink-0 flex-col items-center rounded-lg bg-slate-100 py-1 text-center leading-tight"><span class="text-[10px] font-semibold uppercase text-slate-500">{{ fdate($e->start, 'M') }}</span><span class="text-base font-semibold text-slate-800">{{ fdate($e->start, 'd') }}</span></span>
                <span class="min-w-0"><span class="block truncate font-medium text-slate-900">{{ $e->title }}</span><span class="block text-xs text-slate-500">{{ fdate($e->start, 'H:i') }}–{{ fdate($e->end, 'H:i') }} · {{ $e->organizer?->name }}</span></span></a></li>
        @empty
            <li class="px-5 py-6 text-center text-slate-500">No upcoming appointments.</li>
        @endforelse
    </ul>
</div>
