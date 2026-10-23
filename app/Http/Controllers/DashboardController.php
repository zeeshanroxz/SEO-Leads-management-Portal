<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\AuditRepository;
use App\Mock\Repositories\CalendarRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\FinanceRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Carbon\Carbon;

/** Role-specific dashboards. All numbers come from the mock repositories. */
class DashboardController extends Controller
{
    public function __construct(
        private LeadRepository $leads,
        private AuditRepository $audits,
        private CalendarRepository $calendar,
        private AgreementRepository $agreements,
        private FinanceRepository $finance,
        private PortalRepository $portal,
        private DirectoryRepository $dir,
    ) {}

    public function __invoke()
    {
        $role = Demo::roleKey();
        $data = match ($role) {
            'admin' => $this->admin(),
            'finance' => $this->financeData(),
            'auditor' => $this->auditor(),
            'manager' => $this->manager(),
            'team_lead' => $this->teamLead(),
            default => $this->agent(),
        };

        return view('pages.dashboard.index', ['role' => $role, 'd' => $data]);
    }

    /** @return array<string,int> counts by sales status in canonical order */
    private function pipeline($leads): array
    {
        $out = [];
        foreach (config('statuses.sales.values') as $k => $v) {
            if (in_array($k, ['closed_lost', 'not_interested'], true)) {
                continue;
            }
            $out[$k] = $leads->where('review_status', 'accepted')->where('sales_status', $k)->count();
        }

        return $out;
    }

    private function upcoming(int $n = 5)
    {
        return $this->calendar->scoped()->where('status', '!=', 'cancelled')->filter(fn ($e) => Carbon::parse($e->start)->gte(Carbon::now()->startOfDay()))->sortBy('start')->take($n)->values();
    }

    private function followUps($leads, int $n = 6)
    {
        return $leads->filter(fn ($l) => $l->next_action && $l->review_status === 'accepted' && ! in_array($l->sales_status, ['closed_lost', 'not_interested']))->sortBy(fn ($l) => $l->next_action->due)->take($n)->values();
    }

    private function admin(): array
    {
        $leads = $this->leads->all();
        $audits = $this->audits->all();
        $agreements = $this->agreements->all();
        $com = $this->finance->commissions();
        $errors = $this->portal->integrationErrors();

        $bySource = $leads->groupBy('source')->map->count()->sortDesc();

        return [
            'kpi' => [
                ['Pending review', $leads->where('review_status', 'pending_review')->count(), 'clipboard-document-check', 'amber', route('review.index'), 'Waiting for accept / reject'],
                ['Open opportunities', $leads->where('review_status', 'accepted')->whereNotIn('sales_status', ['closed_won', 'closed_lost', 'not_interested'])->count(), 'user-group', 'brand', route('leads.index'), 'In the sales pipeline'],
                ['Audit backlog', $audits->whereIn('status', ['requested', 'assigned', 'in_progress', 'revision_requested'])->count(), 'document-magnifying-glass', 'violet', route('audits.index'), 'Open requests'],
                ['Awaiting signature', $agreements->whereIn('status', ['sent', 'viewed', 'partially_signed'])->count(), 'document-text', 'teal', route('agreements.index'), 'Agreements out for signing'],
            ],
            'pipeline' => $this->pipeline($leads),
            'sources' => $bySource,
            'liability' => [
                'Pending' => $com->where('status', 'pending')->sum('current_amount'),
                'Eligible' => $com->where('status', 'eligible')->sum('current_amount'),
                'Approved' => $com->where('status', 'approved')->sum('current_amount'),
                'Due' => $com->where('status', 'due')->sum('current_amount'),
                'On hold' => $com->where('status', 'on_hold')->sum('current_amount'),
            ],
            'exceptions' => [
                ['Leads waiting > 24h for review', $leads->where('review_status', 'pending_review')->filter(fn ($l) => Carbon::parse($l->submitted_at)->lt(Carbon::now()->subDay()))->count(), route('review.index')],
                ['Closed Won deals without an agreement', $leads->where('sales_status', 'closed_won')->filter(fn ($l) => ! $agreements->contains('lead_id', $l->id))->count(), route('finance.handoff')],
                ['Reconciliation mismatches', $this->finance->reconciliation()->where('resolved', false)->count(), route('billing.index').'#reconciliation'],
                ['Failed / rejected webhooks', $this->finance->webhooks()->where('status', 'failed')->count(), route('billing.index').'#webhooks'],
                ['Overdue audit requests', $audits->filter(fn ($a) => ! in_array($a->status, ['sent', 'approved', 'cancelled']) && Carbon::parse($a->due_date)->isPast())->count(), route('audits.index')],
            ],
            'integrations' => [
                ['Square (sandbox)', $this->finance->square()->status === 'connected' ? 'ok' : 'error', 'Last test '.ago($this->finance->square()->last_test_at)],
                ['Google Calendar', $errors->where('integration', 'Google Calendar')->where('status', 'open')->count() ? 'warning' : 'ok', $errors->where('integration', 'Google Calendar')->where('status', 'open')->count().' user(s) with sync errors'],
                ['Zoom', 'ok', 'Simulated'],
                ['Email delivery', 'warning', '1 simulated bounce acknowledged'],
            ],
            'activity' => $this->portal->auditLog()->take(7),
        ];
    }

