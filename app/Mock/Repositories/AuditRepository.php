<?php

namespace App\Mock\Repositories;

use App\Contracts\AuditRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Audit / guidance / quotation requests, their versions and client sends. */
class AuditRepository extends MockRepository implements AuditRepositoryContract
{
    public function __construct(private DirectoryRepository $dir, private LeadRepository $leads) {}

    public function all(): Collection
    {
        return $this->rows('audits')->map(function (Record $r) {
            $r->lead = $this->leads->find($r->lead_id);
            $r->customer = $r->lead?->customer;
            $r->requester = $this->dir->user($r->requested_by);
            $r->auditor = $this->dir->user($r->assigned_to);
            $r->latest = collect($r->versions)->sortByDesc('no')->first();
            $r->version_count = count($r->versions);

            return $r;
        })->sortByDesc('created_at')->values();
    }

    public function scoped(): Collection
    {
        $me = Demo::id();
        $leadIds = $this->leads->scoped()->pluck('id')->all();

        return match (Demo::roleKey()) {
            'admin' => $this->all(),
            'auditor' => $this->all()->where('assigned_to', $me)->values(),
            'finance' => collect(),
            default => $this->all()->filter(fn ($r) => in_array($r->lead_id, $leadIds, true))->values(),
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

    public function forLead(string $leadId): Collection
    {
        return $this->all()->where('lead_id', $leadId)->values();
    }

    public function create(string $leadId, string $type, string $notes, string $priority, ?string $due): Record
    {
        $lead = $this->leads->find($leadId);
        $auditor = $lead->project?->auditor_ids[0] ?? null;
        $now = Carbon::now()->toDateTimeString();
        $row = DemoStore::create('audits', [
            'lead_id' => $leadId, 'customer_id' => $lead->customer_id, 'project_id' => $lead->project_id,
            'type' => $type, 'requested_by' => Demo::id(), 'assigned_to' => $auditor,
            'status' => $auditor ? 'assigned' : 'requested', 'priority' => $priority, 'created_at' => $now,
            'due_date' => $due ?: Carbon::today()->addDays(5)->toDateString(), 'notes' => $notes,
            'links' => [], 'files' => [], 'versions' => [], 'sends' => [],
            'timeline' => [['at' => $now, 'by' => Demo::id(), 'text' => 'Request created ('.$type.')'], ['at' => $now, 'by' => 'System', 'text' => $auditor ? 'Auto-assigned to project auditor '.$this->dir->userName($auditor) : 'Waiting for an Admin to assign an auditor']],
        ], 'A-', 2100);
        $this->leads->addActivity($leadId, 'audit', ucfirst($type).' requested ('.$row['id'].')', $notes);
        DemoStore::patch('leads', $leadId, ['sales_status' => $lead->sales_status === 'new' || $lead->sales_status === 'contacted' || $lead->sales_status === 'pitched' ? 'audit_quote_requested' : $lead->sales_status]);

        return $this->find($row['id']);
    }

    public function saveVersion(string $id, string $html, string $internalHtml, string $note, bool $submit): int
    {
        $req = $this->find($id);
        $no = count($req->versions) + 1;
        $now = Carbon::now()->toDateTimeString();
        $status = $submit ? 'submitted' : 'draft';
        DemoStore::append('audits', $id, 'versions', [
            'no' => $no, 'status' => $status, 'author' => Demo::id(), 'at' => $now, 'html' => $html, 'internal_html' => $internalHtml, 'note' => $note ?: ($submit ? 'Submitted' : 'Draft saved'),
        ]);
        DemoStore::patch('audits', $id, ['status' => $submit ? 'submitted' : 'in_progress']);
        DemoStore::append('audits', $id, 'timeline', ['at' => $now, 'by' => Demo::id(), 'text' => ($submit ? 'Submitted version ' : 'Saved draft version ').$no]);

        return $no;
    }

    public function setStatus(string $id, string $status, ?string $note = null): void
    {
        DemoStore::patch('audits', $id, ['status' => $status]);
        DemoStore::append('audits', $id, 'timeline', ['at' => Carbon::now()->toDateTimeString(), 'by' => Demo::id(), 'text' => 'Status changed to '.status_label('audit', $status).($note ? ' - '.$note : '')]);
    }

    public function assign(string $id, string $userId): void
    {
        DemoStore::patch('audits', $id, ['assigned_to' => $userId, 'status' => 'assigned']);
        DemoStore::append('audits', $id, 'timeline', ['at' => Carbon::now()->toDateTimeString(), 'by' => Demo::id(), 'text' => 'Assigned to '.$this->dir->userName($userId)]);
    }

    /** Simulated send: recorded in the request and on the lead timeline, nothing is emailed. */
    public function recordSend(string $id, array $to, array $cc, string $subject, string $message, int $version): void
    {
        $now = Carbon::now()->toDateTimeString();
        $req = $this->find($id);
        DemoStore::append('audits', $id, 'sends', [
            'to' => $to, 'cc' => $cc, 'subject' => $subject, 'message' => $message, 'version' => $version, 'at' => $now, 'by' => Demo::id(),
            'result' => 'Demo simulation - no email was actually sent',
        ]);
        DemoStore::patch('audits', $id, ['status' => 'sent']);
        DemoStore::append('audits', $id, 'timeline', ['at' => $now, 'by' => Demo::id(), 'text' => 'Version '.$version.' "sent" to '.implode(', ', $to).' (demo simulation)']);
        $this->leads->addActivity($req->lead_id, 'email', 'Report v'.$version.' sent to client (demo)', 'To: '.implode(', ', $to).($cc ? ' | CC: '.implode(', ', $cc) : '').' | Subject: '.$subject);
    }
}
