<x-app-layout title="Not found" :crumbs="[['Not found']]">
    <div class="card mx-auto max-w-xl">
        <x-empty-state icon="question-mark-circle" title="We couldn't find that page" text="The record may not exist in the demo data, or it was removed.">
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Back to dashboard</a>
        </x-empty-state>
    </div>
</x-app-layout>
