@php
    $urls = ['draft' => route('forms.draft', $form->id)];
    $cur = $draft ? 'Draft v'.$draft->version : 'Published v'.$published->version;
@endphp
<x-app-layout :title="$form->name" :crumbs="[['Administration'], ['Lead forms', route('forms.index')], [$form->id]]" subtitle="Edit a draft, preview it, then publish a new immutable version. Published questions can be disabled but never deleted.">
    <div x-data="formBuilder(@js(['sections' => $working['sections']]), @js($urls), @js($publishedKeys))" class="space-y-5">
        <div class="card flex flex-wrap items-center justify-between gap-3 px-5 py-3">
          <div class="flex items-center gap-2 text-sm text-slate-600"><x-ico name="pencil-square" class="size-5 text-slate-400" /> Working on: <span class="chip {{ $draft ? 'chip-amber' : 'chip-green' }}">{{ $cur }}</span> <span class="tag" x-show="dirty" x-cloak>Unsaved changes</span></div>
          <div class="flex flex-wrap items-center gap-2">
            @if ($draft)<button type="button" class="btn btn-secondary" @click="$store.modal.confirm({ title: 'Discard draft v{{ $draft->version }}?', body: 'Unpublished changes are lost. The published version is not affected.', action: '{{ route('forms.discard', $form->id) }}', confirm: 'Discard draft', danger: true })">Discard draft</button>@endif
            <button type="button" class="btn btn-secondary" @click="saveDraft()" :disabled="saving">Save draft</button>
            <button type="button" class="btn btn-primary" @click="openPublish()"><x-ico name="rocket-launch" class="size-4" /> Publish new version</button>
          </div>
        </div>

        <div x-show="errors.length" x-cloak class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><p class="font-semibold">Fix before saving:</p><ul class="mt-1 list-disc pl-5"><template x-for="e in errors"><li x-text="e"></li></template></ul></div>

        <div x-data="tabs('builder')">
            <div class="mb-4 flex gap-1 border-b border-slate-200" role="tablist">
                @foreach (['builder' => 'Builder', 'preview' => 'Preview', 'versions' => 'Versions ('.count($form->versions).')'] as $k => $l)<button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $k }}' }" @click="set('{{ $k }}')">{{ $l }}</button>@endforeach
            </div>

            {{-- BUILDER --}}
            <div x-show="tab === 'builder'" class="grid gap-5 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
                <div class="space-y-4">
                    <template x-for="(sec, si) in sections" :key="sec.key">
                        <div class="card" @dragover.prevent @drop.prevent="dropEnd(si)">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-3 py-2">
                                <input x-model="sec.title" class="min-w-0 flex-1 rounded border-0 bg-transparent px-1.5 py-1 text-sm font-semibold focus:bg-slate-50 focus:ring-2 focus:ring-brand-500/30" :aria-label="'Section title ' + (si + 1)">
                                <button type="button" class="btn btn-ghost btn-icon" @click="moveSection(si, -1)" :disabled="si === 0" aria-label="Move section up"><x-ico name="chevron-up" class="size-4" /></button>
                                <button type="button" class="btn btn-ghost btn-icon" @click="moveSection(si, 1)" :disabled="si === sections.length - 1" aria-label="Move section down"><x-ico name="chevron-down" class="size-4" /></button>
                                <button type="button" class="btn btn-ghost btn-icon text-red-600" @click="removeSection(si)" aria-label="Delete section"><x-ico name="trash" class="size-4" /></button>
                            </div>
                            <ul class="p-2">
                                <template x-for="(f, fi) in sec.fields" :key="f.key">
                                    <li draggable="true" @dragstart="dragStart(si, fi, $event)" @dragover.prevent @drop.prevent.stop="dropOn(si, fi)"
                                        class="group flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 text-sm" :class="sel.s === si && sel.i === fi ? 'bg-brand-50 ring-1 ring-brand-200' : 'hover:bg-slate-50'" @click="select(si, fi)">
                                        <span class="cursor-grab text-slate-300 group-hover:text-slate-500" title="Drag to reorder" aria-hidden="true"><x-ico name="bars-2" class="size-4" /></span>
                                        <span class="min-w-0 flex-1"><span class="block truncate font-medium" :class="f.active ? '' : 'text-slate-400 line-through'" x-text="f.label"></span><span class="block text-xs text-slate-500" x-text="typeLabel(f.type) + (f.required ? ' · required' : '') + (f.condition ? ' · conditional' : '') + (isPublished(f) ? ' · published' : '')"></span></span>
                                        <span class="flex opacity-0 group-hover:opacity-100 group-focus-within:opacity-100">
                                            <button type="button" class="btn btn-ghost btn-icon size-7" @click.stop="move(si, fi, -1)" aria-label="Move up"><x-ico name="arrow-up" class="size-3.5" /></button>
                                            <button type="button" class="btn btn-ghost btn-icon size-7" @click.stop="move(si, fi, 1)" aria-label="Move down"><x-ico name="arrow-down" class="size-3.5" /></button>
                                            <button type="button" class="btn btn-ghost btn-icon size-7" @click.stop="duplicate(si, fi)" aria-label="Duplicate question"><x-ico name="document-duplicate" class="size-3.5" /></button>
                                        </span>
                                    </li>
                                </template>
                            </ul>
                            <div class="border-t border-slate-100 p-2"><button type="button" class="btn btn-ghost btn-sm w-full justify-start" @click="addField(si, 'short_answer')"><x-ico name="plus" class="size-4" /> Add question</button></div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-secondary w-full" @click="addSection()"><x-ico name="plus" class="size-4" /> Add section</button>
                </div>

                {{-- Editor --}}
                <div class="card self-start" x-show="field">
                    <div class="card-head"><h2 class="card-title">Question settings</h2>
                        <div class="flex items-center gap-1"><button type="button" class="btn btn-secondary btn-xs" @click="remove(sel.s, sel.i)"><span x-text="isPublished(field) ? 'Disable question' : 'Delete question'"></span></button></div></div>
                    <template x-if="field"><div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="label" for="fb-label">Question label <span class="text-red-500">*</span></label><input id="fb-label" x-model="field.label" @input="autoKey(field)" class="input"></div>
                        <div><label class="label" for="fb-type">Field type</label>
                            <select id="fb-type" class="select" :value="field.type" @change="setType(field, $event.target.value)"><template x-for="t in types" :key="t[0]"><option :value="t[0]" x-text="t[1]" :selected="t[0] === field.type"></option></template></select>
                            <p class="help" x-show="isPublished(field)">Changing the type of a published question affects new submissions only.</p></div>
                        <div><label class="label" for="fb-key">Internal key</label><input id="fb-key" x-model="field.key" class="input font-mono text-xs" :disabled="!field._new"><p class="help" x-text="field._new ? 'Generated from the label until published.' : 'Locked — historical answers use this key.'"></p></div>
                        <div class="sm:col-span-2"><label class="label" for="fb-help">Help text</label><input id="fb-help" x-model="field.help" class="input" placeholder="Optional hint shown under the question"></div>
                        <div class="flex flex-wrap items-center gap-5 sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="check" x-model="field.required"> Required</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="check" x-model="field.active"> Enabled <span class="text-xs text-slate-500">(disabled keeps old answers)</span></label>
                        </div>
                        <div class="sm:col-span-2" x-show="hasOptions(field)"><label class="label" for="fb-opts">Options <span class="font-normal text-slate-500">(one per line, in display order)</span></label><textarea id="fb-opts" class="textarea font-mono text-xs" rows="5" x-model="optionsText"></textarea></div>
                        <template x-if="isNumeric(field)"><div class="grid gap-4 sm:col-span-2 sm:grid-cols-3">
                            <div><label class="label" for="fb-min">Minimum</label><input id="fb-min" type="number" class="input" x-model.number="field.min"></div>
                            <div><label class="label" for="fb-max">Maximum</label><input id="fb-max" type="number" class="input" x-model.number="field.max"></div>
                            <div><label class="label" for="fb-def">Default</label><input id="fb-def" type="number" class="input" x-model.number="field.default"></div></div></template>
                        <div class="sm:col-span-2 rounded-lg bg-slate-50 p-3">
                            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" class="check" :checked="!!field.condition" @change="field.condition = $event.target.checked ? { key: allFields.find((q) => q.key !== field.key)?.key ?? '', equals: '' } : null"> Show only when another answer matches (conditional)</label>
                            <template x-if="field.condition"><div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div><label class="label" for="fb-ck">When question</label><select id="fb-ck" class="select" x-model="field.condition.key"><template x-for="q in allFields.filter((q) => q.key !== field.key)" :key="q.key"><option :value="q.key" x-text="q.label"></option></template></select></div>
                                <div><label class="label" for="fb-cv">Equals</label><input id="fb-cv" class="input" x-model="field.condition.equals" placeholder="Exact option text"></div></div></template></div>
                        </div>
                    </div></template>
                </div>
            </div>

            {{-- PREVIEW --}}
            <div x-show="tab === 'preview'" x-cloak class="mx-auto max-w-3xl space-y-5">
                <div class="flex items-center gap-2 text-xs text-slate-500"><span class="tag">Live preview of your working copy</span> Conditional questions respond to your answers.</div>
                <template x-for="sec in sections" :key="sec.key"><div class="card card-pad">
                    <h3 class="card-title mb-4" x-text="sec.title"></h3>
                    <div class="grid gap-4 sm:grid-cols-2"><template x-for="q in sec.fields" :key="q.key">
                        <div x-show="visiblePreview(q)" :class="['text','checkbox','radio'].includes(q.type) ? 'sm:col-span-2' : ''">
                            <label class="label"><span x-text="q.label"></span><span class="text-red-500" x-show="q.required"> *</span></label>
                            <template x-if="q.type === 'text'"><textarea class="textarea" rows="3" x-model="previewAnswers[q.key]"></textarea></template>
                            <template x-if="q.type === 'short_answer'"><input class="input" x-model="previewAnswers[q.key]"></template>
                            <template x-if="q.type === 'number'"><input type="number" class="input" :min="q.min" :max="q.max" x-model="previewAnswers[q.key]"></template>
                            <template x-if="q.type === 'percentage'"><div class="relative"><input type="number" class="input pr-8" :min="q.min ?? 0" :max="q.max ?? 100" x-model="previewAnswers[q.key]"><span class="pointer-events-none absolute right-3 top-2 text-sm text-slate-400">%</span></div></template>
                            <template x-if="q.type === 'dropdown'"><select class="select" x-model="previewAnswers[q.key]"><option value="">Select…</option><template x-for="o in q.options" :key="o"><option x-text="o"></option></template></select></template>
                            <template x-if="q.type === 'radio'"><div class="flex flex-wrap gap-2"><template x-for="o in q.options" :key="o"><label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="radio" class="check rounded-full" :name="'pv_' + q.key" :value="o" x-model="previewAnswers[q.key]"> <span x-text="o"></span></label></template></div></template>
                            <template x-if="q.type === 'checkbox'"><div class="flex flex-wrap gap-2"><template x-for="o in q.options" :key="o"><label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm"><input type="checkbox" class="check"> <span x-text="o"></span></label></template></div></template>
                            <p class="help" x-show="q.help" x-text="q.help"></p>
                        </div></template></div>
                </div></template>
            </div>

            {{-- VERSIONS --}}
            <div x-show="tab === 'versions'" x-cloak class="card">
                <div class="card-head"><h2 class="card-title">Version history</h2><span class="text-xs text-slate-500">Each lead stores the version it used</span></div>
                <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Version</th><th>Status</th><th class="text-right">Questions</th><th>Published</th><th>Change note</th></tr></thead><tbody>
                    @foreach ($form->versions as $v)
                        <tr><td class="font-medium">v{{ $v->version }} <span class="text-xs font-normal text-slate-400">{{ $v->id }}</span></td><td><x-status dim="misc" :value="$v->status === 'archived' ? 'revoked' : $v->status" class="{{ $v->status === 'archived' ? '' : '' }}" /> @if ($v->status === 'archived')<span class="text-xs text-slate-500">archived</span>@endif</td><td class="text-right">{{ collect($v->sections)->sum(fn ($s) => count($s->fields)) }}</td><td class="text-slate-600">{{ $v->published_at ? fdate($v->published_at).' · '.app(\App\Mock\Repositories\DirectoryRepository::class)->userName($v->published_by) : '—' }}</td><td class="text-slate-600">{{ $v->change_note }}</td></tr>
                    @endforeach
                </tbody></table></div>
                <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Lead submissions keep a snapshot of the exact questions and answers they were captured with — publishing a new version never rewrites history.</p>
            </div>
        </div>

        <div>
            <x-modal name="publish" title="Publish new version" description="Becomes the form used for new leads. Existing leads keep their snapshot.">
                <form method="POST" action="{{ route('forms.publish', $form->id) }}" x-data="{ note: '', touched: false }" @submit="touched = true; if (note.trim().length < 5) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="sections" :value="JSON.stringify(payload())">
                    <p class="mb-3 text-sm text-slate-600"><span x-text="count"></span> question(s) in <span x-text="sections.length"></span> section(s).</p>
                    <x-field label="Change note" name="note" :required="true" help="Shown in the version history."><input id="note" name="note" x-model="note" class="input" :class="{ 'input-error': touched && note.trim().length < 5 }" placeholder="e.g. Added decision-maker question"><p class="err" x-show="touched && note.trim().length < 5" x-cloak>Add a change note (min. 5 characters).</p></x-field>
                    <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Publish</button></div>
                </form>
            </x-modal>
        </div>
    </div>
</x-app-layout>
