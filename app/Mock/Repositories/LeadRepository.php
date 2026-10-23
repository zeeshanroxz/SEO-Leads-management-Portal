<?php

namespace App\Mock\Repositories;

use App\Contracts\LeadRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\MockData;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Leads / opportunities, customers, contacts and duplicate matching. */
class LeadRepository extends MockRepository implements LeadRepositoryContract
{
    public function __construct(
        private DirectoryRepository $dir,
        private FormRepository $forms,
    ) {}

    // ---------------------------------------------------------------- reads

    /** All leads, unscoped and enriched. Internal use - views should call scoped(). */
    public function all(): Collection
    {
        $customers = $this->rows('customers')->keyBy('id');

        return $this->rows('leads')->map(function (Record $l) use ($customers) {
            $l->customer = $customers[$l->customer_id] ?? null;
            $l->owner = $this->dir->user($l->owner_id);
            $l->team_lead = $this->dir->user($l->team_lead_id);
            $l->manager = $this->dir->user($l->manager_id);
            $l->project = $this->dir->project($l->project_id);
            $l->services = collect($l->service_ids ?? [])->map(fn ($s) => $this->dir->service($s))->filter()->values()->all();
            $l->locked = $l->review_status === 'accepted';

            return $l;
        })->sortByDesc('created_at')->values();
    }

    /** Leads visible to the current demo role (UI simulation of server-side scoping). */
    public function scoped(): Collection
    {
        $me = Demo::id();
        $all = $this->all();

        return match (Demo::roleKey()) {
            'admin' => $all,
            'finance' => $all->where('sales_status', 'closed_won')->values(),
            'auditor' => $all->filter(fn ($l) => app(AuditRepository::class)->forLead($l->id)->contains('assigned_to', $me))->values(),
            'manager' => $all->where('manager_id', $me)->values(),
            'team_lead' => $all->filter(fn ($l) => $l->team_lead_id === $me || $l->owner_id === $me)->values(),
            default => $all->where('owner_id', $me)->values(),
        };
    }

    public function find(?string $id): ?Record
    {
        return $id ? $this->all()->firstWhere('id', $id) : null;
    }

    public function findScoped(?string $id): ?Record
    {
        return $id ? $this->scoped()->firstWhere('id', $id) : null;
    }

    public function forCustomer(string $customerId): Collection
    {
        return $this->all()->where('customer_id', $customerId)->values();
    }

    public function customers(): Collection
    {
        return $this->rows('customers');
    }

    /** Customers having at least one lead in the current scope. */
    public function customersScoped(): Collection
    {
        $ids = $this->scoped()->pluck('customer_id')->unique()->all();
        $leads = $this->scoped();

        return $this->customers()->filter(fn ($c) => in_array($c->id, $ids, true))->map(function ($c) use ($leads) {
            $c->lead_count = $leads->where('customer_id', $c->id)->count();
            $c->primary = collect($c->contacts)->firstWhere('primary', true);

            return $c;
        })->values();
    }

    public function customer(?string $id): ?Record
    {
        return $this->row('customers', $id);
    }

    public function reviewQueue(): Collection
    {
        $pending = $this->all()->where('review_status', 'pending_review');
        $me = Demo::id();

        return match (Demo::roleKey()) {
            'admin' => $pending->values(),
            'manager' => $pending->where('manager_id', $me)->values(),
            'team_lead' => $pending->filter(fn ($l) => $l->owner_id === $me || ($this->teamLeadsMayReviewTeam() && $l->team_lead_id === $me))->values(),
            default => collect(),
        };
    }

    public function teamLeadsMayReviewTeam(): bool
    {
        return (bool) DemoStore::flag('tl_review_team', false);
    }

    /** Can the current demo user accept/reject this lead? */
    public function canReview(Record $lead): bool
    {
        if ($lead->review_status !== 'pending_review') {
            return false;
        }
        $me = Demo::id();

        return match (Demo::roleKey()) {
            'admin' => true,
            'manager' => $lead->manager_id === $me,
            'team_lead' => $lead->owner_id === $me || ($this->teamLeadsMayReviewTeam() && $lead->team_lead_id === $me),
            default => false,
        };
    }

