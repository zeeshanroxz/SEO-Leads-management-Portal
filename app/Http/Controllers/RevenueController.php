<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AgreementRepository;
use App\Support\Demo;

/** MRR/MRC for fully signed customers - the only agreement-derived data Managers may see. */
class RevenueController extends Controller
{
    public function __construct(private AgreementRepository $agreements) {}

    public function index()
    {
        $rows = $this->agreements->signedRecurring();
        if (Demo::is('manager')) {
            $rows = $rows->filter(fn ($r) => true); // Managers see ALL signed customers by design (not only their team).
        }

        return view('pages.finance.revenue', ['rows' => $rows->sortByDesc('mrr')->values(), 'total' => $rows->sum('mrr')]);
    }
}
