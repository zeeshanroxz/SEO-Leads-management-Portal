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
        $lead = $this->leads->find($d['lead_id']);
        $services = collect($d['lines'])->map(fn ($l) => ['service_id' => $l['service_id'], 'name' => $this->dir->service($l['service_id'])->name, 'price' => (float) $l['price'], 'billing' => $l['billing'], 'qty' => (int) ($l['qty'] ?? 1)]);
        $clauses = collect($d['clause_ids'] ?? [])->map(fn ($cid) => $this->clause($cid))->filter()->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'version' => $c->version, 'body' => $c->body])->values()->all();
        $now = Carbon::now()->toDateTimeString();
        $row = DemoStore::create('agreements', [
            'lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'status' => 'draft', 'currency' => $d['currency'] ?? 'USD',
            'lines' => $services->all(), 'mrr' => $services->where('billing', 'recurring')->sum(fn ($l) => $l['price'] * $l['qty']),
            'one_time_total' => $services->where('billing', 'one_time')->sum(fn ($l) => $l['price'] * $l['qty']),
            'term_months' => (int) $d['term_months'], 'start_date' => ($d['start_date'] ?? null) ?: null,
            'end_date' => ! empty($d['start_date']) ? Carbon::parse($d['start_date'])->addMonths((int) $d['term_months'])->toDateString() : null,
            'renewal' => $d['renewal'] ?? 'Auto-renews monthly after the initial term unless cancelled with 30 days notice (prototype default).', 'payment_schedule' => $d['payment_schedule'] ?? 'Monthly in advance', 'clauses' => $clauses,
            'version' => 1, 'versions' => [['no' => 1, 'status' => 'draft', 'at' => $now, 'note' => 'Draft created; clause versions snapshotted']],
            'signers' => [
                ['name' => $d['signer_name'], 'role' => 'Customer', 'email' => $d['signer_email'], 'signed_at' => null, 'method' => null, 'verification' => 'Pending', 'consent' => false],
                ['name' => Demo::user()->name, 'role' => 'Company signatory', 'email' => Demo::user()->email, 'signed_at' => null, 'method' => null, 'verification' => 'Pending', 'consent' => false],
            ],
            'events' => [['at' => $now, 'text' => 'Draft created', 'by' => Demo::user()->name]],
            'created_by' => Demo::id(), 'created_at' => $now, 'signed_at' => null, 'hash' => null, 'billing_status' => 'not_set_up', 'offline_upload' => null,
        ], 'AG-', 3100);
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
