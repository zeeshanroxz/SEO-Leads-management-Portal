<?php

namespace App\Http\Controllers;

use App\Mock\Record;
use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\AuditRepository;
use App\Mock\Repositories\CalendarRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\FinanceRepository;
use App\Mock\Repositories\FormRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use App\Support\Statuses;
use App\Support\Visibility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public const REJECTION_REASONS = ['Duplicate / already worked', 'Outside service area', 'Not a decision maker', 'Incomplete or inaccurate details', 'Does not fit the project', 'Do-not-contact request', 'Other'];

    public const LOST_REASONS = ['Budget', 'Chose a competitor', 'Already has an agency', 'No longer needs the service', 'Timing', 'Unresponsive', 'Other'];

    public function __construct(
        private LeadRepository $leads,
        private FormRepository $forms,
        private DirectoryRepository $dir,
        private AuditRepository $audits,
        private CalendarRepository $calendar,
        private AgreementRepository $agreements,
        private FinanceRepository $finance,
        private PortalRepository $portal,
    ) {}

    // ------------------------------------------------------------------ list

    public function index(Request $request)
    {
        $leads = $this->leads->scoped();
        if (Demo::is('finance')) {
            $leads->each(fn ($l) => $l->agreement = $this->agreements->forLead($l->id)->first());
        }

        return view('pages.leads.index', [
            'leads' => $leads,
            'projects' => $this->dir->projects(),
            'owners' => $leads->pluck('owner')->filter()->unique('id')->values(),
            'state' => $request->query('state'),
        ]);
    }

    public function review()
    {
        $queue = $this->leads->reviewQueue();
        $recent = $this->leads->scoped()->whereIn('review_status', ['accepted', 'rejected'])->sortByDesc('reviewed_at')->take(8);

        return view('pages.leads.review', [
            'queue' => $queue, 'recent' => $recent,
            'reasons' => self::REJECTION_REASONS, 'tlTeamReview' => $this->leads->teamLeadsMayReviewTeam(),
        ]);
    }

    // ---------------------------------------------------------------- detail

    public function show(string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l, 403, 'This lead is outside your visibility scope ('.Demo::roleLabel().'). In production the server would not return it at all.');

        $audits = $this->audits->forLead($l->id);
        $agreements = Demo::can('agreements.view') ? $this->agreements->forLead($l->id) : collect();
        $commissions = $this->finance->commissionsScoped()->where('lead_id', $l->id)->values();

        return view('pages.leads.show', [
            'lead' => $l,
            'customer' => $l->customer,
            'audits' => $audits,
            'agreements' => $agreements,
            'commissions' => $commissions,
            'signedMrr' => Demo::can('revenue.mrr') && ! Demo::can('agreements.view') ? $this->agreements->signedRecurring()->firstWhere('lead_id', $l->id) : null,
            'events' => $this->calendar->all()->where('lead_id', $l->id)->when(! Demo::is('admin'), fn ($c) => $c->filter(fn ($e) => $this->calendar->scoped()->contains('id', $e->id)))->values(),
            'canReview' => $this->leads->canReview($l),
            'editable' => $this->leads->editableGroups($l),
            'upsellRestricted' => Visibility::restricted($l, 'upsell'),
            'notesGranted' => Visibility::granted($l, 'notes'),
            'overrides' => Demo::is('admin') ? $this->portal->overrides()->where('lead_id', $l->id)->values() : collect(),
            'reasons' => self::REJECTION_REASONS,
            'lostReasons' => self::LOST_REASONS,
            'auditor' => $l->project?->auditor_ids ? $this->dir->user($l->project->auditor_ids[0]) : null,
            'users' => $this->dir->users()->where('status', 'active'),
        ]);
    }

    // ---------------------------------------------------------- create/edit

    private function wizardConfig(?Record $lead = null): array
    {
        $fixed = $this->forms->fixedFields();

        return [
            'projects' => $this->dir->projects()->where('status', 'active')->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'audit_policy' => $p->audit_policy,
                'services' => $p->service_ids,
            ])->values()->all(),
            'services' => $this->dir->services()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'project_id' => $s->project_id])->values()->all(),
            'forms' => $this->forms->publishedByProject(),
            'fixed' => $fixed->mapWithKeys(fn ($f) => [$f->id => ['label' => $f->label, 'required' => (bool) $f->required, 'type' => $f->type]])->all(),
            'sources' => ['Cold call', 'Inbound call', 'Referral', 'Website form', 'Social outreach', 'Trade show'],
            'duplicateUrl' => route('leads.duplicates'),
            'canOverride' => Demo::is('admin'),
        ];
    }

    public function create(Request $request)
    {
        return view('pages.leads.form', [
            'mode' => 'create', 'lead' => null, 'config' => $this->wizardConfig(),
            'initial' => ['project_id' => $request->query('project', ''), 'contacts' => [['name' => '', 'designation' => '', 'email' => '', 'phone' => '']]],
            'locked' => [], 'editable' => ['business', 'contacts', 'answers'],
        ]);
    }

    public function edit(string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l, 403, 'This lead is outside your visibility scope.');
        $editable = $this->leads->editableGroups($l);
        $c = $l->customer;

        $answers = [];
        foreach ($l->answers as $a) {
            $answers[$a->key] = $a->value;
        }

        return view('pages.leads.form', [
            'mode' => 'edit', 'lead' => $l, 'config' => $this->wizardConfig($l),
            'initial' => [
                'project_id' => $l->project_id, 'source' => $l->source, 'service_ids' => $l->service_ids,
                'business_name' => $c->business_name, 'business_email' => $c->business_email, 'business_phone' => $c->business_phone,
                'business_address' => $c->business_address, 'website' => $c->website, 'industry' => $c->industry,
                'contacts' => collect($c->contacts)->map(fn ($k) => ['name' => $k->name, 'designation' => $k->designation, 'email' => $k->email, 'phone' => $k->phone])->values()->all(),
                'primary_contact' => collect($c->contacts)->search(fn ($k) => $k->primary) ?: 0,
                'answers' => $answers,
            ],
            'editable' => $editable,
        ]);
    }

    private function validated(Request $request, bool $submit, array $groups = ['business', 'contacts', 'answers']): array
    {
        $fixed = $this->forms->fixedFields()->keyBy('id');
        $req = fn ($id) => ($submit && ($fixed[$id]->required ?? false)) ? 'required' : 'nullable';
        $rules = ['project_id' => ['required', Rule::in($this->dir->projects()->pluck('id')->all())], 'source' => 'nullable|string|max:60', 'service_ids' => 'nullable|array'];
        $messages = [];

        if (in_array('business', $groups, true)) {
            $rules += [
                'business_name' => 'required|string|max:160',
                'business_email' => [$req('business_email'), 'email', 'max:160'],
                'business_phone' => [$req('business_phone'), 'string', 'max:40'],
                'business_address' => [$req('business_address'), 'string', 'max:255'],
                'website' => 'nullable|string|max:200', 'industry' => 'nullable|string|max:80',
            ];
        }
        if (in_array('contacts', $groups, true)) {
            $rules += [
                'contacts' => 'nullable|array|max:10',
                'contacts.0.name' => [$req('contact_name'), 'string', 'max:120'],
                'contacts.0.designation' => [$req('contact_designation'), 'string', 'max:120'],
                'contacts.*.email' => 'nullable|email|max:160', 'contacts.*.phone' => 'nullable|string|max:40',
            ];
            $messages += ['contacts.0.name.required' => 'Contact name is required.', 'contacts.0.designation.required' => 'Contact designation is required.'];
        }
        if (in_array('answers', $groups, true)) {
            $version = $this->forms->publishedVersion($this->dir->project($request->input('project_id'))?->form_id);
            $answers = (array) $request->input('answers', []);
            foreach ($version?->sections ?? [] as $section) {
                foreach ($section->fields as $f) {
                    if (! $f->active) {
                        continue;
                    }
                    if ($f->condition && ($answers[$f->condition->key] ?? null) !== $f->condition->equals) {
                        continue;
                    }
                    $key = 'answers.'.$f->key;
                    $r = [($submit && $f->required) ? 'required' : 'nullable'];
                    $r = array_merge($r, match ($f->type) {
                        'number', 'percentage' => ['numeric', 'min:'.($f->min ?? 0), 'max:'.($f->max ?? ($f->type === 'percentage' ? 100 : 1000000))],
                        'radio', 'dropdown' => [Rule::in($f->options)],
                        'checkbox' => ['array'],
                        default => ['string', 'max:2000'],
                    });
                    $rules[$key] = $r;
                    $messages["$key.required"] = '"'.$f->label.'" is required.';
                    $messages["$key.numeric"] = '"'.$f->label.'" must be a number.';
                    $messages["$key.min"] = '"'.$f->label.'" is below the allowed minimum.';
                    $messages["$key.max"] = '"'.$f->label.'" is above the allowed maximum.';
                    if ($f->type === 'checkbox') {
                        $rules[$key.'.*'] = [Rule::in($f->options)];
                    }
                }
            }
        }

        return $request->validate($rules, $messages);
    }

    public function store(Request $request)
    {
        $submit = $request->input('action') === 'submit';
        $data = $this->validated($request, $submit);
        $data['service_ids'] = $request->input('service_ids', []);
        $data['primary_contact'] = (int) $request->input('primary_contact', 0);

        $dups = $this->leads->duplicates($data);
        $override = false;
        $reason = null;
        if ($dups && $submit) {
            $reason = trim((string) $request->input('override_reason'));
            if (Demo::is('admin') && strlen($reason) >= 10) {
                $override = true;
            } else {
                return back()->withInput()->withErrors(['duplicate' => 'Duplicate lead blocked: '.count($dups).' matching record(s) found. '.(Demo::is('admin') ? 'Provide an override reason (min 10 characters) to continue.' : 'Ask an Admin to override with a reason.')]);
            }
        }

        $lead = $this->leads->createLead($data, $submit, $override, $reason);
        $this->portal->log($submit ? 'Lead submitted' : 'Draft saved', $lead->id, $override ? 'Duplicate override: '.$reason : null, 'lead');

        return redirect()->route('leads.show', $lead->id)->with('toast', ['message' => $submit ? 'Lead submitted for review. It is now in the review queue.' : 'Draft saved. Submit it for review when it is ready.']);
    }

    public function update(Request $request, string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l, 403);
        $groups = $this->leads->editableGroups($l);
        abort_if(! $groups, 403, 'Submitted lead fields are locked after acceptance. Add updates as activity notes, or ask an Admin to reopen specific fields.');
        $data = $this->validated($request, $l->review_status !== 'draft', $groups);
        $data['primary_contact'] = (int) $request->input('primary_contact', 0);
        $this->leads->updateLead($l->id, $data, $groups);

        return redirect()->route('leads.show', $l->id)->with('toast', ['message' => 'Lead updated.']);
    }

    public function submit(string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l && $l->review_status === 'draft' && ($l->owner_id === Demo::id() || Demo::is('admin')), 403);
        $this->leads->submitDraft($l->id);

        return redirect()->route('leads.show', $l->id)->with('toast', ['message' => 'Draft submitted for review.']);
    }

    public function duplicates(Request $request)
    {
        $matches = $this->leads->duplicates($request->only(['business_name', 'business_email', 'business_phone', 'website']), $request->input('ignore_customer'));

        return response()->json([
            'matches' => $matches, 'blocked' => count($matches) > 0, 'can_override' => Demo::is('admin'),
            'note' => 'Demo matching on normalised email, phone, website domain and business name. Details are hidden for records outside your scope.',
        ]);
    }

    // --------------------------------------------------------- state changes

    public function accept(Request $request, string $lead)
    {
        $l = $this->leads->find($lead);
        abort_unless($l && $this->leads->canReview($l), 403, 'You cannot accept this lead.');
        $this->leads->accept($l->id);

        return redirect()->back()->with('toast', ['message' => $l->customer->business_name.' accepted. Submitted fields are now read-only for the Agent.']);
    }

    public function reject(Request $request, string $lead)
    {
        $l = $this->leads->find($lead);
        abort_unless($l && $this->leads->canReview($l), 403, 'You cannot reject this lead.');
        $data = $request->validate([
            'reason' => ['required', Rule::in(self::REJECTION_REASONS)],
            'notes' => 'required|string|min:10|max:1000',
        ], ['notes.required' => 'A rejection note is mandatory.', 'notes.min' => 'Please explain the rejection (at least 10 characters).']);
        $this->leads->reject($l->id, $data['reason'], $data['notes']);

        return redirect()->back()->with('toast', ['message' => $l->customer->business_name.' rejected. The Agent can see the reason.', 'type' => 'info']);
    }

    public function status(Request $request, string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l && $l->review_status === 'accepted', 403, 'Only accepted leads have a sales pipeline.');
        $allowed = array_keys(Statuses::options('sales'));
        $to = $request->validate(['to' => ['required', Rule::in($allowed)]])['to'];
        $needsReason = in_array($to, ['not_interested', 'closed_lost'], true) || (Statuses::all('sales')[$to][2]['reason'] ?? false);
        $data = $request->validate([
            'reason' => [$needsReason ? 'required' : 'nullable', 'string', 'max:120'],
            'notes' => [$to === 'closed_lost' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], ['reason.required' => 'A reason is mandatory for '.status_label('sales', $to).'.', 'notes.required' => 'A note is mandatory for Closed Lost.']);
        $this->leads->changeSalesStatus($l->id, $to, $data['reason'] ?? null, $data['notes'] ?? null);
        $affected = ($l->sales_status === 'closed_won' && in_array($to, ['closed_lost', 'not_interested'], true))
            ? $this->finance->applyReversalPolicy($l->id, (string) ($data['reason'] ?? '')) : 0;
        if ($to === 'closed_lost' && $this->agreements->forLead($l->id)->count()) {
            foreach ($this->agreements->forLead($l->id) as $a) {
                $this->agreements->addEvent($a->id, 'Opportunity marked Closed Lost - agreement history retained, nothing deleted. Reason: '.$data['reason'], Demo::user()->name);
            }
        }

        return back()->with('toast', ['message' => 'Sales status changed to '.status_label('sales', $to).'.'.($affected ? ' '.$affected.' unpaid commission(s) were held/reversed under the configured policy; paid ones are kept for Finance review.' : '').($to === 'closed_won' ? ' The deal was added to the Finance Closed Won queue (agreement is not implied).' : '')]);
    }

    public function activity(Request $request, string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l && $l->review_status !== 'rejected', 403);
        $data = $request->validate([
            'type' => ['required', Rule::in(['call', 'note', 'email'])], 'title' => 'required|string|max:120', 'body' => 'nullable|string|max:2000',
            'outcome' => 'nullable|string|max:60', 'next_label' => 'nullable|string|max:120', 'next_due' => 'nullable|date',
        ]);
        $next = ! empty($data['next_label']) ? ['label' => $data['next_label'], 'due' => $data['next_due'] ?? now()->addDay()->toDateString()] : null;
        $this->leads->addActivity($l->id, $data['type'], $data['title'], $data['body'] ?? null, $data['outcome'] ?? null, $next);

        return back()->with('toast', ['message' => 'Activity added to the timeline. The original submission is unchanged.']);
    }

    /** SIMULATED client email summary (To / CC / subject / body). Logged on the timeline; nothing is sent. */
    public function email(Request $request, string $lead)
    {
        $l = $this->leads->findScoped($lead);
        abort_unless($l && $l->review_status === 'accepted' && Demo::can('leads.create'), 403, 'You cannot email a summary for this lead.');
        $data = $request->validate(['to' => 'required|string|max:300', 'cc' => 'nullable|string|max:300', 'subject' => 'required|string|max:160', 'body' => 'required|string|min:10|max:3000'], ['to.required' => 'Add at least one recipient.', 'body.min' => 'Write a short summary (min. 10 characters).']);
        $split = fn ($s) => collect(preg_split('/[,;\s]+/', (string) $s))->filter()->unique()->values()->all();
        $to = $split($data['to']);
        $cc = $split($data['cc'] ?? '');
        foreach (array_merge($to, $cc) as $addr) {
            if (! filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['to' => '“'.$addr.'” is not a valid email address.']);
            }
        }
        $this->leads->addActivity($l->id, 'email', 'Email summary sent (demo): '.$data['subject'], 'To: '.implode(', ', $to).($cc ? ' | CC: '.implode(', ', $cc) : '').' | Result: Demo simulation - no email was sent'."\n".$data['body']);
        $this->portal->log('Client summary emailed (demo)', $l->id, 'To: '.implode(', ', $to), 'lead');

        return back()->with('toast', ['message' => 'Summary recorded on the timeline for '.implode(', ', $to).'. No real email was sent.', 'demo' => true]);
    }

    public function visibility(Request $request, string $lead)
    {
        $data = $request->validate([
            'group_key' => ['required', Rule::in(['upsell', 'notes', 'agreement', 'commission'])], 'effect' => ['required', Rule::in(['restricted', 'granted'])],
            'applies_to' => 'required|string|max:120', 'reason' => 'required|string|min:10|max:500', 'expires' => 'nullable|date',
        ], ['reason.required' => 'An audit reason is required for every override.', 'reason.min' => 'Reason must be at least 10 characters.']);
        $labels = ['upsell' => 'Services and upsell details', 'notes' => 'Internal call notes', 'agreement' => 'Agreement summary', 'commission' => 'Commission details'];
        $this->portal->addOverride($data + ['lead_id' => $lead, 'field_group' => $labels[$data['group_key']]]);

        return back()->with('toast', ['message' => 'Visibility override saved and logged. It can be revoked at any time.']);
    }

    public function reopen(Request $request, string $lead)
    {
        $data = $request->validate(['groups' => 'required|array|min:1', 'groups.*' => Rule::in(['business', 'contacts', 'answers']), 'reason' => 'required|string|min:10|max:500'], ['groups.required' => 'Select at least one field group to reopen.']);
        $this->leads->reopenFields($lead, $data['groups'], $data['reason']);

        return back()->with('toast', ['message' => 'Selected fields reopened for the owner. The reason is in the audit trail.']);
    }
}
