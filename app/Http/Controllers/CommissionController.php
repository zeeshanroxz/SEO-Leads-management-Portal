<?php

namespace App\Http\Controllers;

use App\Mock\DemoStore;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\FinanceRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommissionController extends Controller
{
    public function __construct(private FinanceRepository $finance, private LeadRepository $leads, private DirectoryRepository $dir, private PortalRepository $portal) {}

    public function index()
    {
        $rows = $this->finance->commissionsScoped();
        $me = Demo::id();
        $t = [
            'pending' => $rows->whereIn('status', ['pending', 'not_eligible'])->sum('current_amount'), 'eligible' => $rows->where('status', 'eligible')->sum('current_amount'),
            'approved' => $rows->where('status', 'approved')->sum('current_amount'), 'due' => $rows->where('status', 'due')->sum('current_amount'),
            'paid' => $rows->where('status', 'paid')->sum('current_amount'), 'hold' => $rows->where('status', 'on_hold')->sum('current_amount'),
        ];

        return view('pages.commissions.index', [
            'rows' => $rows, 'mine' => $rows->where('recipient_id', $me)->values(), 'totals' => $t,
            'mode' => Demo::is('admin', 'finance') ? 'all' : (Demo::is('manager') ? 'manager' : 'own'),
            'recipients' => $this->recipientsForCreate(), 'leads' => $this->leads->closedWon()->when(Demo::is('manager'), fn ($c) => $c->filter(fn ($l) => $l->manager_id === $me))->values(),
            'rules' => $this->finance->rules(), 'days' => (int) DemoStore::flag('commission_days', config('portal.defaults.commission_eligibility_days')),
            'dayMode' => DemoStore::flag('commission_day_mode', config('portal.defaults.commission_day_mode')),
            'canApprove' => Demo::can('commission.approve'), 'canApproveManager' => Demo::can('commission.approve_manager'),
        ]);
    }

    private function recipientsForCreate()
    {
        $users = $this->dir->users()->where('status', 'active')->whereIn('role', ['agent', 'team_lead', 'manager']);
        if (Demo::is('manager')) {
            $ids = $this->dir->reportIds(Demo::id());
            $users = $users->filter(fn ($u) => in_array($u->id, $ids, true));
        }

        return $users->values();
    }

    public function show(string $commission)
    {
        $c = $this->finance->commissionsScoped()->firstWhere('id', $commission);
        abort_unless($c, 403, 'This commission record is outside your scope. You can only see your own commission'.(Demo::is('manager') ? ' and your team\'s' : '').'.');

        return view('pages.commissions.show', ['c' => $c, 'rule' => $this->finance->rule($c->rule_id), 'dir' => $this->dir, 'days' => (int) DemoStore::flag('commission_days', config('portal.defaults.commission_eligibility_days')), 'dayMode' => DemoStore::flag('commission_day_mode', config('portal.defaults.commission_day_mode')), 'canApprove' => Demo::can('commission.approve')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => 'required|string', 'recipient_id' => 'required|string', 'type' => ['required', Rule::in(['one_time', 'residual'])],
            'basis_label' => 'required|string|max:80', 'basis_amount' => 'required|numeric|min:0', 'rate_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'rate' => 'required|numeric|min:0', 'reason' => 'nullable|string|max:300', 'rule_id' => 'nullable|string',
        ], ['rate.required' => 'Enter a rate or fixed amount (sample values only — no policy is assumed).']);
        $recipient = $this->dir->user($data['recipient_id']);
        abort_unless($recipient && $this->recipientsForCreate()->contains('id', $recipient->id), 403, 'You cannot create commissions for this person.');
        abort_if($data['rate_type'] === 'percentage' && $data['rate'] > 100, 422, 'A percentage rate cannot exceed 100.');
        $c = $this->finance->createCommission($data);

        return back()->with('toast', ['message' => 'Commission '.$c->id.' created as '.status_label('commission', $c->status).'. Synthetic sample amount: '.money($c->amount, null, 2).'.']);
    }

    public function transition(Request $request, string $commission)
    {
        $c = $this->finance->commission($commission);
        abort_unless($c, 404);
        $to = $request->validate(['to' => ['required', Rule::in(['approved', 'due', 'paid', 'on_hold', 'reversed', 'eligible', 'pending'])]])['to'];
        if ($c->recipient?->role === 'manager' && ! Demo::can('commission.approve_manager')) {
            abort(403, 'Manager commissions can be edited or approved only by Admin or a Finance Manager.');
        }
        $data = $request->validate(['reason' => 'nullable|string|max:300', 'payout_ref' => 'nullable|string|max:60']);
        if (in_array($to, ['on_hold', 'reversed'], true) && strlen(trim($data['reason'] ?? '')) < 5) {
            return back()->withErrors(['reason' => 'A reason is required to '.($to === 'on_hold' ? 'put a commission on hold' : 'reverse it').'.']);
        }
        if ($c->status === 'paid' && $to !== 'reversed') {
            return back()->withErrors(['to' => 'A paid commission is never edited or deleted. Use an adjustment or a reversal.']);
        }
        $this->finance->transition($c->id, $to, $data['reason'] ?? null, $data['payout_ref'] ?? null);

        return back()->with('toast', ['message' => $c->id.' is now '.status_label('commission', $to).($to === 'paid' ? ' — payout recorded (demo; no money moved).' : '.')]);
    }

    public function adjust(Request $request, string $commission)
    {
        $c = $this->finance->commission($commission);
        abort_unless($c, 404);
        if ($c->recipient?->role === 'manager' && ! Demo::can('commission.approve_manager')) {
            abort(403, 'Manager commissions can be edited only by Admin or a Finance Manager.');
        }
        $data = $request->validate(['delta' => 'required|numeric|not_in:0', 'reason' => 'required|string|min:5|max:300']);
        $this->finance->adjust($c->id, (float) $data['delta'], $data['reason']);

        return back()->with('toast', ['message' => 'Adjustment of '.money($data['delta'], null, 2).' recorded as a linked ledger entry. The original record is unchanged.']);
    }

    public function recalculate()
    {
        $n = $this->finance->recalculateEligibility();

        return back()->with('toast', ['message' => $n ? $n.' commission(s) moved from Pending to Eligible based on today\'s date and the configured rule.' : 'No commissions reached their eligibility date today.', 'type' => $n ? 'success' : 'info']);
    }

    public function rules()
    {
        return view('pages.commissions.rules', [
            'rules' => $this->finance->rules(), 'days' => (int) DemoStore::flag('commission_days', config('portal.defaults.commission_eligibility_days')),
            'dayMode' => DemoStore::flag('commission_day_mode', config('portal.defaults.commission_day_mode')), 'dir' => $this->dir,
        ]);
    }

    public function updateRule(Request $request, string $rule)
    {
        $data = $request->validate(['rate' => 'required|numeric|min:0|max:100', 'basis' => 'required|string|max:80', 'change_note' => 'required|string|min:5|max:200']);
        $this->finance->updateRule($rule, ['rate' => (float) $data['rate'], 'basis' => $data['basis'], 'change_note' => $data['change_note']]);
        $this->portal->log('Commission rule updated', $rule, $data['change_note'], 'finance');

        return back()->with('toast', ['message' => 'Saved as a new rule version. Past commissions keep the rule version used when they were calculated.']);
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['days' => 'required|integer|min:0|max:365', 'mode' => ['required', Rule::in(['calendar', 'business'])]]);
        DemoStore::setFlag('commission_days', (int) $data['days']);
        DemoStore::setFlag('commission_day_mode', $data['mode']);
        $this->portal->log('Eligibility rule changed', 'Commission settings', $data['days'].' '.$data['mode'].' days', 'finance');

        return back()->with('toast', ['message' => 'Eligibility rule saved: Closed Won + '.$data['days'].' '.$data['mode'].' days (prototype assumption). Dates recalculated.']);
    }
}
