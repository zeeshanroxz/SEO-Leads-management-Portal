@props(['reasons'])
<x-modal name="reject" title="Reject lead" description="The Agent and authorised reviewers will see this reason. A note is mandatory.">
    <form method="POST" :action="$store.modal.payload.action" x-data="{ reason: '', notes: '', touched: false }"
          @submit="touched = true; if (!reason || notes.trim().length < 10) $event.preventDefault()">
        @csrf
        <p class="mb-4 text-sm text-slate-600">Rejecting <strong x-text="$store.modal.payload.name"></strong>.</p>
        <div class="space-y-4">
            <x-field label="Rejection reason" name="reason" :required="true">
                <select id="reason" name="reason" x-model="reason" class="select" :class="{ 'input-error': touched && !reason }">
                    <option value="">Select a reason…</option>
                    @foreach ($reasons as $r)<option>{{ $r }}</option>@endforeach
                </select>
                <p class="err" x-show="touched && !reason" x-cloak>Choose a reason.</p>
            </x-field>
            <x-field label="Rejection notes" name="notes" :required="true" help="At least 10 characters. Explain what the Agent should know.">
                <textarea id="notes" name="notes" x-model="notes" rows="4" class="textarea" :class="{ 'input-error': touched && notes.trim().length < 10 }" placeholder="e.g. Operates only in a region we do not serve…"></textarea>
                <p class="err" x-show="touched && notes.trim().length < 10" x-cloak>A rejection note of at least 10 characters is mandatory.</p>
            </x-field>
        </div>
        <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-danger">Reject lead</button></div>
    </form>
</x-modal>
