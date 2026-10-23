<?php

namespace App\Http\Controllers;

use App\Mock\DemoStore;
use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgreementController extends Controller
{
    public function __construct(private AgreementRepository $agreements, private LeadRepository $leads, private DirectoryRepository $dir, private PortalRepository $portal) {}

    public function index()
    {
        return view('pages.agreements.index', ['rows' => $this->agreements->all(), 'dir' => $this->dir, 'clauses' => $this->agreements->clauses(), 'usage' => $this->agreements->all()->flatMap(fn ($a) => collect($a->clauses)->pluck('id'))->countBy()]);
    }

    public function show(string $agreement)
    {
        $a = $this->agreements->find($agreement);
        abort_unless($a, 404);

        return view('pages.agreements.show', [
            'a' => $a, 'lead' => $a->lead, 'customer' => $a->customer, 'siblings' => $this->agreements->forCustomer($a->customer_id)->where('id', '!=', $a->id)->values(),
            'canManage' => Demo::can('agreements.manage'), 'dir' => $this->dir, 'signUrl' => route('sign.show', $a->id),
        ]);
    }

    public function create(Request $request)
    {
        $lead = $this->leads->find($request->query('lead'));
        $pool = $this->leads->closedWon();
        $services = $this->dir->services()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'billing' => $s->billing, 'price' => $s->sample_price, 'project_id' => $s->project_id, 'clauses' => $s->clause_ids])->values()->all();

        return view('pages.agreements.create', [
            'lead' => $lead && $lead->sales_status === 'closed_won' ? $lead : null, 'pool' => $pool, 'services' => $services,
            'clauses' => $this->agreements->clauses()->map(fn ($c) => ['id' => $c->id, 'title' => $c->title, 'scope' => $c->scope, 'version' => $c->version, 'body' => $c->body])->values()->all(),
            'contacts' => $pool->mapWithKeys(fn ($l) => [$l->id => collect($l->customer->contacts)->map(fn ($k) => ['name' => $k->name, 'email' => $k->email, 'primary' => $k->primary])->values()->all()])->all(),
            'leadServices' => $pool->mapWithKeys(fn ($l) => [$l->id => $l->service_ids])->all(),
            'names' => $pool->mapWithKeys(fn ($l) => [$l->id => $l->customer->business_name])->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => 'required|string', 'lines' => 'required|array|min:1', 'lines.*.service_id' => 'required|string', 'lines.*.price' => 'required|numeric|min:0',
            'lines.*.billing' => ['required', Rule::in(['recurring', 'one_time'])], 'lines.*.qty' => 'nullable|integer|min:1',
            'term_months' => 'required|integer|min:1|max:60', 'start_date' => 'nullable|date', 'currency' => 'required|string|size:3',
            'renewal' => 'nullable|string|max:400', 'payment_schedule' => 'nullable|string|max:200', 'clause_ids' => 'nullable|array',
            'signer_name' => 'required|string|max:120', 'signer_email' => 'required|email|max:160',
        ], ['lines.required' => 'Add at least one service line.', 'signer_name.required' => 'Name the customer signer.', 'signer_email.required' => 'Add the signer\'s email (used for the one-time code).']);
        $lead = $this->leads->find($data['lead_id']);
        abort_unless($lead && $lead->sales_status === 'closed_won', 422, 'Agreements are built from Closed Won opportunities.');
        $a = $this->agreements->create($data);
        $this->portal->log('Agreement created', $a->id, null, 'agreement');

        return redirect()->route('agreements.show', $a->id)->with('toast', ['message' => 'Draft agreement '.$a->id.' created with a snapshot of the clauses and prices used.']);
    }

    public function status(Request $request, string $agreement)
    {
        $a = $this->agreements->find($agreement);
        abort_unless($a, 404);
        $data = $request->validate(['to' => ['required', Rule::in(['ready_to_send', 'sent', 'viewed', 'cancelled', 'countersign', 'reopen_request', 'revise', 'rejected'])], 'reason' => 'nullable|string|max:400']);
        $to = $data['to'];
        if (in_array($to, ['cancelled', 'reopen_request', 'rejected'], true) && strlen(trim($data['reason'] ?? '')) < 5) {
            return back()->withErrors(['reason' => 'A reason is required for this action.']);
        }
        $msg = '';
        switch ($to) {
            case 'ready_to_send': $this->agreements->setStatus($a->id, 'ready_to_send', 'Marked ready to send');
                $msg = 'Marked ready to send.';
                break;
            case 'sent': $this->agreements->setStatus($a->id, 'sent', 'Secure signing link generated and sent to '.($a->signers[0]->email ?? 'customer').' (DEMO — no email sent)');
                $msg = 'Signing link “sent” (demo). Open the customer signing page from this agreement.';
                break;
            case 'viewed': $this->agreements->setStatus($a->id, 'viewed', 'Customer opened the signing link (simulated)');
                $msg = 'Simulated: customer viewed the agreement.';
                break;
            case 'countersign': $this->agreements->countersign($a->id);
                $msg = 'Counter-signature recorded.';
                break;
            case 'cancelled': $this->agreements->setStatus($a->id, 'cancelled', 'Agreement cancelled. Reason: '.$data['reason']);
                $msg = 'Agreement cancelled. Its history is retained.';
                break;
            case 'rejected': $this->agreements->setStatus($a->id, 'rejected', 'Customer rejected. Reason: '.$data['reason']);
                $msg = 'Marked rejected.';
                break;
            case 'revise': $this->agreements->setStatus($a->id, 'superseded', 'Superseded by a new version (v'.($a->version + 1).')');
                $this->agreements->create(['lead_id' => $a->lead_id, 'lines' => collect($a->lines)->map(fn ($l) => ['service_id' => $l->service_id, 'price' => $l->price, 'billing' => $l->billing, 'qty' => $l->qty])->all(), 'term_months' => $a->term_months, 'start_date' => $a->start_date, 'currency' => $a->currency, 'renewal' => $a->renewal, 'payment_schedule' => $a->payment_schedule, 'clause_ids' => collect($a->clauses)->pluck('id')->all(), 'signer_name' => $a->signers[0]->name, 'signer_email' => $a->signers[0]->email]);
                $msg = 'A new draft was created; the old agreement is marked Superseded (never overwritten).';
                break;
            case 'reopen_request': $this->agreements->addEvent($a->id, 'Reopen requested - needs Admin approval. Reason: '.$data['reason'], Demo::user()->name);
                $msg = Demo::is('admin') ? 'Reopen approved and logged. Start a new opportunity/agreement for the renewed term.' : 'Reopen request logged — it needs Admin approval.';
                break;
        }
        $this->portal->log('Agreement action: '.$to, $a->id, $data['reason'] ?? null, 'agreement');

        return back()->with('toast', ['message' => $msg, 'demo' => in_array($to, ['sent', 'viewed'], true)]);
    }

    public function offline(Request $request, string $agreement)
    {
        $data = $request->validate(['file_name' => 'required|string|max:200'], ['file_name.required' => 'Choose the signed PDF or image.']);
        $this->agreements->recordOfflineUpload($agreement, $data['file_name']);

        return back()->with('toast', ['message' => 'Signed copy recorded for review (demo — no file was stored).', 'demo' => true]);
    }

    public function offlineReview(Request $request, string $agreement)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'note' => 'nullable|string|max:300']);
        if ($data['decision'] === 'reject' && strlen(trim($data['note'] ?? '')) < 5) {
            return back()->withErrors(['note' => 'Explain why the copy was rejected.']);
        }
        $this->agreements->reviewOffline($agreement, $data['decision'] === 'approve', $data['note'] ?? null);
        $this->portal->log('Offline signed copy '.$data['decision'].'d', $agreement, $data['note'] ?? null, 'agreement');

        return back()->with('toast', ['message' => $data['decision'] === 'approve' ? 'Reviewed and accepted — agreement marked signed.' : 'Signed copy rejected; the customer must resubmit.']);
    }

    public function clause(Request $request, string $clause)
    {
        $data = $request->validate(['body' => 'required|string|min:10|max:2000']);
        DemoStore::patch('clauses', $clause, ['body' => $data['body'], 'version' => ($this->agreements->clause($clause)->version ?? 1) + 1]);
        $this->portal->log('Clause updated', $clause, 'New version; existing agreements keep their snapshot', 'agreement');

        return back()->with('toast', ['message' => 'Clause saved as a new version. Existing agreements keep the version they were created with.']);
    }
}
