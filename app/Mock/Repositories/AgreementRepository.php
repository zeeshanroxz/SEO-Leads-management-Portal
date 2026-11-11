<?php

namespace App\Mock\Repositories;

use App\Contracts\AgreementRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Agreements, reusable clauses and the (simulated) signing trail. */
class AgreementRepository extends MockRepository implements AgreementRepositoryContract
{
    public function __construct(private DirectoryRepository $dir, private LeadRepository $leads) {}

    public function all(): Collection
    {
        return $this->rows('agreements')->map(function (Record $a) {
            $a->lead = $this->leads->find($a->lead_id);
            $a->customer = $a->lead?->customer;
            $a->total_first_invoice = $a->one_time_total + $a->mrr;
            $a->creator = $this->dir->user($a->created_by);

            return $a;
        })->sortByDesc('created_at')->values();
    }

    public function find(?string $id): ?Record
    {
        return $id ? $this->all()->firstWhere('id', $id) : null;
    }

    public function forLead(string $leadId): Collection
    {
        return $this->all()->where('lead_id', $leadId)->values();
    }

    public function forCustomer(string $customerId): Collection
    {
        return $this->all()->where('customer_id', $customerId)->values();
    }

    public function clauses(): Collection
    {
        return $this->rows('clauses');
    }

    public function clause(?string $id): ?Record
    {
        return $this->row('clauses', $id);
    }

    /**
     * Fully signed customers - the ONLY agreement-derived data Managers may see
     * (MRR/MRC), without agreement content or other finance records.
     */
    public function signedRecurring(): Collection
    {
        return $this->all()->where('status', 'signed')->map(fn ($a) => Record::from([
            'agreement_ref' => $a->id, 'lead_id' => $a->lead_id, 'customer' => $a->customer?->business_name, 'customer_id' => $a->customer_id,
            'mrr' => $a->mrr, 'signed_at' => $a->signed_at, 'start_date' => $a->start_date, 'end_date' => $a->end_date,
            'owner' => $a->lead?->owner?->name, 'team_lead' => $a->lead?->team_lead?->name,
        ]))->values();
    }

    public function create(array $d): Record
    {
        return $this->insert($d);
    }

    /**
     * How an agreement may be edited, if at all. Nothing has reached the customer
     * while it is a draft, so it is edited in place. Once it has been sent the
     * customer may have seen or signed that exact text, so an edit becomes a new
     * version and the old one is Superseded (never overwritten). Signed and other
     * closed agreements are locked.
     */
    public function editMode(Record $a): ?string
    {
        return match ($a->status) {
            'draft', 'ready_to_send' => 'in_place',
            'sent', 'viewed', 'partially_signed' => 'revision',
            default => null,
        };
    }

    /** Edit a not-yet-sent agreement in place. Unchanged clauses keep the version they were snapshotted at. */
    public function update(string $id, array $d, ?string $note = null): Record
    {
        $a = $this->find($id);
        $before = $a->plain();
        $fields = $this->fields($d, $before['clauses']);
        $changes = $this->changes($before, $fields, $d);
        $signers = $before['signers'];
        $signers[0]['name'] = $d['signer_name'];
        $signers[0]['email'] = $d['signer_email'];

        $patch = $fields + ['signers' => $signers];
        $text = 'Draft edited ('.($changes ? implode(', ', $changes) : 'no field changes').')'.($note ? '. Note: '.$note : '');
        if ($a->status === 'ready_to_send') {
            $patch['status'] = 'draft';
            $text .= ' - returned from Ready to Send to Draft; re-confirm before sending';
        }
        DemoStore::patch('agreements', $id, $patch);
        $this->addEvent($id, $text, Demo::user()->name);

        return $this->find($id);
    }

    /** Edit an already-sent agreement: a new draft version is created and the old agreement is marked Superseded. */
    public function revise(string $id, array $d, ?string $note = null): Record
    {
        $old = $this->find($id);
        $before = $old->plain();
        $d['lead_id'] = $old->lead_id;
        $changes = $this->changes($before, $this->fields($d, $before['clauses']), $d);
        $now = Carbon::now()->toDateTimeString();
        $version = (int) $old->version + 1;
        $detail = ($changes ? 'Edited: '.implode(', ', $changes) : 'No field changes').($note ? '. Note: '.$note : '');

        $history = $before['versions'];
        if ($history) {
            $history[array_key_last($history)]['status'] = 'superseded';
        }
        $new = $this->insert($d, $before['clauses'], [
            'version' => $version, 'supersedes' => $old->id,
            'versions' => array_merge($history, [['no' => $version, 'status' => 'draft', 'at' => $now, 'note' => 'Revision of '.$old->id.' v'.$old->version.'. '.$detail]]),
            'events' => [['at' => $now, 'text' => 'Draft created as a revision of '.$old->id.' (v'.$old->version.' to v'.$version.'). '.$detail, 'by' => Demo::user()->name]],
        ]);
        DemoStore::patch('agreements', $old->id, ['superseded_by' => $new->id, 'versions' => $history]);
        $this->setStatus($old->id, 'superseded', 'Superseded by '.$new->id.' (v'.$version.'). '.$detail);

        return $new;
    }

