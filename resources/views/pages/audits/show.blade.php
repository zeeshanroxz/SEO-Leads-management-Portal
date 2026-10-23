@php
    $latest = $versions->first();
    $statusNote = collect($req->timeline)->sortByDesc('at')->first(fn ($t) => str_contains($t->text, 'Revision Requested'));
    $canPreview = $versions->count() && (demo()::can('audit.send') || demo()::is('admin'));
@endphp
<x-app-layout :title="ucfirst($req->type).' · '.$req->id" :crumbs="[['Audits & quotes', route('audits.index')], [$req->id]]">
    <x-slot:actions>
        <x-status dim="audit" :value="$req->status" class="!text-sm" />
        @if ($canPreview)<a href="{{ route('audits.preview', $req->id) }}" class="btn btn-secondary"><x-ico name="eye" class="size-4" /> Client preview</a>@endif
        @if ($canSend && $versions->count() && in_array($req->status, ['submitted', 'approved', 'sent']))<a href="{{ route('audits.preview', $req->id) }}#send" class="btn btn-primary"><x-ico name="paper-airplane" class="size-4" /> Send to client</a>@endif
    </x-slot:actions>

    <div class="mb-5 card card-pad flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0"><p class="text-sm text-slate-500">{{ ucfirst($req->type) }} for <a href="{{ route('leads.show', $req->lead_id) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $req->customer?->business_name }}</a> ({{ $req->lead_id }})</p>
            <p class="mt-1 text-sm text-slate-700">“{{ $req->notes }}”</p></div>
        <div class="flex items-center gap-6 text-sm"><div><p class="text-xs text-slate-500">Requested by</p><p class="font-medium">{{ $req->requester?->name }}</p></div><div><p class="text-xs text-slate-500">Auditor</p><p class="font-medium">{{ $req->auditor?->name ?? 'Unassigned' }}</p></div><div><p class="text-xs text-slate-500">Due</p><p class="font-medium">{{ fdate($req->due_date) }}</p></div></div>
    </div>

    @if ($req->status === 'revision_requested')
        <div class="mb-5 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900"><p class="font-semibold">Revision requested</p><p class="mt-0.5">{{ $statusNote?->text ?? 'A reviewer asked for changes. Save a new version — earlier versions are kept.' }}</p></div>
    @endif
    @if ($errors->has('html'))<div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $errors->first('html') }}</div>@endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        {{-- ======== Report ======== --}}
        <div class="min-w-0 space-y-5">
            @if ($canAuthor && ! in_array($req->status, ['cancelled', 'sent']))
                <form method="POST" action="{{ route('audits.version', $req->id) }}" x-data="{ submitting: false }" @submit="$refs.ce?.dispatchEvent(new Event('sync'))">
                    @csrf
                    <div class="card">
                        <div class="card-head"><h2 class="card-title">Report editor</h2><div class="flex items-center gap-2"><span class="tag">Customer-facing content</span><span class="tag tag-demo">Editor mockup</span></div></div>
                        <div class="p-5" x-data="richEditor({ html: @js($base?->html ?? '<h2>Report title</h2><p>Start writing the customer-facing report…</p>'), full: true })">
                            <x-editor-toolbar />
                            <div x-ref="surface" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Customer-facing report content" class="prose-doc min-h-[360px] rounded-b-lg border border-slate-300 bg-white p-5 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20" @input="sync()" @blur="sync()" @paste="onPaste($event)"></div>
                            <input type="hidden" name="html" x-ref="input">
                            <p class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500"><span><span x-text="words"></span> words · Amber “Internal block” sections never reach the customer version.</span><span>Content is sanitised on save (allow-list).</span></p>
                        </div>
                    </div>

                    <div class="card mt-5 border-amber-300">
                        <div class="card-head bg-amber-50"><h2 class="card-title text-amber-900"><x-ico name="eye-slash" class="mr-1 inline size-4" /> Internal notes — never sent to the customer</h2><span class="chip chip-amber">Internal only</span></div>
                        <div class="p-5" x-data="richEditor({ html: @js($base?->internal_html ?? ''), full: false })">
                            <x-editor-toolbar :full="false" />
                            <div x-ref="surface" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Internal notes" class="prose-doc min-h-[110px] rounded-b-lg border border-amber-300 bg-amber-50/40 p-4 focus:outline-none focus:ring-2 focus:ring-amber-400/30" @input="sync()" @blur="sync()" @paste="onPaste($event)"></div>
                            <input type="hidden" name="internal_html" x-ref="input">
                        </div>
                    </div>

                    <div class="card card-pad mt-5 flex flex-wrap items-end justify-between gap-3">
                        <div class="min-w-64 flex-1"><label class="label" for="note">Version note</label><input id="note" name="note" class="input" maxlength="200" placeholder="What changed in this version?"></div>
                        <div class="flex gap-2"><button class="btn btn-secondary">Save draft version</button><button name="submit" value="1" class="btn btn-primary"><x-ico name="check" class="size-4" /> Submit for approval</button></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Saving always creates a <strong>new version</strong> ({{ $versions->count() + 1 }}). Earlier versions are never overwritten.@if ($base) You are editing from version {{ $base->no }}.@endif</p>
                </form>
            @else
                <div class="card">
                    <div class="card-head"><h2 class="card-title">{{ $base ? 'Report — version '.$base->no : 'Report' }}</h2>@if ($base)<div class="flex items-center gap-2"><x-status dim="audit" :value="$base->status" /><span class="text-xs text-slate-500">{{ $req->requester && $base ? \App\Mock\Repositories\DirectoryRepository::class ? app(\App\Mock\Repositories\DirectoryRepository::class)->userName($base->author) : '' : '' }} · {{ fdt($base->at) }}</span></div>@endif</div>
                    @if ($base)
                        <div class="prose-doc p-6">{!! $internalVisible ? $base->html : \App\Support\HtmlSanitizer::customerFacing($base->html) !!}</div>
                        @if ($internalVisible && $base->internal_html)
                            <div class="border-t border-amber-200 bg-amber-50/60 p-5"><p class="mb-1 text-xs font-bold tracking-wider text-amber-700">INTERNAL NOTES — NOT SENT TO THE CUSTOMER</p><div class="prose-doc">{!! $base->internal_html !!}</div></div>
                        @elseif (! $internalVisible)
                            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500"><x-ico name="lock-closed" class="mr-1 inline size-3.5" />Internal auditor notes are hidden for your role.</p>
                        @endif
                    @else
                        <x-empty-state icon="document-text" title="No report yet" :text="$req->auditor ? $req->auditor->name.' has not saved a version yet.' : 'Waiting for an auditor to be assigned.'" />
                    @endif
                </div>
                @if ($canReview && in_array($req->status, ['submitted', 'approved']))
                    <div class="card card-pad flex flex-wrap items-center justify-between gap-3"><div><p class="font-medium">Review this submission</p><p class="text-xs text-slate-500">Approve it for sending, or ask the auditor for changes.</p></div>
                        <div class="flex gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.open('revision')"><x-ico name="arrow-uturn-left" class="size-4" /> Request revision</button>
                            @if ($req->status === 'submitted')<form method="POST" action="{{ route('audits.status', $req->id) }}">@csrf<input type="hidden" name="to" value="approved"><button class="btn btn-success"><x-ico name="check" class="size-4" /> Approve</button></form>@endif</div></div>
                @endif
            @endif
        </div>

        {{-- ======== Aside ======== --}}
        <aside class="space-y-5">
            <div class="card">
                <div class="card-head"><h3 class="card-title">Version history</h3><span class="tag">{{ $versions->count() }}</span></div>
                @if ($versions->isEmpty())<p class="px-5 py-4 text-sm text-slate-500">No versions yet.</p>@else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($versions as $v)
                        <li class="px-5 py-3"><div class="flex items-center justify-between gap-2"><p class="font-medium">Version {{ $v->no }} @if ($v->no === $latest->no)<span class="tag ml-1">latest</span>@endif</p><x-status dim="audit" :value="$v->status" /></div>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $dir->userName($v->author) }} · {{ fdt($v->at) }}</p><p class="mt-0.5 text-xs text-slate-600">{{ $v->note }}</p>
                            <div class="mt-1.5 flex gap-3 text-xs"><a class="font-medium text-brand-700 hover:underline" href="{{ route('audits.show', ['audit' => $req->id, 'load' => $v->no]) }}">{{ $canAuthor ? 'Edit from this' : 'View' }}</a>@if ($canPreview)<a class="font-medium text-brand-700 hover:underline" href="{{ route('audits.preview', ['audit' => $req->id, 'v' => $v->no]) }}">Client preview</a>@endif</div></li>
                    @endforeach
                </ul>@endif
            </div>

            <div class="card">
                <div class="card-head"><h3 class="card-title">Attachments</h3><button type="button" class="btn btn-secondary btn-xs" @click="$store.toast.push('Upload is simulated in Phase 1 - nothing was stored.', 'info', { demo: true })"><x-ico name="arrow-up-tray" class="size-3.5" /> Attach (demo)</button></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($req->files as $f)<li class="flex items-center gap-2 px-5 py-2.5"><x-ico name="paper-clip" class="size-4 text-slate-400" /><div class="min-w-0"><p class="truncate font-medium">{{ $f->name }}</p><p class="text-xs text-slate-500">{{ $f->size }} · {{ ago($f->at) }}</p></div></li>@empty<li class="px-5 py-3 text-slate-500">No files attached.</li>@endforelse
                    @foreach ($req->links as $l)<li class="flex items-center gap-2 px-5 py-2.5"><x-ico name="link" class="size-4 text-slate-400" /><span class="truncate text-slate-600">{{ $l }}</span></li>@endforeach
                </ul>
            </div>

            @if (demo()::is('admin'))
                <form method="POST" action="{{ route('audits.assign', $req->id) }}" class="card card-pad"><p class="label">Assign auditor (Admin)</p>@csrf<div class="flex gap-2"><select name="user_id" class="select" aria-label="Auditor">@foreach ($auditors as $a)<option value="{{ $a->id }}" @selected($a->id === $req->assigned_to)>{{ $a->name }}</option>@endforeach</select><button class="btn btn-secondary">Assign</button></div></form>
            @endif

            @if ($siblings->count())
                <div class="card"><div class="card-head"><h3 class="card-title">Other requests on this opportunity</h3></div>
                    <ul class="divide-y divide-slate-100 text-sm">@foreach ($siblings as $s)<li><a href="{{ route('audits.show', $s->id) }}" class="flex items-center justify-between px-5 py-2.5 hover:bg-slate-50"><span>{{ ucfirst($s->type) }} · {{ $s->id }}</span><x-status dim="audit" :value="$s->status" /></a></li>@endforeach</ul></div>
            @endif

            @if ($req->sends && count($req->sends))
                <div class="card"><div class="card-head"><h3 class="card-title">Sent to client</h3><x-demo-tag /></div>
                    <ul class="divide-y divide-slate-100 text-sm">@foreach ($req->sends as $s)<li class="px-5 py-3"><p class="font-medium">v{{ $s->version }} → {{ implode(', ', (array) $s->to) }}</p>@if (count((array) $s->cc))<p class="text-xs text-slate-500">CC {{ implode(', ', (array) $s->cc) }}</p>@endif<p class="mt-0.5 text-xs text-slate-500">{{ $s->subject }}</p><p class="mt-0.5 text-xs text-slate-400">{{ fdt($s->at) }} · {{ $s->result }}</p></li>@endforeach</ul></div>
            @endif

            <div class="card"><div class="card-head"><h3 class="card-title">Request timeline</h3></div>
                <ol class="space-y-3 p-5 text-sm">@foreach (collect($req->timeline)->sortByDesc('at') as $t)<li class="flex gap-3"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-slate-300"></span><div><p>{{ $t->text }}</p><p class="text-xs text-slate-400">{{ $t->by === 'System' ? 'System' : $dir->userName($t->by) }} · {{ ago($t->at) }}</p></div></li>@endforeach</ol></div>
        </aside>
    </div>

    <x-slot:modals>
        <x-modal name="revision" title="Request a revision" description="The auditor sees your note. The current version stays in the history.">
            <form method="POST" action="{{ route('audits.status', $req->id) }}" x-data="{ note: '', touched: false }" @submit="touched = true; if (note.trim().length < 5) $event.preventDefault()">
                @csrf <input type="hidden" name="to" value="revision_requested">
                <x-field label="What needs to change?" name="note" :required="true"><textarea id="note" name="note" x-model="note" rows="4" class="textarea" :class="{ 'input-error': touched && note.trim().length < 5 }"></textarea><p class="err" x-show="touched && note.trim().length < 5" x-cloak>Add a note (min. 5 characters).</p></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Request revision</button></div>
            </form>
        </x-modal>
    </x-slot:modals>
</x-app-layout>
