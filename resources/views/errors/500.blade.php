<x-app-layout title="Something went wrong" :crumbs="[['Error']]">
    <div class="card mx-auto max-w-xl">
        <x-empty-state icon="exclamation-triangle" title="Something went wrong" text="An unexpected error occurred in the prototype. Try reloading, or reset the demo data.">
            <div class="flex justify-center gap-2">
                <a href="{{ url()->current() }}" class="btn btn-secondary btn-sm">Reload</a>
                <form method="POST" action="{{ route('demo.reset') }}">@csrf<button class="btn btn-primary btn-sm">Reset demo data</button></form>
            </div>
        </x-empty-state>
    </div>
</x-app-layout>
