<x-app-layout title="Notifications" :crumbs="[['Notifications']]" subtitle="In-app notifications for your role. Email delivery is simulated.">
    <x-slot:actions>
        <a href="{{ route('notifications.settings') }}" class="btn btn-secondary btn-sm"><x-ico name="cog-6-tooth" class="size-4" /> Settings</a>
        <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-secondary btn-sm"><x-ico name="check" class="size-4" /> Mark all read</button></form>
    </x-slot:actions>
    <div class="card divide-y divide-slate-100">
        @forelse ($items as $n)
            <div class="flex items-start gap-4 px-5 py-4 {{ $n->unread ? 'bg-brand-50/40' : '' }}">
                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500"><x-ico :name="$n->icon" class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2"><p class="text-sm font-semibold text-slate-900">{{ $n->title }}</p>@if ($n->unread)<span class="chip chip-blue">New</span>@endif</div>
                    <p class="mt-0.5 text-sm text-slate-600">{{ $n->body }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ ago($n->at) }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route($n->link[0], $n->link[1]) }}" class="btn btn-secondary btn-xs">Open</a>
                    @if ($n->unread)<form method="POST" action="{{ route('notifications.read') }}">@csrf<input type="hidden" name="id" value="{{ $n->id }}"><button class="btn btn-ghost btn-xs">Mark read</button></form>@endif
                </div>
            </div>
        @empty
            <x-empty-state icon="bell-slash" title="You're all caught up" text="New activity for your role will appear here." />
        @endforelse
    </div>
</x-app-layout>
