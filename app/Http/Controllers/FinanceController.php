<?php

namespace App\Http\Controllers;

use App\Mock\DemoStore;
use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\FinanceRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(private LeadRepository $leads, private AgreementRepository $agreements, private FinanceRepository $finance, private PortalRepository $portal) {}

    public function handoff()
    {
        $rows = $this->leads->closedWon()->map(function ($l) {
            $l->agreement = $this->agreements->forLead($l->id)->first();
            $l->days_since = (int) Carbon::parse($l->closed_won_at)->diffInDays(now());

            return $l;
        });

        return view('pages.finance.handoff', ['rows' => $rows, 'counts' => $rows->groupBy(fn ($l) => $l->handoff?->state ?? 'new')->map->count()]);
    }

    public function claim(string $lead)
    {
        $l = $this->leads->find($lead);
        abort_unless($l && $l->sales_status === 'closed_won', 404);
        DemoStore::patch('leads', $lead, ['handoff' => ['state' => 'in_progress', 'received_at' => $l->handoff?->received_at ?? $l->closed_won_at, 'assigned_to' => Demo::id()]]);
        $this->portal->log('Handoff claimed', $lead, null, 'finance');

        return back()->with('toast', ['message' => $l->customer->business_name.' assigned to you. Build the agreement when ready.']);
    }

    public function dashboard(Request $request)
    {
        $months = in_array((int) $request->query('range', 6), [3, 6], true) ? (int) $request->query('range', 6) : 6;
        $series = $this->finance->cashSeries();
        $slice = fn (array $a) => array_slice($a, -$months);
        $series = collect($series)->map($slice)->all();
        $t = $this->finance->totals();

        return view('pages.finance.dashboard', [
            'series' => $series, 'totals' => $t, 'months' => $months,
            'payments' => $this->finance->payments(), 'subs' => $this->finance->subscriptions(),
            'cashIn' => array_sum($series['cash_in']), 'cashOut' => array_sum($series['commission']) + array_sum($series['refunds']) + array_sum($series['other_out']),
            'commissionPaid' => array_sum($series['commission']),
            'projects' => app(DirectoryRepository::class)->projects(),
            'agreements' => $this->agreements->all(),
        ]);
    }
}
