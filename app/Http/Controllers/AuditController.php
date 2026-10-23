<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AuditRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditController extends Controller
{
    public function __construct(private AuditRepository $audits, private LeadRepository $leads, private DirectoryRepository $dir, private PortalRepository $portal) {}

    public function index(Request $request)
    {
        $rows = $this->audits->scoped();

        return view('pages.audits.index', [
            'rows' => $rows, 'auditors' => $this->dir->usersByRole('auditor'),
            'mine' => Demo::is('auditor'), 'state' => $request->query('state'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => 'required|string', 'type' => ['required', Rule::in(['audit', 'guidance', 'quotation'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])], 'due' => 'nullable|date', 'notes' => 'required|string|min:10|max:1500',
        ], ['notes.required' => 'Describe what you need from the auditor.', 'notes.min' => 'Please add at least 10 characters of detail.']);
        $lead = $this->leads->findScoped($data['lead_id']);
        abort_unless($lead && $lead->review_status === 'accepted', 403, 'Requests can only be raised on accepted leads in your scope.');
        $req = $this->audits->create($lead->id, $data['type'], $data['notes'], $data['priority'], $data['due'] ?? null);
        $this->portal->log(ucfirst($data['type']).' requested', $req->id.' for '.$lead->id, null, 'audit');

        return redirect()->route('audits.show', $req->id)->with('toast', ['message' => ucfirst($data['type']).' request '.$req->id.' created and linked to '.$lead->customer->business_name.'. Earlier requests are untouched.']);
    }

    private function find(string $audit)
    {
        $r = $this->audits->findScoped($audit);
        abort_unless($r, 403, 'This request is outside your scope (assigned audits only for Auditors; your own/team leads for sales roles).');

        return $r;
    }

    public function show(Request $request, string $audit)
    {
        $r = $this->find($audit);
        $versions = collect($r->versions)->sortByDesc('no')->values();
        $loadNo = (int) $request->query('load', $versions->first()?->no ?? 0);
        $base = $versions->firstWhere('no', $loadNo) ?? $versions->first();
        $canAuthor = Demo::can('audit.author') && (Demo::is('admin') || $r->assigned_to === Demo::id());
        $internalVisible = ! Demo::is('agent');

        return view('pages.audits.show', [
            'req' => $r, 'versions' => $versions, 'base' => $base, 'canAuthor' => $canAuthor, 'internalVisible' => $internalVisible,
            'canReview' => Demo::can(['audit.request']) && ! $canAuthor, 'canSend' => Demo::can('audit.send') && (! Demo::is('agent') || $this->portal->setting('agent_send_audit', true)),
            'auditors' => $this->dir->usersByRole('auditor'), 'dir' => $this->dir,
            'siblings' => $this->audits->forLead($r->lead_id)->where('id', '!=', $r->id)->values(),
        ]);
    }

    public function version(Request $request, string $audit)
    {
        $r = $this->find($audit);
        abort_unless(Demo::is('admin') || $r->assigned_to === Demo::id(), 403, 'Only the assigned auditor can write the report.');
        $data = $request->validate(['html' => 'nullable|string|max:200000', 'internal_html' => 'nullable|string|max:50000', 'note' => 'nullable|string|max:200', 'submit' => 'nullable|boolean']);
        $submit = $request->boolean('submit');
        if ($submit && ! trim(strip_tags($data['html'] ?? ''))) {
            return back()->withErrors(['html' => 'The customer-facing report is empty — add content before submitting.'])->withInput();
        }
        $no = $this->audits->saveVersion($r->id, HtmlSanitizer::clean($data['html'] ?? ''), HtmlSanitizer::clean($data['internal_html'] ?? ''), $data['note'] ?? '', $submit);
        $this->portal->log($submit ? 'Report version submitted' : 'Report draft saved', $r->id.' v'.$no, null, 'audit');

        return redirect()->route('audits.show', $r->id)->with('toast', ['message' => $submit ? 'Version '.$no.' submitted for approval. Earlier versions are preserved.' : 'Draft version '.$no.' saved. Earlier versions are preserved.']);
    }

    public function status(Request $request, string $audit)
    {
        $r = $this->find($audit);
        $data = $request->validate(['to' => ['required', Rule::in(['revision_requested', 'approved', 'cancelled', 'in_progress'])], 'note' => 'nullable|string|max:500']);
        if (in_array($data['to'], ['revision_requested', 'cancelled'], true) && strlen(trim($data['note'] ?? '')) < 5) {
            return back()->withErrors(['note' => 'A note is required when requesting a revision or cancelling.']);
        }
        $this->audits->setStatus($r->id, $data['to'], $data['note'] ?? null);
        $this->portal->log('Audit status: '.status_label('audit', $data['to']), $r->id, $data['note'] ?? null, 'audit');

        return back()->with('toast', ['message' => 'Request marked '.status_label('audit', $data['to']).'.']);
    }

    public function assign(Request $request, string $audit)
    {
        $data = $request->validate(['user_id' => 'required|string']);
        $this->audits->assign($audit, $data['user_id']);

        return back()->with('toast', ['message' => 'Assigned to '.$this->dir->userName($data['user_id']).'.']);
    }

    public function preview(Request $request, string $audit)
    {
        $r = $this->find($audit);
        abort_unless(Demo::can('audit.send') || Demo::is('admin'), 403);
        $versions = collect($r->versions)->sortByDesc('no')->values();
        $v = $versions->firstWhere('no', (int) $request->query('v')) ?? $versions->first();
        abort_unless($v, 404, 'No report version exists yet to preview.');
        $primary = collect($r->customer->contacts)->firstWhere('primary', true);

        return view('pages.audits.preview', [
            'req' => $r, 'v' => $v, 'versions' => $versions, 'html' => HtmlSanitizer::customerFacing($v->html),
            'primary' => $primary, 'canSend' => Demo::can('audit.send') && (! Demo::is('agent') || $this->portal->setting('agent_send_audit', true)),
            'hadInternal' => str_contains($v->html, 'data-visibility="internal"'),
        ]);
    }

    public function send(Request $request, string $audit)
    {
        $r = $this->find($audit);
        $data = $request->validate([
            'to' => 'required|string|max:300', 'cc' => 'nullable|string|max:300', 'subject' => 'required|string|max:160', 'message' => 'required|string|max:3000', 'version' => 'required|integer',
        ], ['to.required' => 'Add at least one recipient.']);
        $split = fn ($s) => collect(preg_split('/[,;\s]+/', (string) $s))->filter()->unique()->values()->all();
        $to = $split($data['to']);
        $cc = $split($data['cc'] ?? '');
        foreach (array_merge($to, $cc) as $addr) {
            if (! filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['to' => '“'.$addr.'” is not a valid email address.'])->withInput();
            }
        }
        $this->audits->recordSend($r->id, $to, $cc, $data['subject'], $data['message'], (int) $data['version']);
        $this->portal->log('Report sent to client (demo)', $r->id.' v'.$data['version'], 'To: '.implode(', ', $to), 'audit');

        return redirect()->route('audits.show', $r->id)->with('toast', ['message' => 'Recorded: version '.$data['version'].' "sent" to '.implode(', ', $to).'. No real email was sent.', 'demo' => true]);
    }
}
