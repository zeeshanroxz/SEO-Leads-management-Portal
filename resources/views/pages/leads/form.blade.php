@php
    $isEdit = $mode === 'edit';
    $title = $isEdit ? 'Edit '.$lead->customer->business_name : 'Create lead';
    $locked = $isEdit && ! count($editable);
@endphp
<x-app-layout :title="$title" :crumbs="[['Leads', route('leads.index')], [$isEdit ? $lead->id : 'Create']]" :subtitle="$isEdit ? 'Edit the submission while it is a draft or pending. Accepted leads are locked for Agents.' : 'Capture business details, contacts and the project\'s qualification questions. A duplicate check runs as you type.'">
    @if ($isEdit && $lead->locked)
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <x-ico name="lock-closed" class="mt-0.5 size-5" />
            <div>
                <p class="font-semibold">Submitted fields are locked after acceptance</p>
                <p class="mt-0.5">{{ count($editable) ? 'An Admin reopened: '.implode(', ', $editable).'. Other groups remain read-only.' : 'Agents cannot edit the original submission. Add call notes and updates from the lead workspace instead. Only an Admin can reopen specific fields (with a logged reason).' }}</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('leads.update', $lead->id) : route('leads.store') }}" novalidate
          x-data="leadWizard(@js($config), @js(old() ? array_merge($initial, old()) : $initial), @js($editable), '{{ $mode }}')" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <input type="hidden" name="action" :value="action">
        <input type="hidden" name="override_reason" :value="override_reason">

        <div class="min-w-0 space-y-5">
            {{-- Stepper --}}
            <ol class="card flex items-center gap-1 overflow-x-auto p-2" aria-label="Progress">
                <template x-for="(s, i) in steps" :key="s">
                    <li class="flex-1"><button type="button" class="flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium"
                        :class="step === i + 1 ? 'bg-brand-50 text-brand-700' : (step > i + 1 ? 'text-emerald-700 hover:bg-slate-50' : 'text-slate-400')" @click="goto(i + 1)" :aria-current="step === i + 1 ? 'step' : null">
                        <span class="flex size-5 items-center justify-center rounded-full text-[11px] font-bold" :class="step === i + 1 ? 'bg-brand-600 text-white' : (step > i + 1 ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500')" x-text="step > i + 1 ? '✓' : i + 1"></span><span class="hidden sm:inline" x-text="s"></span></button></li>
                </template>
            </ol>

            {{-- Duplicate banner (permission-safe) --}}
            <div x-cloak x-show="dup.blocked" class="rounded-xl border border-amber-300 bg-amber-50 p-4" role="alert">
                <div class="flex items-start gap-3">
                    <x-ico name="exclamation-triangle" class="mt-0.5 size-5 text-amber-600" />
                    <div class="min-w-0 flex-1 text-sm text-amber-900">
                        <p class="font-semibold">Possible duplicate — creation is blocked by default</p>
                        <template x-for="m in dup.matches" :key="m.customer_id">
                            <div class="mt-2 rounded-lg bg-white/70 p-3">
                                <template x-if="m.visible"><p><a :href="m.details.url" target="_blank" class="font-semibold underline" x-text="m.details.business_name"></a> <span class="text-amber-800" x-text="'· owned by ' + m.details.owner + ' · ' + m.details.review_status + ' / ' + m.details.sales_status"></span></p></template>
                                <template x-if="!m.visible"><p class="font-medium">A matching record exists in another team's scope. Details are hidden for your role.</p></template>
                                <p class="mt-0.5 text-xs text-amber-800">Matched on: <span x-text="m.matched_on.join(', ')"></span></p>
                            </div>
                        </template>
                        <template x-if="dup.can_override">
                            <div class="mt-3"><label class="label text-amber-900" for="ovr">Admin override reason (demo) — min. 10 characters</label>
                                <input id="ovr" type="text" x-model="override_reason" class="input border-amber-300" placeholder="Why is this not a duplicate?">
                                <p class="mt-1 text-xs" :class="overrideOk ? 'text-emerald-700' : 'text-amber-800'" x-text="overrideOk ? 'Override accepted - it will be written to the audit trail.' : 'Submit stays blocked until a reason is provided.'"></p></div>
                        </template>
                        <template x-if="!dup.can_override"><p class="mt-2 text-xs">Only an Admin can override a duplicate block, with a logged reason.
                            <button type="button" class="font-semibold underline" @click="$store.toast.push('Override request sent to Admin (nothing is actually sent).', 'info', { demo: true })">Request an override</button></p></template>
                    </div>
                </div>
            </div>

            {{-- STEP 1 --}}
            <section class="card card-pad" x-show="step === 1">
                <h2 class="card-title mb-4">Project &amp; source</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Project / campaign" name="project_id" :required="true" help="Decides which qualification questions you answer.">
                        <select id="project_id" name="project_id" x-model="f.project_id" class="select" :class="{ 'input-error': err('project_id') }" :data-err="err('project_id') ? '' : null" @if ($isEdit) disabled @endif>
                            <option value="">Select a project…</option>
                            <template x-for="p in cfg.projects" :key="p.id"><option :value="p.id" x-text="p.name" :selected="p.id === f.project_id"></option></template>
                        </select>
                        @if ($isEdit)<input type="hidden" name="project_id" :value="f.project_id">@endif
                        <p class="err" x-text="err('project_id')" x-show="err('project_id')"></p>
                    </x-field>
                    <x-field label="Lead source" name="source">
                        <select id="source" name="source" x-model="f.source" class="select"><template x-for="s in cfg.sources" :key="s"><option x-text="s" :selected="s === f.source"></option></template></select>
                    </x-field>
                </div>
                <div class="mt-4" x-show="project">
                    <p class="label">Services of interest</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="s in projectServices" :key="s.id"><label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50"><input type="checkbox" class="check" name="service_ids[]" :value="s.id" x-model="f.service_ids"> <span x-text="s.name"></span></label></template>
                    </div>
                    <p class="help" x-show="project?.audit_policy === 'required'" x-cloak>This project requires an audit before a proposal can be sent.</p>
                </div>
            </section>

            {{-- STEP 2 --}}
            <section class="card card-pad" x-show="step === 2" x-cloak>
                <h2 class="card-title mb-1">Business details</h2>
                <p class="mb-4 text-xs text-slate-500">The business is separate from the people who work there — add contacts in the next step.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Business name" name="business_name" :required="true" class="sm:col-span-2">
                        <input id="business_name" name="business_name" x-model="f.business_name" :disabled="!can.business" class="input" :class="{ 'input-error': err('business_name') }" :data-err="err('business_name') ? '' : null" autocomplete="off">
                        <p class="err" x-text="err('business_name')" x-show="err('business_name')"></p>
                    </x-field>
                    <div><label class="label" for="business_email">Business email address<span class="text-red-500" x-show="isReq('business_email')"> *</span></label>
                        <input id="business_email" name="business_email" type="email" x-model="f.business_email" :disabled="!can.business" class="input" :class="{ 'input-error': err('business_email') }" :data-err="err('business_email') ? '' : null" placeholder="info@business.example">
                        <p class="err" x-text="err('business_email')" x-show="err('business_email')"></p></div>
                    <div><label class="label" for="business_phone">Business contact number<span class="text-red-500" x-show="isReq('business_phone')"> *</span></label>
                        <input id="business_phone" name="business_phone" x-model="f.business_phone" :disabled="!can.business" class="input" :class="{ 'input-error': err('business_phone') }" :data-err="err('business_phone') ? '' : null" placeholder="+1 555 0100">
                        <p class="err" x-text="err('business_phone')" x-show="err('business_phone')"></p></div>
                    <div class="sm:col-span-2"><label class="label" for="business_address">Business address<span class="text-red-500" x-show="isReq('business_address')"> *</span></label>
                        <input id="business_address" name="business_address" x-model="f.business_address" :disabled="!can.business" class="input" :class="{ 'input-error': err('business_address') }" :data-err="err('business_address') ? '' : null">
                        <p class="err" x-text="err('business_address')" x-show="err('business_address')"></p></div>
                    <x-field label="Website" name="website" help="Used for duplicate matching by domain."><input id="website" name="website" x-model="f.website" :disabled="!can.business" class="input" placeholder="https://www.business.example"></x-field>
                    <x-field label="Industry" name="industry"><input id="industry" name="industry" x-model="f.industry" :disabled="!can.business" class="input" placeholder="e.g. Dental practice"></x-field>
                </div>
            </section>

            {{-- STEP 3 --}}
            <section class="card card-pad" x-show="step === 3" x-cloak>
                <div class="mb-4 flex items-center justify-between"><div><h2 class="card-title">Contacts</h2><p class="text-xs text-slate-500">One business can have several contacts. Mark one as primary.</p></div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="addContact()" :disabled="!can.contacts"><x-ico name="plus" class="size-4" /> Add contact</button></div>
                <div class="space-y-4">
                    <template x-for="(c, i) in f.contacts" :key="i">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <label class="flex items-center gap-2 text-sm font-medium"><input type="radio" name="primary_contact" class="check rounded-full" :value="i" x-model.number="f.primary_contact" :disabled="!can.contacts"> Primary contact <span class="tag" x-show="f.primary_contact === i">Primary</span></label>
                                <button type="button" class="btn btn-ghost btn-xs text-red-600" x-show="f.contacts.length > 1" :disabled="!can.contacts" @click="removeContact(i)"><x-ico name="trash" class="size-3.5" /> Remove</button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="label" :for="'cn' + i">Contact name<span class="text-red-500" x-show="i === 0 && isReq('contact_name')"> *</span></label><input :id="'cn' + i" :name="'contacts[' + i + '][name]'" x-model="c.name" :disabled="!can.contacts" class="input" :class="{ 'input-error': err('contacts.' + i + '.name') }" :data-err="err('contacts.' + i + '.name') ? '' : null"><p class="err" x-text="err('contacts.' + i + '.name')" x-show="err('contacts.' + i + '.name')"></p></div>
                                <div><label class="label" :for="'cd' + i">Designation<span class="text-red-500" x-show="i === 0 && isReq('contact_designation')"> *</span></label><input :id="'cd' + i" :name="'contacts[' + i + '][designation]'" x-model="c.designation" :disabled="!can.contacts" class="input" :class="{ 'input-error': err('contacts.' + i + '.designation') }"><p class="err" x-text="err('contacts.' + i + '.designation')" x-show="err('contacts.' + i + '.designation')"></p></div>
                                <div><label class="label" :for="'ce' + i">Email</label><input :id="'ce' + i" type="email" :name="'contacts[' + i + '][email]'" x-model="c.email" :disabled="!can.contacts" class="input" :class="{ 'input-error': err('contacts.' + i + '.email') }"><p class="err" x-text="err('contacts.' + i + '.email')" x-show="err('contacts.' + i + '.email')"></p></div>
                                <div><label class="label" :for="'cp' + i">Phone</label><input :id="'cp' + i" :name="'contacts[' + i + '][phone]'" x-model="c.phone" :disabled="!can.contacts" class="input"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            {{-- STEP 4 --}}
            <section class="space-y-5" x-show="step === 4" x-cloak>
                <template x-if="!form"><div class="card"><x-empty-state icon="clipboard-document-list" title="Choose a project first" text="The qualification questions come from the project's published form." /></div></template>
                <template x-if="form">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500"><span class="tag">Published form version <span x-text="'v' + form.version"></span></span><span>Your answers are saved as a snapshot of this version.</span></div>
                        <template x-for="s in form.sections" :key="s.key">
                            <div class="card card-pad">
                                <h2 class="card-title mb-4" x-text="s.title"></h2>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <template x-for="q in s.fields" :key="q.key">
                                        <div x-show="visible(q)" :class="['text','checkbox','radio'].includes(q.type) ? 'sm:col-span-2' : ''">
                                            <label class="label" :for="'q_' + q.key"><span x-text="q.label"></span><span class="text-red-500" x-show="q.required"> *</span></label>
                                            <template x-if="q.type === 'text'"><textarea :id="'q_' + q.key" :name="'answers[' + q.key + ']'" x-model="f.answers[q.key]" :disabled="!can.answers" rows="3" class="textarea" :class="{ 'input-error': err('answers.' + q.key) }"></textarea></template>
                                            <template x-if="q.type === 'short_answer'"><input :id="'q_' + q.key" :name="'answers[' + q.key + ']'" x-model="f.answers[q.key]" :disabled="!can.answers" class="input" :class="{ 'input-error': err('answers.' + q.key) }" :data-err="err('answers.' + q.key) ? '' : null"></template>
                                            <template x-if="q.type === 'number'"><input :id="'q_' + q.key" type="number" :name="'answers[' + q.key + ']'" x-model="f.answers[q.key]" :min="q.min" :max="q.max" :disabled="!can.answers" class="input" :class="{ 'input-error': err('answers.' + q.key) }" :data-err="err('answers.' + q.key) ? '' : null"></template>
                                            <template x-if="q.type === 'percentage'"><div class="relative"><input :id="'q_' + q.key" type="number" :name="'answers[' + q.key + ']'" x-model="f.answers[q.key]" min="0" :max="q.max ?? 100" :disabled="!can.answers" class="input pr-8" :class="{ 'input-error': err('answers.' + q.key) }" :data-err="err('answers.' + q.key) ? '' : null"><span class="pointer-events-none absolute right-3 top-2 text-sm text-slate-400">%</span></div></template>
                                            <template x-if="q.type === 'dropdown'"><select :id="'q_' + q.key" :name="'answers[' + q.key + ']'" x-model="f.answers[q.key]" :disabled="!can.answers" class="select" :class="{ 'input-error': err('answers.' + q.key) }" :data-err="err('answers.' + q.key) ? '' : null"><option value="">Select…</option><template x-for="o in q.options" :key="o"><option :value="o" x-text="o"></option></template></select></template>
                                            <template x-if="q.type === 'radio'"><div class="flex flex-wrap gap-2" role="radiogroup" :aria-label="q.label"><template x-for="o in q.options" :key="o"><label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50"><input type="radio" class="check rounded-full" :name="'answers[' + q.key + ']'" :value="o" x-model="f.answers[q.key]" :disabled="!can.answers"> <span x-text="o"></span></label></template></div></template>
                                            <template x-if="q.type === 'checkbox'"><div class="flex flex-wrap gap-2"><template x-for="o in q.options" :key="o"><label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50"><input type="checkbox" class="check" :name="'answers[' + q.key + '][]'" :value="o" x-model="f.answers[q.key]" :disabled="!can.answers"> <span x-text="o"></span></label></template></div></template>
                                            <p class="help" x-show="q.help" x-text="q.help"></p>
                                            <p class="err" x-text="err('answers.' + q.key)" x-show="err('answers.' + q.key)"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </section>

            {{-- STEP 5 --}}
            <section class="card card-pad" x-show="step === 5" x-cloak>
                <h2 class="card-title mb-4">Review &amp; submit</h2>
                <dl class="dl">
                    <div><dt>Project</dt><dd x-text="project?.name ?? '—'"></dd></div><div><dt>Source</dt><dd x-text="f.source"></dd></div>
                    <div><dt>Business</dt><dd x-text="f.business_name || '—'"></dd></div><div><dt>Email / phone</dt><dd x-text="(f.business_email || '—') + ' · ' + (f.business_phone || '—')"></dd></div>
                    <div class="sm:col-span-2"><dt>Address</dt><dd x-text="f.business_address || '—'"></dd></div>
                    <div class="sm:col-span-2"><dt>Contacts</dt><dd><template x-for="(c, i) in f.contacts" :key="i"><p x-show="c.name"><span class="font-medium" x-text="c.name"></span> <span class="text-slate-500" x-text="c.designation ? '· ' + c.designation : ''"></span> <span class="tag" x-show="f.primary_contact === i">Primary</span></p></template></dd></div>
                </dl>
                <template x-if="form"><div class="mt-5 border-t border-slate-100 pt-4"><p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Qualification answers</p>
                    <dl class="dl"><template x-for="q in allQuestions" :key="q.key"><div x-show="visible(q)"><dt x-text="q.label"></dt><dd x-text="answerText(q)"></dd></div></template></dl></div></template>
                <div class="mt-5 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Submitting sends the lead to <strong>Pending Review</strong>. After acceptance the submitted fields are locked for you; you can still add call notes and see status updates.</div>
            </section>

            {{-- Nav buttons --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="button" class="btn btn-secondary" @click="back()" x-show="step > 1"><x-ico name="arrow-left" class="size-4" /> Back</button><span x-show="step === 1"></span>
                <div class="flex flex-wrap items-center gap-2">
                    @if (! $locked)
                        @if (! $isEdit || ($lead && $lead->review_status === 'draft'))
                            <button type="button" class="btn btn-secondary" @click="submit('draft')">Save draft</button>
                        @endif
                        <button type="button" class="btn btn-primary" x-show="step < 5" @click="next()">Continue <x-ico name="arrow-right" class="size-4" /></button>
                        <button type="button" class="btn btn-primary" x-show="step === 5" x-cloak @click="submit('submit')"><x-ico name="paper-airplane" class="size-4" /> {{ $isEdit && $lead->review_status !== 'draft' ? 'Save changes' : 'Submit for review' }}</button>
                    @else
                        <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-primary">Back to lead workspace</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Side help --}}
        <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
            <div class="card card-pad text-sm text-slate-600">
                <p class="mb-2 font-semibold text-slate-900">How this works</p>
                <ul class="space-y-2">
                    <li class="flex gap-2"><x-ico name="document-duplicate" class="mt-0.5 size-4 text-slate-400" /> Duplicates are checked on email, phone, website domain and business name.</li>
                    <li class="flex gap-2"><x-ico name="camera" class="mt-0.5 size-4 text-slate-400" /> Answers are stored with the form version they were captured on.</li>
                    <li class="flex gap-2"><x-ico name="lock-closed" class="mt-0.5 size-4 text-slate-400" /> Submitted fields lock for Agents once the lead is accepted.</li>
                </ul>
            </div>
            <div class="card card-pad text-xs text-slate-500" x-show="dup.checked && !dup.blocked" x-cloak><span class="chip chip-green">No duplicates found</span><p class="mt-2">Checked against the demo records using normalised email, phone, domain and name.</p></div>
        </aside>
    </form>
</x-app-layout>
