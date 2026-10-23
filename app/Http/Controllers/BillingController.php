<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\FinanceRepository;
use App\Mock\Repositories\PortalRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Square billing screens - ALL DATA AND EVENTS ARE SIMULATED. */
class BillingController extends Controller
{
    public function __construct(private FinanceRepository $finance, private AgreementRepository $agreements, private PortalRepository $portal) {}

    public function index()
    {
        $subs = $this->finance->subscriptions()->map(function ($s) {
            $s->agreement = $this->agreements->find($s->agreement_id);
            $s->can_activate = $s->status === 'not_set_up' && $s->agreement?->status === 'signed' && $this->finance->squareCustomers()->firstWhere('customer_id', $s->customer_id)?->status === 'linked';
            $s->block_reason = $s->status !== 'not_set_up' ? null : ($s->agreement?->status !== 'signed' ? 'Agreement is not fully signed yet.' : 'Square customer is not mapped.');

            return $s;
        });

        return view('pages.finance.billing', [
            'square' => $this->finance->square(), 'customers' => $this->finance->squareCustomers(), 'subs' => $subs,
            'payments' => $this->finance->payments(), 'webhooks' => $this->finance->webhooks(), 'recon' => $this->finance->reconciliation(),
            'syncLog' => $this->portal->integrationErrors()->where('integration', 'Square')->values(), 'totals' => $this->finance->totals(),
        ]);
    }

    public function map(Request $request, string $record)
    {
        $mode = $request->validate(['mode' => ['required', Rule::in(['create', 'link'])]])['mode'];
        $this->finance->mapSquareCustomer($record, $mode);
        $this->portal->log('Square customer '.($mode === 'create' ? 'created (sandbox)' : 'linked'), $record, null, 'finance');

        return back()->with('toast', ['message' => 'Customer '.($mode === 'create' ? 'created in Square sandbox' : 'linked to an existing Square customer').' (simulated). Duplicate mappings are blocked by default.', 'demo' => true]);
    }

    public function subscription(string $subscription)
    {
        $this->finance->activateSubscription($subscription);
        $this->portal->log('Subscription set up (sandbox)', $subscription, null, 'finance');

        return back()->with('toast', ['message' => 'Subscription activated in the Square sandbox (simulated). No customer was charged.', 'demo' => true]);
    }

    public function retry(string $webhook)
    {
        $this->finance->retryWebhook($webhook);
        $this->portal->log('Webhook retried', $webhook, null, 'integration');

        return back()->with('toast', ['message' => 'Retry processed. The idempotency key prevented any duplicate ledger entry.', 'demo' => true]);
    }

    public function simulate(Request $request)
    {
        $type = $request->validate(['type' => ['required', Rule::in(['payment.updated', 'subscription.updated', 'refund.updated'])]])['type'];
        $this->finance->simulateWebhook($type);

        return back()->with('toast', ['message' => 'Simulated '.$type.' event received and processed. In production events arrive via a verified webhook.', 'demo' => true]);
    }

    public function reconcile(Request $request, string $record)
    {
        $data = $request->validate(['note' => 'required|string|min:5|max:300']);
        $this->finance->resolveReconciliation($record, $data['note']);
        $this->portal->log('Reconciliation resolved', $record, $data['note'], 'finance');

        return back()->with('toast', ['message' => 'Marked reconciled with your note.']);
    }

    // ----------------------------------------------------- Admin: Square settings

    public function settings()
    {
        return view('pages.finance.square-settings', ['square' => $this->finance->square(), 'log' => $this->portal->integrationErrors()->where('integration', 'Square')->values()]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['environment' => ['required', Rule::in(['sandbox', 'live'])], 'location_id' => 'required|string|max:60', 'action' => 'nullable|string']);
        if ($request->input('action') === 'rotate') {
            $this->finance->updateSquare(['token_rotated_at' => Carbon::now()->toDateTimeString()]);
            $this->portal->log('Square credentials rotated (demo)', 'Integration: Square', null, 'integration');

            return back()->with('toast', ['message' => 'Rotation simulated. This prototype never accepts or stores a real key — in Phase 2 secrets are entered server-side only.', 'demo' => true]);
        }
        if ($data['environment'] === 'live' && ! $request->boolean('confirm_live')) {
            return back()->withErrors(['environment' => 'Confirm that you understand live mode would move real money (it is only a mockup here).']);
        }
        $this->finance->updateSquare(['environment' => $data['environment'], 'location_id' => $data['location_id'], 'location_name' => $data['environment'] === 'live' ? 'Demo Live Location (mock)' : 'Demo Location (sandbox)']);
        $this->portal->log('Square environment set to '.$data['environment'].' (mock)', 'Integration: Square', null, 'integration');

        return back()->with('toast', ['message' => 'Square settings saved for this demo session ('.$data['environment'].' mode — mock only).', 'demo' => true]);
    }

    public function test()
    {
        $this->finance->updateSquare(['last_test_at' => Carbon::now()->toDateTimeString(), 'last_test_result' => 'ok', 'status' => 'connected']);
        $this->portal->log('Square connection tested (simulated)', 'Integration: Square', null, 'integration');

        return back()->with('toast', ['message' => 'Connection test passed — SIMULATED. Merchant "Demo Merchant (sandbox)", location LOC-DEMO-0001, 142 ms. No request left this prototype.', 'demo' => true]);
    }
}