    public function closedWon(): Collection
    {
        return $this->all()->where('sales_status', 'closed_won')->sortByDesc('closed_won_at')->values();
    }

    // ------------------------------------------------------------ duplicates

    public static function normPhone(?string $p): string
    {
        $d = preg_replace('/\D+/', '', (string) $p);

        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    public static function normEmail(?string $e): string
    {
        return strtolower(trim((string) $e));
    }

    public static function normDomain(?string $w): string
    {
        $w = strtolower(trim((string) $w));
        if ($w === '') {
            return '';
        }
        if (str_contains($w, '@')) {
            $w = substr(strrchr($w, '@'), 1);
        }
        $w = preg_replace('#^https?://#', '', $w);
        $w = preg_replace('#^www\.#', '', $w);

        return rtrim(explode('/', $w)[0], '.');
    }

    public static function normName(?string $n): string
    {
        $n = strtolower((string) $n);
        $n = preg_replace('/\b(ltd|llc|inc|co|group|the|and|&|partners|company)\b/', ' ', $n);

        return preg_replace('/[^a-z0-9]+/', '', $n);
    }

    /**
     * Demo duplicate matching on normalised phone, email, company name and website domain.
     * Permission-safe: details are only returned for records the current role may view.
     *
     * @return array<int, array{customer_id:string,matched_on:array,visible:bool,details:?array}>
     */
    public function duplicates(array $input, ?string $ignoreCustomerId = null): array
    {
        $email = self::normEmail($input['business_email'] ?? null);
        $phone = self::normPhone($input['business_phone'] ?? null);
        $domain = self::normDomain($input['website'] ?? null) ?: ($email ? self::normDomain($email) : '');
        $name = self::normName($input['business_name'] ?? null);
        $scoped = $this->scoped()->pluck('id')->all();
        $matches = [];

        foreach ($this->customers() as $c) {
            if ($c->id === $ignoreCustomerId) {
                continue;
            }
            $on = [];
            if ($email && self::normEmail($c->business_email) === $email) {
                $on[] = 'Business email';
            }
            if ($phone && strlen($phone) >= 7 && self::normPhone($c->business_phone) === $phone) {
                $on[] = 'Business phone';
            }
            if ($domain && self::normDomain($c->website) === $domain && ! in_array($domain, ['gmail.com', 'outlook.com', 'yahoo.com'], true)) {
                $on[] = 'Website domain';
            }
            if (strlen($name) >= 4 && self::normName($c->business_name) === $name) {
                $on[] = 'Business name';
            }
            foreach ($c->contacts as $k) {
                if ($email && self::normEmail($k->email) === $email && ! in_array('Business email', $on)) {
                    $on[] = 'Contact email';
                }
            }
            if (! $on) {
                continue;
            }
            $lead = $this->forCustomer($c->id)->sortByDesc('created_at')->first();
            $visible = Demo::is('admin') || ($lead && in_array($lead->id, $scoped, true));
            $matches[] = [
                'customer_id' => $c->id,
                'matched_on' => $on,
                'visible' => $visible,
                'details' => $visible && $lead ? [
                    'business_name' => $c->business_name,
                    'lead_id' => $lead->id,
                    'owner' => $lead->owner?->name,
                    'team' => $lead->team_lead?->name ? 'Team of '.$lead->team_lead->name : null,
                    'review_status' => status_label('lead_review', $lead->review_status),
                    'sales_status' => status_label('sales', $lead->sales_status),
                    'url' => route('leads.show', $lead->id),
                ] : null,
            ];
        }

        return $matches;
    }

    // ------------------------------------------------------------ mutations

    public function createLead(array $data, bool $submit, bool $duplicateOverride = false, ?string $overrideReason = null): Record
    {
        $now = Carbon::now()->toDateTimeString();
        $me = Demo::user();
        $project = $this->dir->project($data['project_id']);
        $version = $this->forms->publishedVersion($project->form_id);

        $answers = [];
        foreach ($version->sections as $section) {
            foreach ($section->fields as $f) {
                $v = $data['answers'][$f->key] ?? null;
                if ($v === null || $v === '' || $v === []) {
                    continue;
                }
                $answers[] = ['key' => $f->key, 'label' => $f->label, 'type' => $f->type, 'section' => $section->title, 'value' => $v];
            }
        }

        $contacts = collect($data['contacts'] ?? [])->filter(fn ($c) => ! empty($c['name']))->values()->map(fn ($c, $i) => [
            'id' => 'K-'.uniqid(), 'name' => $c['name'], 'designation' => $c['designation'] ?? null,
            'email' => $c['email'] ?? null, 'phone' => $c['phone'] ?? null, 'primary' => $i === (int) ($data['primary_contact'] ?? 0), 'notes' => null,
        ])->all();
        if ($contacts && ! collect($contacts)->contains('primary', true)) {
            $contacts[0]['primary'] = true;
        }

        $customer = DemoStore::create('customers', [
            'business_name' => $data['business_name'],
            'business_email' => $data['business_email'],
            'business_phone' => $data['business_phone'],
            'business_address' => $data['business_address'],
            'website' => $data['website'] ?? null,
            'domain' => self::normDomain($data['website'] ?? $data['business_email']),
            'industry' => $data['industry'] ?? null,
            'city' => null, 'contacts' => $contacts, 'created_at' => $now, 'status' => 'active',
        ], 'C-', 100);

        [$tl, $mgr] = match ($me->role) {
            'agent' => [$me->team_lead_id, $me->manager_id],
            'team_lead' => [$me->id, $me->manager_id],
            'manager' => [null, $me->id],
            default => [null, null],
        };

        $acts = [[
            'id' => 'ACT-'.uniqid(), 'type' => 'system', 'by' => $me->id, 'at' => $now,
            'title' => $submit ? 'Lead submitted for review' : 'Draft saved',
            'body' => $submit ? 'Submitted using form version '.$version->id.' ('.count($answers).' answers snapshotted).' : 'Draft not yet submitted.',
        ]];
        $history = $submit ? [['at' => $now, 'by' => $me->id, 'field' => 'Lead review', 'from' => 'Draft', 'to' => 'Pending Review', 'reason' => null]] : [];
        if ($duplicateOverride) {
            $acts[] = ['id' => 'ACT-'.uniqid(), 'type' => 'system', 'by' => $me->id, 'at' => $now, 'title' => 'Duplicate warning overridden (demo)', 'body' => 'Reason: '.$overrideReason];
        }

        $lead = DemoStore::create('leads', [
            'customer_id' => $customer['id'], 'project_id' => $project->id, 'service_ids' => $data['service_ids'] ?? [],
            'owner_id' => $me->id, 'team_lead_id' => $tl, 'manager_id' => $mgr, 'source' => $data['source'] ?? 'Cold call',
            'review_status' => $submit ? 'pending_review' : 'draft', 'sales_status' => 'new',
            'created_at' => $now, 'submitted_at' => $submit ? $now : null,
            'reviewed_by' => null, 'reviewed_at' => null, 'rejection_reason' => null, 'rejection_notes' => null,
            'lost_reason' => null, 'lost_notes' => null, 'parent_id' => null, 'note' => $duplicateOverride ? 'Created with duplicate override' : null,
            'closed_won_at' => null, 'locked' => false, 'form_version_id' => $version->id, 'answers' => $answers,
            'activities' => $acts, 'history' => $history,
            'next_action' => ['label' => $submit ? 'Awaiting review' : 'Finish and submit draft', 'due' => MockData::d(1)],
            'handoff' => null,
        ], 'L-', 1100);

        return $this->find($lead['id']);
    }

    /**
     * Edit a draft / pending lead (or the field groups an Admin has reopened).
     * $groups limits what may change: business, contacts, answers.
     */
    public function updateLead(string $id, array $data, array $groups): void
    {
        $lead = $this->find($id);
        $now = Carbon::now()->toDateTimeString();
        if (in_array('business', $groups, true)) {
            DemoStore::patch('customers', $lead->customer_id, [
                'business_name' => $data['business_name'], 'business_email' => $data['business_email'], 'business_phone' => $data['business_phone'],
                'business_address' => $data['business_address'], 'website' => $data['website'] ?? null,
                'domain' => self::normDomain($data['website'] ?? $data['business_email']), 'industry' => $data['industry'] ?? $lead->customer->industry,
            ]);
        }
        if (in_array('contacts', $groups, true)) {
            $contacts = collect($data['contacts'] ?? [])->filter(fn ($c) => ! empty($c['name']))->values()->map(fn ($c, $i) => [
                'id' => 'K-'.uniqid(), 'name' => $c['name'], 'designation' => $c['designation'] ?? null,
                'email' => $c['email'] ?? null, 'phone' => $c['phone'] ?? null, 'primary' => $i === (int) ($data['primary_contact'] ?? 0), 'notes' => null,
            ])->all();
            if ($contacts && ! collect($contacts)->contains('primary', true)) {
                $contacts[0]['primary'] = true;
            }
            DemoStore::patch('customers', $lead->customer_id, ['contacts' => $contacts]);
        }
        if (in_array('answers', $groups, true)) {
            $version = $this->forms->version($lead->form_version_id) ?? $this->forms->publishedVersion($lead->project->form_id);
            $answers = [];
            foreach ($version->sections as $section) {
                foreach ($section->fields as $f) {
                    $v = $data['answers'][$f->key] ?? null;
                    if ($v === null || $v === '' || $v === []) {
                        continue;
                    }
                    $answers[] = ['key' => $f->key, 'label' => $f->label, 'type' => $f->type, 'section' => $section->title, 'value' => $v];
                }
            }
            DemoStore::patch('leads', $id, ['answers' => $answers]);
        }
        DemoStore::append('leads', $id, 'activities', ['id' => $id.'-A'.uniqid(), 'type' => 'system', 'by' => Demo::id(), 'at' => $now, 'title' => 'Lead details edited', 'body' => 'Changed: '.implode(', ', $groups).'.']);
    }

    /** Admin reopens specific submitted field groups for the Agent (reason is logged). */
    public function reopenFields(string $id, array $groups, string $reason): void
    {
        $now = Carbon::now()->toDateTimeString();
        DemoStore::patch('leads', $id, ['reopened_groups' => $groups]);
        DemoStore::append('leads', $id, 'activities', ['id' => $id.'-A'.uniqid(), 'type' => 'system', 'by' => Demo::id(), 'at' => $now, 'title' => 'Submitted fields reopened by Admin', 'body' => 'Groups: '.implode(', ', $groups).'. Reason: '.$reason]);
        DemoStore::append('leads', $id, 'history', ['at' => $now, 'by' => Demo::id(), 'field' => 'Field lock', 'from' => 'Locked', 'to' => 'Reopened: '.implode(', ', $groups), 'reason' => $reason]);
        $this->audit('Reopened submitted fields', $id, $reason);
    }

    /** Field groups the current role may edit on this lead. */
    public function editableGroups(Record $lead): array
    {
        $all = ['business', 'contacts', 'answers'];
        if (Demo::is('admin')) {
            return $lead->review_status === 'rejected' ? [] : $all;
        }
        $isOwner = $lead->owner_id === Demo::id();
        if (! $isOwner && ! Demo::is('manager', 'team_lead')) {
            return [];
        }
        if (in_array($lead->review_status, ['draft', 'pending_review'], true)) {
            return $isOwner || $lead->manager_id === Demo::id() || $lead->team_lead_id === Demo::id() ? $all : [];
        }
        if ($lead->review_status === 'accepted') {
            return $isOwner || Demo::is('manager', 'team_lead') ? array_values(array_intersect($all, (array) ($lead->reopened_groups ?? []))) : [];
        }

        return [];
    }

    public function accept(string $id): void
    {
        $me = Demo::user();
        $now = Carbon::now()->toDateTimeString();
        DemoStore::patch('leads', $id, [
            'review_status' => 'accepted', 'reviewed_by' => $me->id, 'reviewed_at' => $now,
            'locked' => true, 'next_action' => ['label' => 'Make first contact call', 'due' => MockData::d(1)],
        ]);
        DemoStore::append('leads', $id, 'activities', ['id' => $id.'-A'.uniqid(), 'type' => 'status', 'by' => $me->id, 'at' => $now, 'title' => 'Lead accepted', 'body' => 'Submitted lead fields are now locked for the Agent.']);
        DemoStore::append('leads', $id, 'history', ['at' => $now, 'by' => $me->id, 'field' => 'Lead review', 'from' => 'Pending Review', 'to' => 'Accepted', 'reason' => null]);
        $this->audit('Accepted lead', $id);
    }

    public function reject(string $id, string $reason, string $notes): void
    {
        $me = Demo::user();
        $now = Carbon::now()->toDateTimeString();
        DemoStore::patch('leads', $id, [
            'review_status' => 'rejected', 'reviewed_by' => $me->id, 'reviewed_at' => $now,
            'rejection_reason' => $reason, 'rejection_notes' => $notes, 'next_action' => null,
        ]);
        DemoStore::append('leads', $id, 'activities', ['id' => $id.'-A'.uniqid(), 'type' => 'status', 'by' => $me->id, 'at' => $now, 'title' => 'Lead rejected', 'body' => 'Reason: '.$reason.'. '.$notes]);
        DemoStore::append('leads', $id, 'history', ['at' => $now, 'by' => $me->id, 'field' => 'Lead review', 'from' => 'Pending Review', 'to' => 'Rejected', 'reason' => $reason.' - '.$notes]);
        $this->audit('Rejected lead', $id, $reason);
    }

    public function submitDraft(string $id): void
    {
        $now = Carbon::now()->toDateTimeString();
        DemoStore::patch('leads', $id, ['review_status' => 'pending_review', 'submitted_at' => $now, 'next_action' => ['label' => 'Awaiting review', 'due' => MockData::d(1)]]);
        DemoStore::append('leads', $id, 'activities', ['id' => $id.'-A'.uniqid(), 'type' => 'system', 'by' => Demo::id(), 'at' => $now, 'title' => 'Lead submitted for review', 'body' => 'Draft submitted.']);
        DemoStore::append('leads', $id, 'history', ['at' => $now, 'by' => Demo::id(), 'field' => 'Lead review', 'from' => 'Draft', 'to' => 'Pending Review', 'reason' => null]);
    }

    public function changeSalesStatus(string $id, string $to, ?string $reason, ?string $notes): void
    {
        $lead = $this->find($id);
        $now = Carbon::now()->toDateTimeString();
        $patch = ['sales_status' => $to];
        if (in_array($to, ['closed_lost', 'not_interested'], true)) {
            $patch += ['lost_reason' => $reason, 'lost_notes' => $notes, 'next_action' => null];
        }
        if ($to === 'closed_won') {
            $patch += ['closed_won_at' => $now, 'handoff' => ['state' => 'new', 'received_at' => $now, 'assigned_to' => null], 'next_action' => ['label' => 'Finance handoff in progress', 'due' => MockData::d(5)]];
        }
        DemoStore::patch('leads', $id, $patch);
        DemoStore::append('leads', $id, 'activities', ['id' => $id.'-A'.uniqid(), 'type' => 'status', 'by' => Demo::id(), 'at' => $now, 'title' => 'Status: '.status_label('sales', $to), 'body' => trim(($reason ? 'Reason: '.$reason.'. ' : '').$notes)]);
        DemoStore::append('leads', $id, 'history', ['at' => $now, 'by' => Demo::id(), 'field' => 'Sales status', 'from' => status_label('sales', $lead->sales_status), 'to' => status_label('sales', $to), 'reason' => $reason ? $reason.($notes ? ' - '.$notes : '') : null]);
        $this->audit('Changed sales status to '.status_label('sales', $to), $id, $reason);
    }

    public function addActivity(string $id, string $type, string $title, ?string $body, ?string $outcome = null, ?array $next = null): void
    {
        $now = Carbon::now()->toDateTimeString();
        DemoStore::append('leads', $id, 'activities', array_filter([
            'id' => $id.'-A'.uniqid(), 'type' => $type, 'by' => Demo::id(), 'at' => $now, 'title' => $title, 'body' => $body, 'outcome' => $outcome,
        ], fn ($v) => $v !== null));
        if ($next) {
            DemoStore::patch('leads', $id, ['next_action' => $next]);
        }
    }

    public function addContact(string $customerId, array $contact): void
    {
        DemoStore::append('customers', $customerId, 'contacts', $contact + ['id' => $customerId.'-K'.uniqid(), 'primary' => false]);
    }

    private function audit(string $action, string $entity, ?string $reason = null): void
    {
        app(PortalRepository::class)->log($action, $entity, $reason, 'lead');
    }
}
