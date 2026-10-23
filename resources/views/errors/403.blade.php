<x-app-layout title="Access restricted" :crumbs="[['Access restricted']]">
    <div class="card mx-auto max-w-xl">
        <x-empty-state icon="lock-closed" title="This screen is restricted for your role" :text="$exception->getMessage() ?: 'You do not have access to this record or screen.'">
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Back to dashboard</a>
                <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">Go back</a>
            </div>
            <p class="mx-auto mt-4 max-w-sm text-xs text-slate-400">Frontend preview: in production the server refuses the request and the restricted data is never sent to the browser.</p>
        </x-empty-state>
    </div>
</x-app-layout>
