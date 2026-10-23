<x-app-layout title="Lead forms" :crumbs="[['Administration'], ['Lead forms']]" subtitle="Admin-defined questions per project, with fixed fields and a draft → publish flow. No code change needed.">
    <div class="card mb-6">
        <div class="card-head"><h2 class="card-title">Forms by project</h2></div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Form</th><th>Assigned to</th><th>Published</th><th>Draft</th><th class="text-right">Questions</th><th>Last published</th><th class="w-24"></th></tr></thead>
            <tbody>
                @foreach ($forms as $f)
                    @php($pub = collect($f->versions)->firstWhere('status', 'published'))
                    @php($draft = collect($f->versions)->firstWhere('status', 'draft'))
                    <tr>
                        <td><a href="{{ route('forms.show', $f->id) }}" class="font-medium hover:text-brand-700">{{ $f->name }}</a><p class="text-xs text-slate-500">{{ $f->id }} · {{ count($f->versions) }} version(s)</p></td>
                        <td>{{ $dir->project($f->project_id)?->name ?? 'Unassigned' }} <span class="tag ml-1">{{ $f->scope }}</span></td>
                        <td><span class="chip chip-green">v{{ $pub?->version }}</span></td>
                        <td>@if ($draft)<span class="chip chip-amber">Draft v{{ $draft->version }}</span>@else<span class="text-slate-400">—</span>@endif</td>
                        <td class="text-right">{{ collect($pub?->sections)->sum(fn ($s) => count($s->fields)) }}</td>
                        <td class="text-slate-600">{{ fdate($pub?->published_at) }}</td>
                        <td><a href="{{ route('forms.show', $f->id) }}" class="btn btn-secondary btn-xs">Open builder</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="card-title">Fixed lead fields</h2><span class="text-xs text-slate-500">Applied to every project. Mark each required or optional.</span></div>
        <ul class="divide-y divide-slate-100">
            @foreach ($fixed as $f)
                <li>
                    <form method="POST" action="{{ route('forms.fixed') }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        @csrf <input type="hidden" name="id" value="{{ $f->id }}">
                        <div><p class="text-sm font-medium">{{ $f->label }}</p><p class="text-xs text-slate-500">{{ $f->group ?? 'Business' }} · {{ $f->type }}{{ ($f->locked_required ?? false) ? ' · always required (needed to name the record)' : '' }}</p></div>
                        <div class="flex items-center gap-2">
                            <select name="required" class="select h-8 w-32 text-[13px]" onchange="this.form.submit()" @disabled($f->locked_required ?? false) aria-label="{{ $f->label }} requirement">
                                <option value="1" @selected($f->required)>Required</option><option value="0" @selected(! $f->required)>Optional</option>
                            </select>
                        </div>
                    </form>
                </li>
            @endforeach
        </ul>
        <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Contacts are a repeatable list on every lead; one is marked primary. Changing a fixed field's requirement applies to new submissions only.</p>
    </div>
</x-app-layout>
