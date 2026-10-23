@php
    $greeting = match (true) { now()->hour < 12 => 'Good morning', now()->hour < 18 => 'Good afternoon', default => 'Good evening' };
    $sub = [
        'admin' => 'Global pipeline, review queue, integration health and exceptions.',
        'finance' => 'Closed Won handoffs, agreements, billing and commissions. Figures are mock data.',
        'auditor' => 'Your assigned audit, guidance and quotation requests.',
        'manager' => 'Your team\'s pipeline, follow-ups and commissions.',
        'team_lead' => 'Your pipeline, your agents and what needs closing.',
        'agent' => 'Your leads, next actions and appointments.',
    ][$role];
@endphp
<x-app-layout :title="$greeting.', '.explode(' ', demo()::user()->name)[0]" :subtitle="$sub">
    <x-slot:actions>
        @if ($role === 'finance')<a href="{{ route('finance.handoff') }}" class="btn btn-primary"><x-ico name="inbox-arrow-down" class="size-4" /> Closed Won queue</a>@endif
    </x-slot:actions>
    @include('pages.dashboard.'.$role)
</x-app-layout>
