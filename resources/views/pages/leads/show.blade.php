@php
    $dir = app(\App\Mock\Repositories\DirectoryRepository::class);
    $role = demo()::roleKey();
    $isOwnerRole = in_array($role, ['agent', 'team_lead', 'manager'], true);
    $showActivity = ! in_array($role, ['auditor'], true) && ($role !== 'finance' || $notesGranted);
    $icons = ['call' => 'phone', 'note' => 'document-text', 'email' => 'envelope', 'status' => 'arrow-path', 'system' => 'cog-6-tooth', 'appointment' => 'calendar-days', 'audit' => 'document-magnifying-glass'];
    $tabs = ['overview' => 'Overview', 'contacts' => 'Contacts'];
    if ($showActivity) { $tabs['activity'] = 'Activity'; }
    if (demo()::can('audit.view')) { $tabs['audits'] = 'Audits & quotes'; }
    if (demo()::can('calendar.use')) { $tabs['appointments'] = 'Appointments'; }
    $tabs['files'] = 'Files';
    if (demo()::can('agreements.view')) { $tabs['agreement'] = 'Agreement & billing'; }
    if ($commissions->count()) { $tabs['commission'] = 'Commission'; }
    if ($role !== 'auditor') { $tabs['history'] = 'History'; }
    $latestAudit = $audits->first();
    $agreement = $agreements->first();
    $canSales = demo()::can('leads.sales_edit') && $lead->review_status === 'accepted';
    $isTerminal = in_array($lead->sales_status, ['closed_lost', 'not_interested']);
    $primary = collect($customer->contacts)->firstWhere('primary', true);