    /**
     * Builder input -> the stored agreement fields (priced lines, totals, term, clause snapshots).
     * Clauses already in $keepClauses keep their snapshotted wording; newly ticked ones snapshot the current version.
     */
    private function fields(array $d, array $keepClauses = []): array
    {
        $lines = collect($d['lines'])->map(fn ($l) => ['service_id' => $l['service_id'], 'name' => $this->dir->service($l['service_id'])->name, 'price' => (float) $l['price'], 'billing' => $l['billing'], 'qty' => (int) ($l['qty'] ?? 1)]);
        $kept = collect($keepClauses)->keyBy('id');
        $snapshot = fn (?Record $c) => $c ? ['id' => $c->id, 'title' => $c->title, 'version' => $c->version, 'body' => $c->body] : null;
        $clauses = collect($d['clause_ids'] ?? [])->map(fn ($cid) => $kept->get($cid) ?? $snapshot($this->clause($cid)))->filter()->values()->all();

        return [
            'currency' => $d['currency'] ?? 'USD',
            'lines' => $lines->all(), 'mrr' => $lines->where('billing', 'recurring')->sum(fn ($l) => $l['price'] * $l['qty']),
            'one_time_total' => $lines->where('billing', 'one_time')->sum(fn ($l) => $l['price'] * $l['qty']),
            'term_months' => (int) $d['term_months'], 'start_date' => ($d['start_date'] ?? null) ?: null,
            'end_date' => ! empty($d['start_date']) ? Carbon::parse($d['start_date'])->addMonths((int) $d['term_months'])->toDateString() : null,
            'renewal' => $d['renewal'] ?? 'Auto-renews monthly after the initial term unless cancelled with 30 days notice (prototype default).', 'payment_schedule' => $d['payment_schedule'] ?? 'Monthly in advance', 'clauses' => $clauses,
        ];
    }

    /** Human-readable list of what an edit changed, for the event log. */
    private function changes(array $before, array $fields, array $d): array
    {
        $lines = fn (array $rows) => collect($rows)->map(fn ($l) => [$l['service_id'], (float) $l['price'], $l['billing'], (int) $l['qty']])->all();
        $clauseIds = fn (array $rows) => collect($rows)->pluck('id')->sort()->values()->all();

        return array_keys(array_filter([
            'service lines' => $lines($before['lines']) !== $lines($fields['lines']),
            'term' => (int) $before['term_months'] !== $fields['term_months'],
            'start date' => ($before['start_date'] ?: null) !== $fields['start_date'],
            'currency' => $before['currency'] !== $fields['currency'],
            'renewal rule' => $before['renewal'] !== $fields['renewal'],
            'payment schedule' => $before['payment_schedule'] !== $fields['payment_schedule'],
            'clauses' => $clauseIds($before['clauses']) !== $clauseIds($fields['clauses']),
            'customer signer' => ($before['signers'][0]['name'] ?? null) !== $d['signer_name'] || ($before['signers'][0]['email'] ?? null) !== $d['signer_email'],
        ]));
    }

    /** Create the agreement row. $extra overrides the first-draft defaults (used for revisions). */
    private function insert(array $d, array $keepClauses = [], array $extra = []): Record
    {
        $lead = $this->leads->find($d['lead_id']);
        $now = Carbon::now()->toDateTimeString();
        $row = DemoStore::create('agreements', array_merge($this->fields($d, $keepClauses), [
            'lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'status' => 'draft',
            'version' => 1, 'versions' => [['no' => 1, 'status' => 'draft', 'at' => $now, 'note' => 'Draft created; clause versions snapshotted']],
            'signers' => [
                ['name' => $d['signer_name'], 'role' => 'Customer', 'email' => $d['signer_email'], 'signed_at' => null, 'method' => null, 'verification' => 'Pending', 'consent' => false],
                ['name' => Demo::user()->name, 'role' => 'Company signatory', 'email' => Demo::user()->email, 'signed_at' => null, 'method' => null, 'verification' => 'Pending', 'consent' => false],
            ],
            'events' => [['at' => $now, 'text' => 'Draft created', 'by' => Demo::user()->name]],
            'created_by' => Demo::id(), 'created_at' => $now, 'signed_at' => null, 'hash' => null, 'billing_status' => 'not_set_up', 'offline_upload' => null,
        ], $extra), 'AG-', 3100);
        DemoStore::patch('leads', $lead->id, ['handoff' => ['state' => 'in_progress', 'received_at' => $lead->handoff?->received_at ?? $now, 'assigned_to' => Demo::id()]]);

        return $this->find($row['id']);
    }