    private function financeData(): array
    {
        $leads = $this->leads->closedWon();
        $agreements = $this->agreements->all();
        $series = $this->finance->cashSeries();
        $t = $this->finance->totals();

        return [
            'queue' => $leads->filter(fn ($l) => ($l->handoff?->state ?? 'new') !== 'done')->values(),
            'agreementCounts' => $agreements->groupBy('status')->map->count(),
            'series' => $series, 'totals' => $t,
            'square' => ['mapped' => $this->finance->squareCustomers()->where('status', 'linked')->count(), 'unmapped' => $this->finance->squareCustomers()->where('status', 'unmapped')->count()],
            'recon' => $this->finance->reconciliation()->where('resolved', false)->values(),
            'subs' => $this->finance->subscriptions()->groupBy('status')->map->count(),
            'agreements' => $agreements,
            'unsigned' => $agreements->whereIn('status', ['ready_to_send', 'sent', 'viewed', 'partially_signed'])->count(),
            'signed' => $agreements->where('status', 'signed')->count(),
        ];
    }

    private function auditor(): array
    {
        $mine = $this->audits->scoped();
        $order = array_keys(config('statuses.audit.values'));

        return [
            'byStatus' => collect($order)->mapWithKeys(fn ($s) => [$s => $mine->where('status', $s)->count()])->filter(fn ($c) => $c > 0),
            'due' => $mine->filter(fn ($a) => ! in_array($a->status, ['sent', 'approved', 'cancelled']))->sortBy('due_date')->take(6)->values(),
            'revisions' => $mine->where('status', 'revision_requested')->values(),
            'history' => $mine->groupBy('lead_id')->map(fn ($g) => ['lead' => $g->first()->lead, 'count' => $g->count(), 'last' => $g->max('created_at')])->values()->take(6),
            'uploads' => $mine->flatMap(fn ($a) => collect($a->files)->map(fn ($f) => ['f' => $f, 'a' => $a]))->values()->take(5),
            'total' => $mine->count(),
        ];
    }

