<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\FinanceRepository;
use App\Mock\Repositories\LeadRepository;
use App\Support\Demo;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private LeadRepository $leads, private AgreementRepository $agreements, private FinanceRepository $finance) {}

    public function index()
    {
        return view('pages.customers.index', ['customers' => $this->leads->customersScoped()]);
    }

    public function show(string $customer)
    {
        $c = $this->leads->customersScoped()->firstWhere('id', $customer);
        abort_unless($c, 403, 'This customer is outside your visibility scope.');
        $opps = $this->leads->scoped()->where('customer_id', $c->id)->values();
        $agreements = Demo::can('agreements.view') ? $this->agreements->forCustomer($c->id) : collect();
        // Services bought = services on Closed Won opportunities (no prices unless the role may see agreements).
        $services = $opps->where('sales_status', 'closed_won')->flatMap(fn ($l) => collect($l->services)->map(fn ($s) => ['name' => $s->name, 'lead' => $l->id, 'since' => $l->closed_won_at]))->values();
        $history = $opps->flatMap(fn ($l) => collect($l->history)->map(fn ($h) => ['lead' => $l->id, 'h' => $h]))->sortByDesc(fn ($x) => $x['h']->at)->take(12)->values();

        return view('pages.customers.show', [
            'customer' => $c, 'opps' => $opps, 'agreements' => $agreements, 'services' => $services, 'history' => $history,
            'square' => Demo::can('finance.square') ? $this->finance->squareCustomers()->firstWhere('customer_id', $c->id) : null,
        ]);
    }

    public function addContact(Request $request, string $customer)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'designation' => 'nullable|string|max:120', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:40', 'notes' => 'nullable|string|max:300']);
        $this->leads->addContact($customer, $data);

        return back()->with('toast', ['message' => 'Contact added to the business.']);
    }
}