    public function setStatus(string $id, string $status, string $eventText): void
    {
        $now = Carbon::now()->toDateTimeString();
        $patch = ['status' => $status];
        if ($status === 'signed') {
            $patch += ['signed_at' => $now, 'hash' => 'sha256:'.substr(md5($id.$now), 0, 4).'... (demo placeholder - no real hash computed)'];
        }
        DemoStore::patch('agreements', $id, $patch);
        DemoStore::append('agreements', $id, 'events', ['at' => $now, 'text' => $eventText, 'by' => Demo::user()->name]);
        app(PortalRepository::class)->log('Agreement '.status_label('agreement', $status), $id, null, 'agreement');
    }

    public function addEvent(string $id, string $text, string $by = 'System'): void
    {
        DemoStore::append('agreements', $id, 'events', ['at' => Carbon::now()->toDateTimeString(), 'text' => $text, 'by' => $by]);
    }

    /** Simulated customer signature: marks the customer signer signed. Not a real signature. */
    public function simulateCustomerSignature(string $id, string $name, string $method): void
    {
        $a = $this->find($id);
        $now = Carbon::now()->toDateTimeString();
        $signers = $a->plain()['signers'];
        $signers[0]['signed_at'] = $now;
        $signers[0]['method'] = $method;
        $signers[0]['verification'] = 'Email one-time code (simulated)';
        $signers[0]['consent'] = true;
        $signers[0]['name'] = $name ?: $signers[0]['name'];
        $status = collect($signers)->every(fn ($s) => $s['signed_at']) ? 'signed' : 'partially_signed';
        DemoStore::patch('agreements', $id, ['signers' => $signers, 'status' => $status]);
        $this->addEvent($id, 'Signed by '.$signers[0]['name'].' ('.$method.', consent ticked) - DEMO SIMULATION, not a real signature', 'Customer (demo)');
    }

    public function countersign(string $id): void
    {
        $a = $this->find($id);
        $now = Carbon::now()->toDateTimeString();
        $signers = $a->plain()['signers'];
        $signers[1]['signed_at'] = $now;
        $signers[1]['method'] = 'typed';
        $signers[1]['consent'] = true;
        $signers[1]['verification'] = 'Authenticated staff session (demo)';
        $status = collect($signers)->every(fn ($s) => $s['signed_at']) ? 'signed' : 'partially_signed';
        $patch = ['signers' => $signers, 'status' => $status];
        if ($status === 'signed') {
            $patch += ['signed_at' => $now, 'hash' => 'sha256:'.substr(md5($id.$now), 0, 4).'... (demo placeholder)', 'start_date' => $a->start_date ?: Carbon::today()->toDateString()];
        }
        DemoStore::patch('agreements', $id, $patch);
        $this->addEvent($id, 'Counter-signed by '.Demo::user()->name.($status === 'signed' ? ' - agreement fully signed and version locked (demo)' : ''), Demo::user()->name);
    }

    public function recordOfflineUpload(string $id, string $filename): void
    {
        DemoStore::patch('agreements', $id, ['offline_upload' => ['name' => $filename, 'at' => Carbon::now()->toDateTimeString(), 'by' => Demo::id(), 'review' => 'unreviewed']]);
        $this->addEvent($id, 'Signed copy uploaded offline: '.$filename.' (awaiting Finance/Admin review) - DEMO, nothing stored', Demo::user()->name);
    }

    public function reviewOffline(string $id, bool $approve, ?string $note): void
    {
        $a = $this->find($id);
        $up = $a->offline_upload->plain();
        $up['review'] = $approve ? 'approved' : 'rejected';
        $up['note'] = $note;
        DemoStore::patch('agreements', $id, ['offline_upload' => $up]);
        if ($approve) {
            $this->setStatus($id, 'signed', 'Offline signed copy reviewed and accepted - agreement marked signed (demo)');
        } else {
            $this->addEvent($id, 'Offline signed copy rejected: '.$note, Demo::user()->name);
        }
    }
}