    private function manager(): array
    {
        $leads = $this->leads->scoped();
        $accepted = $leads->where('review_status', 'accepted');
        $won = $accepted->where('sales_status', 'closed_won')->count();
        $reviewed = $leads->whereIn('review_status', ['accepted', 'rejected']);
        $stale = $accepted->filter(fn ($l) => ! in_array($l->sales_status, ['closed_won', 'closed_lost', 'not_interested']) && collect($l->activities)->max('at') && Carbon::parse(collect($l->activities)->max('at'))->lt(Carbon::now()->subDays(6)))->values();
        $signed = $this->agreements->signedRecurring();
        $com = $this->finance->commissionsScoped();

        return [
            'pipeline' => $this->pipeline($leads),
            'conversion' => $accepted->count() ? round($won / $accepted->count() * 100) : 0,
            'accepted' => $leads->where('review_status', 'accepted')->count(), 'rejected' => $leads->where('review_status', 'rejected')->count(), 'pending' => $leads->where('review_status', 'pending_review')->count(),
            'rejectRate' => $reviewed->count() ? round($leads->where('review_status', 'rejected')->count() / $reviewed->count() * 100) : 0,
            'followUps' => $this->followUps($leads), 'stale' => $stale, 'upcoming' => $this->upcoming(),
            'mrr' => $signed->sum('mrr'), 'signedCustomers' => $signed->count(),
            'commissionTeam' => ['due' => $com->where('status', 'due')->sum('current_amount'), 'approved' => $com->where('status', 'approved')->sum('current_amount'), 'paid' => $com->where('status', 'paid')->sum('current_amount'), 'pending' => $com->whereIn('status', ['pending', 'eligible'])->sum('current_amount')],
            'myCommission' => $com->where('recipient_id', Demo::id()),
            'agents' => $this->agentRows($leads),
        ];
    }

    private function agentRows($leads)
    {
        return $leads->groupBy('owner_id')->map(function ($g) {
            $owner = $g->first()->owner;
            $last = $g->flatMap(fn ($l) => collect($l->activities))->max('at');

            return ['owner' => $owner, 'total' => $g->count(), 'open' => $g->where('review_status', 'accepted')->whereNotIn('sales_status', ['closed_won', 'closed_lost', 'not_interested'])->count(), 'won' => $g->where('sales_status', 'closed_won')->count(), 'pending' => $g->where('review_status', 'pending_review')->count(), 'last' => $last];
        })->values();
    }

    private function teamLead(): array
    {
        $leads = $this->leads->scoped();
        $com = $this->finance->commissionsScoped();

        return [
            'pipeline' => $this->pipeline($leads),
            'agents' => $this->agentRows($leads),
            'followUps' => $this->followUps($leads), 'upcoming' => $this->upcoming(),
            'audits' => $this->audits->scoped()->take(5),
            'review' => $this->leads->reviewQueue(),
            'myCommission' => ['due' => $com->whereIn('status', ['due', 'approved'])->sum('current_amount'), 'paid' => $com->where('status', 'paid')->sum('current_amount'), 'upcoming' => $com->whereIn('status', ['pending', 'eligible'])->sum('current_amount')],
            'comRows' => $com->take(4),
        ];
    }

    private function agent(): array
    {
        $leads = $this->leads->scoped();
        $com = $this->finance->commissionsScoped();

        return [
            'counts' => [
                'draft' => $leads->where('review_status', 'draft')->count(), 'pending' => $leads->where('review_status', 'pending_review')->count(),
                'accepted' => $leads->where('review_status', 'accepted')->count(), 'rejected' => $leads->where('review_status', 'rejected')->count(),
            ],
            'pipeline' => $this->pipeline($leads),
            'followUps' => $this->followUps($leads), 'upcoming' => $this->upcoming(),
            'audits' => $this->audits->scoped()->take(5),
            'feedback' => $leads->flatMap(fn ($l) => collect($l->history)->map(fn ($h) => ['lead' => $l, 'h' => $h]))->sortByDesc(fn ($x) => $x['h']->at)->take(5)->values(),
            'rejected' => $leads->where('review_status', 'rejected')->values(),
            'myCommission' => ['due' => $com->whereIn('status', ['due', 'approved'])->sum('current_amount'), 'paid' => $com->where('status', 'paid')->sum('current_amount'), 'upcoming' => $com->whereIn('status', ['pending', 'eligible'])->sum('current_amount')],
            'comRows' => $com->take(4),
            'learning' => $this->portal->learning()->take(3),
        ];
    }
}