@endphp
<x-app-layout :title="$customer->business_name" :crumbs="[['Leads', route('leads.index')], [$lead->id]]">
    <x-slot:actions>
        @if ($canReview)
            <button type="button" class="btn btn-success" @click="$store.modal.confirm({ title: 'Accept {{ addslashes($customer->business_name) }}?', body: 'The lead moves to the sales pipeline and the original submitted fields become read-only for the Agent.', action: '{{ route('leads.accept', $lead->id) }}', confirm: 'Accept lead' })"><x-ico name="check" class="size-4" /> Accept</button>
            <button type="button" class="btn btn-danger" @click="$store.modal.open('reject', { id: '{{ $lead->id }}', name: '{{ addslashes($customer->business_name) }}', action: '{{ route('leads.reject', $lead->id) }}' })"><x-ico name="x-mark" class="size-4" /> Reject</button>
        @endif
        @if ($lead->review_status === 'draft' && ($lead->owner_id === demo()::id() || demo()::is('admin')))
            <form method="POST" action="{{ route('leads.submit', $lead->id) }}">@csrf<button class="btn btn-primary"><x-ico name="paper-airplane" class="size-4" /> Submit for review</button></form>
        @endif
        @if (count($editable))<a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-secondary"><x-ico name="pencil-square" class="size-4" /> Edit</a>@endif
        @if ($canSales && ! $isTerminal)
            <button type="button" class="btn btn-secondary" @click="$store.modal.open('status')"><x-ico name="arrows-right-left" class="size-4" /> Change status</button>
        @endif
        @if ($lead->review_status === 'accepted' && demo()::can('audit.request'))
            <button type="button" class="btn btn-secondary" @click="$store.modal.open('audit-request')"><x-ico name="document-magnifying-glass" class="size-4" /> Request audit / quote</button>
        @endif
        @if ($lead->review_status === 'accepted' && demo()::can('calendar.use'))
            <a href="{{ route('calendar.index', ['new' => 1, 'lead' => $lead->id]) }}" class="btn btn-secondary"><x-ico name="calendar-days" class="size-4" /> Book appointment</a>
        @endif
        @if ($lead->review_status === 'accepted' && demo()::can('leads.create'))
            <button type="button" class="btn btn-secondary" @click="$store.modal.open('email-summary')"><x-ico name="envelope" class="size-4" /> Email summary</button>
        @endif
        @if ($showActivity && $lead->review_status !== 'rejected')
            <button type="button" class="btn btn-primary" @click="$store.modal.open('activity')"><x-ico name="plus" class="size-4" /> Add note / call</button>
        @endif
    </x-slot:actions>

    {{-- Summary header --}}
    <div class="card mb-5 p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-lg font-semibold text-brand-700">{{ initials($customer->business_name) }}</span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $customer->business_name }}</h2>
                        <span class="tag">{{ $lead->id }}</span>
                        @if ($lead->parent_id)<span class="tag">Upsell of {{ $lead->parent_id }}</span>@endif
                        @if ($lead->locked && $isOwnerRole && ! demo()::is('manager'))<span class="tag"><x-ico name="lock-closed" class="size-3" /> Submission locked</span>@endif
                    </div>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $lead->project->name }} · {{ $lead->source }} · created {{ fdate($lead->created_at) }}</p>
                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                        <x-status dim="lead_review" :value="$lead->review_status" />
                        @if ($lead->review_status === 'accepted')<x-status dim="sales" :value="$lead->sales_status" />@endif
                        @if ($latestAudit)<span class="chip chip-{{ status_meta('audit', $latestAudit->status)[1] }}" title="Latest audit / quote request">Audit: {{ status_label('audit', $latestAudit->status) }}</span>@endif
                        @if ($agreement)<span class="chip chip-{{ status_meta('agreement', $agreement->status)[1] }}">Agreement: {{ status_label('agreement', $agreement->status) }}</span>@endif
                    </div>
                </div>
            </div>
            @unless ($role === 'auditor')
            <div class="flex items-center gap-6 text-sm">
                <div><p class="text-xs text-slate-500">Owner</p><div class="mt-1 flex items-center gap-2"><x-avatar :name="$lead->owner?->name" size="xs" /><span class="font-medium">{{ $lead->owner?->name }}</span></div></div>
                @if ($lead->team_lead)<div><p class="text-xs text-slate-500">Team Lead / closer</p><p class="mt-1 font-medium">{{ $lead->team_lead->name }}</p></div>@endif
                @if ($lead->manager && ! demo()::is('agent'))<div class="hidden md:block"><p class="text-xs text-slate-500">Manager</p><p class="mt-1 font-medium">{{ $lead->manager->name }}</p></div>@endif
            </div>
            @endunless
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]" x-data="tabs('overview')">
        {{-- ================= Main ================= --}}
        <div class="min-w-0">
            <div class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
                @foreach ($tabs as $key => $label)
                    <button type="button" role="tab" class="tab" :class="{ 'tab-active': tab === '{{ $key }}' }" :aria-selected="tab === '{{ $key }}'" @click="set('{{ $key }}')">{{ $label }}@if ($key === 'audits' && $audits->count())<span class="ml-1.5 rounded-full bg-slate-100 px-1.5 text-[11px] text-slate-600">{{ $audits->count() }}</span>@endif</button>
                @endforeach
            </div>

            {{-- OVERVIEW --}}
            <div x-show="tab === 'overview'" class="space-y-5">
                @if ($lead->review_status === 'rejected')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4"><div class="flex items-start gap-3"><x-ico name="x-circle" class="mt-0.5 size-5 text-red-500" />
                        <div class="text-sm text-red-900"><p class="font-semibold">Rejected: {{ $lead->rejection_reason }}</p><p class="mt-0.5">{{ $lead->rejection_notes }}</p><p class="mt-1 text-xs text-red-700">By {{ $dir->userName($lead->reviewed_by) }} · {{ fdt($lead->reviewed_at) }}</p></div></div></div>
                @elseif ($lead->review_status === 'pending_review')
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><div class="flex items-start gap-3"><x-ico name="clock" class="mt-0.5 size-5 text-amber-500" /><div><p class="font-semibold">Waiting for review</p><p class="mt-0.5">Submitted {{ ago($lead->submitted_at) }}. An Admin, Manager or authorised Team Lead will accept or reject it. You can still edit the submission until it is accepted.</p></div></div></div>
                @elseif ($lead->review_status === 'draft')
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><p class="font-semibold">Draft</p><p class="mt-0.5">This lead has not been submitted yet.</p></div>
                @endif

                @if ($isTerminal)
                    <div class="rounded-xl border border-slate-300 bg-slate-50 p-4 text-sm"><p class="font-semibold text-slate-900">{{ status_label('sales', $lead->sales_status) }}: {{ $lead->lost_reason }}</p><p class="mt-0.5 text-slate-600">{{ $lead->lost_notes }}</p></div>
                @endif

                <div class="card">
                    <div class="card-head"><h3 class="card-title">Business details</h3>@if ($lead->locked)<span class="tag"><x-ico name="lock-closed" class="size-3" /> Original submission{{ count($editable) ? ' (partly reopened)' : ' — read-only' }}</span>@endif</div>
                    <dl class="dl p-5">
                        <div><dt>Business name</dt><dd>{{ $customer->business_name }}</dd></div>
                        <div><dt>Industry</dt><dd>{{ $customer->industry ?? '—' }}</dd></div>
                        <div><dt>Business email</dt><dd>{{ $customer->business_email }}</dd></div>
                        <div><dt>Business contact number</dt><dd>{{ $customer->business_phone }}</dd></div>
                        <div class="sm:col-span-2"><dt>Business address</dt><dd>{{ $customer->business_address }}</dd></div>
                        <div><dt>Website</dt><dd>{{ $customer->website ?? '—' }}</dd></div>
                        <div><dt>Primary contact</dt><dd>{{ $primary?->name }} <span class="text-slate-500">· {{ $primary?->designation }}</span></dd></div>
                    </dl>
                </div>

                @if ($upsellRestricted)
                    <x-restricted title="Services & upsell details are restricted" text="An Admin chose not to share this upsell's service details with the original deal owners. This is a record-level privacy setting and can be reversed." />
                @else
                    <div class="card">
                        <div class="card-head"><h3 class="card-title">Services of interest</h3></div>
                        <div class="flex flex-wrap gap-2 p-5">@forelse ($lead->services as $s)<span class="chip chip-slate">{{ $s->name }}</span>@empty<span class="text-sm text-slate-500">None selected.</span>@endforelse</div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-head"><h3 class="card-title">Qualification answers</h3><span class="tag">Form version {{ $lead->form_version_id }} snapshot</span></div>
                    @if ($lead->answers)
                        <dl class="dl p-5">
                            @foreach ($lead->answers as $a)
                                <div><dt>{{ $a->label }}</dt><dd>{{ is_array($a->value) ? implode(', ', $a->value) : ($a->type === 'percentage' ? $a->value.'%' : $a->value) }}</dd></div>
                            @endforeach
                        </dl>
                        <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Answers keep the wording of the form version they were captured on. Later form changes never alter these.</p>
                    @else
                        <x-empty-state icon="clipboard-document-list" title="No answers recorded" />
                    @endif
                </div>
            </div>

            {{-- CONTACTS --}}
            <div x-show="tab === 'contacts'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">Business contacts</h3>
                    @if (demo()::can('leads.sales_edit') || $lead->owner_id === demo()::id())<button type="button" class="btn btn-secondary btn-sm" @click="$store.modal.open('contact')"><x-ico name="plus" class="size-4" /> Add contact</button>@endif</div>
                <div class="overflow-x-auto"><table class="tbl"><thead><tr><th>Name</th><th>Designation</th><th>Email</th><th>Phone</th></tr></thead><tbody>
                    @foreach ($customer->contacts as $k)
                        <tr><td class="font-medium">{{ $k->name }} @if ($k->primary)<span class="chip chip-blue ml-1">Primary</span>@endif</td><td>{{ $k->designation }}</td><td>{{ $k->email }}</td><td>{{ $k->phone }}</td></tr>
                    @endforeach
                </tbody></table></div>
                <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">The business and its contacts are separate records. Customer profile: <a class="text-brand-700 underline" href="{{ demo()::can('customers.view') ? route('customers.show', $customer->id) : '#' }}">{{ $customer->business_name }}</a></p>
            </div>

            {{-- ACTIVITY --}}
            @if ($showActivity)
            <div x-show="tab === 'activity'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">Activity timeline</h3>@if ($lead->review_status !== 'rejected')<button type="button" class="btn btn-secondary btn-sm" @click="$store.modal.open('activity')"><x-ico name="plus" class="size-4" /> Add note / call</button>@endif</div>
                @if ($upsellRestricted)
                    <div class="p-5"><x-restricted title="Activity on this upsell is restricted" /></div>
                @else
                    @if ($role === 'finance')<p class="border-b border-amber-200 bg-amber-50 px-5 py-2 text-xs text-amber-900">Call notes are normally outside Finance scope. Visible here because an Admin granted access to “Internal call notes” on this record.</p>@endif
                    <ol class="relative space-y-5 p-5">
                        @foreach (collect($lead->activities)->sortByDesc('at') as $a)
                            <li class="flex gap-3">
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $a->type === 'status' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}"><x-ico :name="$icons[$a->type] ?? 'document-text'" class="size-4" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-900">{{ $a->title }} @if ($a->outcome)<span class="chip chip-slate ml-1">{{ $a->outcome }}</span>@endif</p>
                                    @if ($a->body)<p class="mt-0.5 text-sm text-slate-600">{{ $a->body }}</p>@endif
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $dir->userName($a->by) }} · {{ fdt($a->at) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
            @endif

            {{-- AUDITS --}}
            @if (isset($tabs['audits']))
            <div x-show="tab === 'audits'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">Audit, guidance &amp; quotation requests</h3>
                    @if ($lead->review_status === 'accepted' && demo()::can('audit.request'))<button type="button" class="btn btn-secondary btn-sm" @click="$store.modal.open('audit-request')"><x-ico name="plus" class="size-4" /> New request</button>@endif</div>
                @if ($upsellRestricted)<div class="p-5"><x-restricted /></div>
                @elseif ($audits->isEmpty())<x-empty-state icon="document-magnifying-glass" title="No requests yet" text="{{ $lead->project->audit_policy === 'required' ? 'This project requires an audit before a proposal.' : 'Request an audit, guidance or a quotation when the customer needs one.' }}" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($audits as $r)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                <div class="min-w-0"><p class="text-sm font-medium"><a href="{{ route('audits.show', $r->id) }}" class="hover:text-brand-700">{{ ucfirst($r->type) }} · {{ $r->id }}</a> <x-status dim="audit" :value="$r->status" class="ml-1" /></p>
                                    <p class="mt-0.5 text-xs text-slate-500">Requested by {{ $r->requester?->name }} · assigned to {{ $r->auditor?->name ?? 'unassigned' }} · {{ $r->version_count }} version(s) · due {{ fdate($r->due_date, 'd M') }}</p></div>
                                <a href="{{ route('audits.show', $r->id) }}" class="btn btn-secondary btn-xs">Open</a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Every request is a separate linked record with its own versions and files. A new request never overwrites an older report.</p>
                @endif
            </div>
            @endif

            {{-- APPOINTMENTS --}}
            @if (isset($tabs['appointments']))
            <div x-show="tab === 'appointments'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">Appointments</h3>@if ($lead->review_status === 'accepted')<a href="{{ route('calendar.index', ['new' => 1, 'lead' => $lead->id]) }}" class="btn btn-secondary btn-sm"><x-ico name="plus" class="size-4" /> Book</a>@endif</div>
                @if ($events->isEmpty())<x-empty-state icon="calendar-days" title="No appointments" text="Book a call or meeting from the portal calendar." />
                @else<ul class="divide-y divide-slate-100">@foreach ($events as $e)
                    <li class="flex items-center justify-between gap-3 px-5 py-3.5"><div><p class="text-sm font-medium {{ $e->status === 'cancelled' ? 'text-slate-400 line-through' : '' }}">{{ $e->title }}</p><p class="text-xs text-slate-500">{{ fdt($e->start) }} · {{ $e->organizer?->name }}</p></div><div class="flex items-center gap-2"><x-status dim="misc" :value="$e->status" /><a class="btn btn-ghost btn-xs" href="{{ route('calendar.index', ['date' => fdate($e->start, 'Y-m-d'), 'event' => $e->id]) }}">Open</a></div></li>
                @endforeach</ul>@endif
            </div>
            @endif

            {{-- FILES --}}
            <div x-show="tab === 'files'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">Files &amp; documents</h3>
                    <button type="button" class="btn btn-secondary btn-sm" @click="$store.toast.push('File upload is simulated in Phase 1 - nothing was stored. Phase 2 adds validated, permission-scoped storage.', 'info', { demo: true })"><x-ico name="arrow-up-tray" class="size-4" /> Upload (demo)</button></div>
                @php($files = $audits->flatMap(fn ($r) => collect($r->files)->map(fn ($f) => ['f' => $f, 'r' => $r])))
                @if ($files->isEmpty() && $audits->isEmpty())<x-empty-state icon="folder-open" title="No files" text="Audit attachments and permitted documents appear here." />
                @else
                    <table class="tbl"><thead><tr><th>File</th><th>Source</th><th>Scope</th><th>Added</th></tr></thead><tbody>
                        @foreach ($files as $x)<tr><td class="font-medium"><x-ico name="paper-clip" class="mr-1 inline size-4 text-slate-400" />{{ $x['f']->name }} <span class="text-xs text-slate-400">{{ $x['f']->size }}</span></td><td>{{ $x['r']->id }} attachment</td><td><span class="chip chip-slate">Internal</span></td><td class="text-slate-500">{{ fdate($x['f']->at) }}</td></tr>@endforeach
                        @foreach ($audits as $r)@if ($r->latest)<tr><td class="font-medium"><x-ico name="document-text" class="mr-1 inline size-4 text-slate-400" />{{ ucfirst($r->type) }} report v{{ $r->latest->no }}</td><td>{{ $r->id }}</td><td><span class="chip chip-teal">Customer-facing + internal notes</span></td><td class="text-slate-500">{{ fdate($r->latest->at) }}</td></tr>@endif @endforeach
                        @foreach ($agreements as $a)<tr><td class="font-medium"><x-ico name="document-text" class="mr-1 inline size-4 text-slate-400" />Agreement {{ $a->id }} (v{{ $a->version }})</td><td>Agreements</td><td><span class="chip chip-amber">Finance / Admin only</span></td><td class="text-slate-500">{{ fdate($a->created_at) }}</td></tr>@endforeach
                    </tbody></table>
                @endif
            </div>

            {{-- AGREEMENT --}}
            @if (isset($tabs['agreement']))
            <div x-show="tab === 'agreement'" x-cloak class="space-y-4">
                @forelse ($agreements as $a)
                    <div class="card card-pad flex flex-wrap items-center justify-between gap-4">
                        <div><p class="font-semibold"><a href="{{ route('agreements.show', $a->id) }}" class="hover:text-brand-700">{{ $a->id }}</a> <x-status dim="agreement" :value="$a->status" class="ml-1" /></p>
                            <p class="mt-1 text-sm text-slate-500">{{ count($a->lines) }} service line(s) · {{ $a->term_months }}-month term · recurring {{ money($a->mrr) }}/mo · one-time {{ money($a->one_time_total) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Billing: <x-status dim="billing" :value="$a->billing_status" /></p></div>
                        <a href="{{ route('agreements.show', $a->id) }}" class="btn btn-secondary btn-sm">Open agreement</a>
                    </div>
                @empty
                    <div class="card"><x-empty-state icon="document-text" title="No agreement yet" text="{{ $lead->sales_status === 'closed_won' ? 'This Closed Won deal is waiting in the Finance queue.' : 'Agreements are created by Finance after a deal is Closed Won.' }}">
                        @if ($lead->sales_status === 'closed_won' && demo()::can('agreements.manage'))<a href="{{ route('agreements.create', ['lead' => $lead->id]) }}" class="btn btn-primary btn-sm">Create agreement</a>@endif</x-empty-state></div>
                @endforelse
            </div>
            @endif

            {{-- COMMISSION --}}
            @if (isset($tabs['commission']))
            <div x-show="tab === 'commission'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">{{ demo()::is('admin', 'finance') ? 'Commissions on this deal' : 'Your commission on this deal' }}</h3><x-demo-tag label="Synthetic sample amounts" /></div>
                <table class="tbl"><thead><tr><th>Recipient</th><th>Type</th><th>Status</th><th>Eligible from</th><th class="text-right">Amount</th></tr></thead><tbody>
                    @foreach ($commissions as $c)<tr><td>{{ $c->recipient?->name }} <span class="text-xs text-slate-400">· {{ $c->role_at_sale }}</span></td><td>{{ str_replace('_', '-', ucfirst($c->type)) }}</td><td><x-status dim="commission" :value="$c->status" /></td><td>{{ fdate($c->eligibility_date) }}</td><td class="text-right font-medium">{{ money($c->current_amount, null, 2) }}</td></tr>@endforeach
                </tbody></table>
            </div>
            @endif

            {{-- HISTORY --}}
            @if (isset($tabs['history']))
            <div x-show="tab === 'history'" x-cloak class="card">
                <div class="card-head"><h3 class="card-title">Change history</h3><span class="text-xs text-slate-500">Actor, time, previous and new value are always kept</span></div>
                <div class="overflow-x-auto"><table class="tbl tbl-compact"><thead><tr><th>When</th><th>By</th><th>Field</th><th>From → To</th><th>Reason</th></tr></thead><tbody>
                    @forelse (collect($lead->history)->sortByDesc('at') as $h)
                        <tr><td class="whitespace-nowrap text-slate-500">{{ fdt($h->at) }}</td><td>{{ $dir->userName($h->by) }}</td><td>{{ $h->field }}</td><td>{{ $h->from }} → <span class="font-medium">{{ $h->to }}</span></td><td class="max-w-xs text-slate-600">{{ $h->reason ?? '—' }}</td></tr>
                    @empty<tr><td colspan="5" class="py-6 text-center text-slate-500">No changes yet.</td></tr>@endforelse
                </tbody></table></div>
            </div>
            @endif
        </div>

        {{-- ================= Aside ================= --}}
        <aside class="space-y-5">
            <div class="card">
                <div class="card-head"><h3 class="card-title">Independent statuses</h3></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    <li class="flex items-center justify-between px-5 py-2.5"><span class="text-slate-500">Lead review</span><x-status dim="lead_review" :value="$lead->review_status" /></li>
                    <li class="flex items-center justify-between px-5 py-2.5"><span class="text-slate-500">Sales opportunity</span>@if ($lead->review_status === 'accepted')<x-status dim="sales" :value="$lead->sales_status" />@else<span class="text-xs text-slate-400">Starts after acceptance</span>@endif</li>
                    <li class="flex items-center justify-between px-5 py-2.5"><span class="text-slate-500">Audit / quote</span>@if ($latestAudit)<x-status dim="audit" :value="$latestAudit->status" />@else<span class="text-xs text-slate-400">No request</span>@endif</li>
                    @perm('agreements.view')
                        <li class="flex items-center justify-between px-5 py-2.5"><span class="text-slate-500">Agreement</span>@if ($agreement)<x-status dim="agreement" :value="$agreement->status" />@else<span class="text-xs text-slate-400">None</span>@endif</li>
                        <li class="flex items-center justify-between px-5 py-2.5"><span class="text-slate-500">Billing</span>@if ($agreement)<x-status dim="billing" :value="$agreement->billing_status" />@else<x-status dim="billing" value="not_set_up" />@endif</li>
                    @endperm
                    @if ($commissions->count())
                        <li class="flex items-center justify-between px-5 py-2.5"><span class="text-slate-500">{{ demo()::is('admin', 'finance') ? 'Commission (latest)' : 'Your commission' }}</span><x-status dim="commission" :value="$commissions->first()->status" /></li>
                    @endif
                </ul>
                @if ($signedMrr)
                    <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Recurring revenue visible because the agreement is fully signed: <strong class="text-slate-800">{{ money($signedMrr->mrr) }}/mo</strong></p>
                @endif
                <p class="border-t border-slate-100 px-5 py-2.5 text-[11px] text-slate-400">Each dimension moves independently — e.g. Closed Won does not mean signed or paid.</p>
            </div>

            @if ($lead->next_action && $lead->review_status !== 'rejected')
                <div class="card card-pad"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Next action</p><p class="mt-1 text-sm font-medium text-slate-900">{{ $lead->next_action->label }}</p><p class="mt-0.5 text-xs text-slate-500">Due {{ fdate($lead->next_action->due) }}</p></div>
            @endif

            @if ($lead->sales_status === 'closed_won' && demo()::can('finance.handoff'))
                <div class="card card-pad"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Finance handoff</p><p class="mt-1 text-sm font-medium">{{ ucfirst(str_replace('_', ' ', $lead->handoff?->state ?? 'new')) }}</p><p class="mt-0.5 text-xs text-slate-500">Closed Won {{ fdate($lead->closed_won_at) }}</p><a class="mt-2 inline-block text-sm text-brand-700 underline" href="{{ route('finance.handoff') }}">Open queue</a></div>
            @elseif ($lead->sales_status === 'closed_won')
                <div class="card card-pad text-sm text-slate-600"><span class="chip chip-green">Closed Won</span><p class="mt-2">Handed to Finance for the agreement. Agreement and billing details are not shown for your role.</p></div>
            @endif

            @if (demo()::is('admin'))
                <div class="card">
                    <div class="card-head"><h3 class="card-title">Privacy &amp; overrides</h3><button type="button" class="btn btn-secondary btn-xs" @click="$store.modal.open('visibility')">Add override</button></div>
                    <ul class="divide-y divide-slate-100 text-sm">
                        @forelse ($overrides as $o)
                            <li class="px-5 py-3"><div class="flex items-center justify-between gap-2"><p class="font-medium">{{ $o->field_group }}</p><x-status dim="misc" :value="$o->status === 'active' ? $o->effect : $o->status" /></div><p class="mt-0.5 text-xs text-slate-500">{{ $o->applies_to }}</p><p class="mt-1 text-xs text-slate-600">“{{ $o->reason }}”</p></li>
                        @empty<li class="px-5 py-4 text-sm text-slate-500">No record-level overrides. Default role rules apply.</li>@endforelse
                    </ul>
                    @if ($lead->locked)<div class="border-t border-slate-100 p-3"><button type="button" class="btn btn-secondary btn-sm w-full" @click="$store.modal.open('reopen')"><x-ico name="lock-open" class="size-4" /> Reopen submitted fields…</button></div>@endif
                </div>
            @endif
        </aside>
    </div>

    {{-- ================= Modals ================= --}}
    <x-slot:modals>
        <x-reject-modal :reasons="$reasons" />

        <x-modal name="status" title="Change sales status" description="Not Interested needs a reason; Closed Lost needs a reason and a note. History is always kept.">
            <form method="POST" action="{{ route('leads.status', $lead->id) }}" x-data="{ customReason: @js(collect(\App\Support\Statuses::all('sales'))->filter(fn ($v) => $v[2]['reason'] ?? false)->keys()->values()), to: '', reason: '', notes: '', touched: false, get needReason() { return ['not_interested', 'closed_lost'].includes(this.to) || this.to.startsWith('custom_') && customReason.includes(this.to) }, get needNotes() { return this.to === 'closed_lost' } }"
                  @submit="touched = true; if (!to || (needReason && !reason) || (needNotes && notes.trim().length < 5)) $event.preventDefault()">
                @csrf
                <div class="space-y-4">
                    <x-field label="New sales status" name="to" :required="true">
                        <select id="to" name="to" x-model="to" class="select" :class="{ 'input-error': touched && !to }">
                            <option value="">Select…</option>
                            @foreach (\App\Support\Statuses::options('sales') as $k => $v)@if ($k !== $lead->sales_status)<option value="{{ $k }}">{{ $v[0] }}</option>@endif @endforeach
                        </select>
                        <p class="err" x-show="touched && !to" x-cloak>Choose a status.</p>
                    </x-field>
                    <div x-show="to === 'closed_won'" x-cloak class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">Closed Won adds this deal to the Finance queue. It does <strong>not</strong> mean an agreement is signed or payment received.</div>
                    <div x-show="needReason" x-cloak><x-field label="Reason" name="reason" :required="true">
                        <select id="reason" name="reason" x-model="reason" class="select" :class="{ 'input-error': touched && needReason && !reason }"><option value="">Select…</option>@foreach ($lostReasons as $r)<option>{{ $r }}</option>@endforeach</select>
                        <p class="err" x-show="touched && needReason && !reason" x-cloak>A reason is mandatory.</p></x-field></div>
                    <x-field label="Notes" name="notes"><textarea id="notes" name="notes" x-model="notes" rows="3" class="textarea" :class="{ 'input-error': touched && needNotes && notes.trim().length < 5 }" placeholder="Optional details…"></textarea>
                        <p class="err" x-show="touched && needNotes && notes.trim().length < 5" x-cloak>Closed Lost needs a note (min. 5 characters).</p></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Update status</button></div>
            </form>
        </x-modal>

        <x-modal name="activity" title="Add note or call" description="New activity is stored separately and never edits the original submission.">
            <form method="POST" action="{{ route('leads.activity', $lead->id) }}" x-data="{ title: '', touched: false }" @submit="touched = true; if (!title.trim()) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Type" name="type"><select id="type" name="type" class="select"><option value="call">Call</option><option value="note">Note</option><option value="email">Email summary</option></select></x-field>
                    <x-field label="Outcome" name="outcome"><select id="outcome" name="outcome" class="select"><option value="">—</option><option>Connected</option><option>No answer</option><option>Voicemail</option><option>Positive</option><option>Needs follow-up</option></select></x-field>
                    <x-field label="Title" name="title" :required="true" class="sm:col-span-2"><input id="title" name="title" x-model="title" class="input" :class="{ 'input-error': touched && !title.trim() }" placeholder="e.g. Discussed pricing options"><p class="err" x-show="touched && !title.trim()" x-cloak>Add a short title.</p></x-field>
                    <x-field label="Details" name="body" class="sm:col-span-2"><textarea id="body" name="body" rows="4" class="textarea"></textarea></x-field>
                    <x-field label="Next action" name="next_label"><input id="next_label" name="next_label" class="input" placeholder="e.g. Send proposal"></x-field>
                    <x-field label="Due date" name="next_due"><input id="next_due" name="next_due" type="date" class="input"></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Add to timeline</button></div>
            </form>
        </x-modal>

        <x-modal name="audit-request" title="Request audit, guidance or quotation" description="Creates a new linked request. Earlier requests and reports are kept untouched.">
            <form method="POST" action="{{ route('audits.store') }}" x-data="{ notes: '', touched: false }" @submit="touched = true; if (notes.trim().length < 10) $event.preventDefault()">
                @csrf <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Request type" name="type" :required="true"><select id="type" name="type" class="select"><option value="audit">Audit</option><option value="guidance">Guidance</option><option value="quotation">Quotation</option></select></x-field>
                    <x-field label="Priority" name="priority"><select id="priority" name="priority" class="select"><option value="normal">Normal</option><option value="high">High</option><option value="low">Low</option></select></x-field>
                    <x-field label="Target date" name="due" class="sm:col-span-2"><input id="due" name="due" type="date" class="input"></x-field>
                    <x-field label="What do you need?" name="notes" :required="true" class="sm:col-span-2"><textarea id="notes" name="notes" x-model="notes" rows="4" class="textarea" :class="{ 'input-error': touched && notes.trim().length < 10 }" placeholder="Describe the question or scope…"></textarea><p class="err" x-show="touched && notes.trim().length < 10" x-cloak>Please add at least 10 characters.</p></x-field>
                </div>
                <p class="mt-3 text-xs text-slate-500">Assigned automatically to the project auditor{{ $auditor ? ' ('.$auditor->name.')' : '' }}.</p>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Submit request</button></div>
            </form>
        </x-modal>

        @if ($lead->review_status === 'accepted' && demo()::can('leads.create'))
        <x-modal name="email-summary" title="Email a summary to the client" description="Simulated — the message is recorded on the timeline but never sent." size="lg">
            <form method="POST" action="{{ route('leads.email', $lead->id) }}" x-data="{ to: @js($primary?->email ?? ''), cc: @js(demo()::user()->email), subject: @js('Summary of our conversation — '.$customer->business_name), body: @js('Hi '.explode(' ', str_replace('Dr. ', '', $primary?->name ?? 'there'))[0].",\n\nThanks for your time today. A quick summary of what we discussed and the agreed next steps:\n\n- \n- \n\nKind regards,\n".demo()::user()->name), touched: false }"
                  @submit="touched = true; if (!to.trim() || !subject.trim() || body.trim().length < 10) $event.preventDefault()">
                @csrf
                <div class="rounded-lg bg-fuchsia-50 p-3 text-xs text-fuchsia-900"><x-demo-tag /> Recipient, CC, subject, body and a simulated send result are logged on the activity timeline.</div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-field label="To" name="to" :required="true"><input id="to" name="to" x-model="to" class="input" :class="{ 'input-error': touched && !to.trim() }"><p class="err" x-show="touched && !to.trim()" x-cloak>Add a recipient.</p></x-field>
                    <x-field label="CC (optional)" name="cc"><input id="cc" name="cc" x-model="cc" class="input"></x-field>
                    <x-field label="Subject" name="subject" :required="true" class="sm:col-span-2"><input id="subject" name="subject" x-model="subject" class="input" :class="{ 'input-error': touched && !subject.trim() }"></x-field>
                    <x-field label="Message" name="body" :required="true" class="sm:col-span-2"><textarea id="body" name="body" x-model="body" rows="7" class="textarea" :class="{ 'input-error': touched && body.trim().length < 10 }"></textarea></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary"><x-ico name="paper-airplane" class="size-4" /> Send (demo simulation)</button></div>
            </form>
        </x-modal>
        @endif

        <x-modal name="contact" title="Add contact" description="Contacts belong to the business and can be marked primary later.">
            <form method="POST" action="{{ route('customers.contacts.store', $customer->id) }}" x-data="{ name: '', touched: false }" @submit="touched = true; if (!name.trim()) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Name" name="name" :required="true"><input id="name" name="name" x-model="name" class="input" :class="{ 'input-error': touched && !name.trim() }"><p class="err" x-show="touched && !name.trim()" x-cloak>Name is required.</p></x-field>
                    <x-field label="Designation" name="designation"><input id="designation" name="designation" class="input"></x-field>
                    <x-field label="Email" name="email"><input id="email" name="email" type="email" class="input"></x-field>
                    <x-field label="Phone" name="phone"><input id="phone" name="phone" class="input"></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Add contact</button></div>
            </form>
        </x-modal>

        @if (demo()::is('admin'))
        <x-modal name="visibility" title="Record-level visibility override" description="Every override needs an audit reason and can be revoked.">
            <form method="POST" action="{{ route('leads.visibility', $lead->id) }}" x-data="{ reason: '', touched: false }" @submit="touched = true; if (reason.trim().length < 10) $event.preventDefault()">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Field group" name="group_key"><select id="group_key" name="group_key" class="select"><option value="upsell">Services &amp; upsell details</option><option value="notes">Internal call notes</option><option value="agreement">Agreement summary</option><option value="commission">Commission details</option></select></x-field>
                    <x-field label="Effect" name="effect"><select id="effect" name="effect" class="select"><option value="restricted">Restrict</option><option value="granted">Grant access</option></select></x-field>
                    <x-field label="Applies to" name="applies_to" class="sm:col-span-2" help="Restricting “Services & upsell details” hides it from the original Agent, Team Lead and Manager."><input id="applies_to" name="applies_to" class="input" value="Original Agent, Team Lead and Manager"></x-field>
                    <x-field label="Expires (optional)" name="expires"><input id="expires" name="expires" type="date" class="input"></x-field>
                    <x-field label="Audit reason" name="reason" :required="true" class="sm:col-span-2"><textarea id="reason" name="reason" x-model="reason" rows="3" class="textarea" :class="{ 'input-error': touched && reason.trim().length < 10 }"></textarea><p class="err" x-show="touched && reason.trim().length < 10" x-cloak>Reason must be at least 10 characters.</p></x-field>
                </div>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Save override</button></div>
            </form>
        </x-modal>
        <x-modal name="reopen" title="Reopen submitted fields" description="Lets the owner edit only the groups you select. Logged with your reason.">
            <form method="POST" action="{{ route('leads.reopen', $lead->id) }}" x-data="{ reason: '', touched: false, groups: [] }" @submit="touched = true; if (reason.trim().length < 10 || !groups.length) $event.preventDefault()">
                @csrf
                <div class="space-y-2">
                    @foreach (['business' => 'Business details', 'contacts' => 'Contacts', 'answers' => 'Qualification answers'] as $g => $gl)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="check" name="groups[]" value="{{ $g }}" x-model="groups"> {{ $gl }}</label>
                    @endforeach
                    <p class="err" x-show="touched && !groups.length" x-cloak>Select at least one group.</p>
                </div>
                <x-field label="Reason" name="reopen_reason" :required="true" class="mt-4"><textarea id="reopen_reason" name="reason" x-model="reason" rows="3" class="textarea" :class="{ 'input-error': touched && reason.trim().length < 10 }"></textarea><p class="err" x-show="touched && reason.trim().length < 10" x-cloak>Reason must be at least 10 characters.</p></x-field>
                <div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-secondary" @click="$store.modal.close()">Cancel</button><button class="btn btn-primary">Reopen fields</button></div>
            </form>
        </x-modal>
        @endif
    </x-slot:modals>
</x-app-layout>
