<?php

namespace App\Mock\Repositories;

use App\Contracts\FinanceRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Square (simulated), payments, commissions, rules and cash summaries. */
class FinanceRepository extends MockRepository implements FinanceRepositoryContract
{
    public function __construct(private DirectoryRepository $dir, private LeadRepository $leads, private AgreementRepository $agreements) {}

    // ---------------------------------------------------------------- Square (mock)

    public function square(): Record
    {
        return $this->rows('square_connection')->first();
    }

    public function updateSquare(array $fields): void
    {
        DemoStore::patch('square_connection', 'default', $fields);
    }

    public function squareCustomers(): Collection
    {
        return $this->rows('square_customers')->map(function ($c) {
            $c->customer = $this->leads->customer($c->customer_id);

            return $c;
        });
    }

    public function mapSquareCustomer(string $id, string $mode): void
    {
        DemoStore::patch('square_customers', $id, [
            'status' => 'linked', 'square_customer_id' => 'DEMO-CUST-'.strtoupper(substr(md5($id), 0, 6)),
            'linked_by' => Demo::id(), 'linked_at' => Carbon::now()->toDateTimeString(), 'card_on_file' => $mode === 'create' ? null : 'Card •••• 4242 (demo)',
        ]);
    }

    public function subscriptions(): Collection
    {
        return $this->rows('subscriptions')->map(function ($s) {
            $s->customer = $this->leads->customer($s->customer_id);

            return $s;
        });
    }

    public function activateSubscription(string $id): void
    {
        DemoStore::patch('subscriptions', $id, ['status' => 'active', 'square_subscription_id' => 'DEMO-SUB-'.rand(2000, 2999), 'started' => Carbon::today()->toDateString(), 'next_charge' => Carbon::today()->addMonth()->toDateString(), 'authorisation' => 'Billing authorisation confirmed by Finance (demo)']);
    }

    public function payments(): Collection
    {
        return $this->rows('payments')->map(function ($p) {
            $p->customer = $this->leads->customer($p->customer_id);

            return $p;
        })->sortByDesc('date')->values();
    }

    public function webhooks(): Collection
    {
        return $this->rows('webhooks')->sortByDesc('received_at')->values();
    }

    public function retryWebhook(string $id): void
    {
        DemoStore::patch('webhooks', $id, ['status' => 'processed', 'attempts' => 4, 'error' => null, 'signature' => 'verified', 'summary' => 'Retried successfully (demo simulation)']);
    }

    public function simulateWebhook(string $type): Record
    {
        $summary = match ($type) {
            'payment.updated' => 'Payment completed (simulated)',
            'subscription.updated' => 'Subscription state refreshed (simulated)',
            'refund.updated' => 'Refund recorded (simulated)',
            default => 'Event received (simulated)',
        };

        return Record::from(DemoStore::create('webhooks', [
            'event_id' => 'DEMO-EVT-'.substr(md5(uniqid()), 0, 10), 'type' => $type, 'received_at' => Carbon::now()->toDateTimeString(),
            'signature' => 'verified', 'status' => 'processed', 'attempts' => 1, 'error' => null, 'related' => null, 'summary' => $summary.' - SIMULATED EVENT',
        ], 'WH-', 100));
    }

    public function reconciliation(): Collection
    {
        return $this->rows('reconciliation')->map(function ($r) {
            $r->customer = $this->leads->customer($r->customer_id);

            return $r;
        });
    }

    public function resolveReconciliation(string $id, string $note): void
    {
        DemoStore::patch('reconciliation', $id, ['status' => 'matched', 'resolved' => true, 'note' => 'Manually reconciled: '.$note]);
    }

    // ---------------------------------------------------------------- commissions

    public function rules(): Collection
    {
        return $this->rows('commission_rules');
    }

    public function rule(?string $id): ?Record
    {
        return $this->row('commission_rules', $id);
    }

    public function updateRule(string $id, array $fields): void
    {
        $r = $this->rule($id);
        DemoStore::patch('commission_rules', $id, $fields + ['version' => $r->version + 1]);
        DemoStore::append('commission_rules', $id, 'changes', ['version' => $r->version + 1, 'at' => Carbon::now()->toDateTimeString(), 'by' => Demo::id(), 'note' => $fields['change_note'] ?? 'Rule updated (synthetic sample)']);
    }

    public function commissions(): Collection
    {
        $dayMode = DemoStore::flag('commission_day_mode', config('portal.defaults.commission_day_mode'));
        $days = (int) DemoStore::flag('commission_days', config('portal.defaults.commission_eligibility_days'));

        return $this->rows('commissions')->map(function (Record $c) use ($days, $dayMode) {
            $c->recipient = $this->dir->user($c->recipient_id);
            $c->lead = $this->leads->find($c->lead_id);
            $c->customer = $c->lead?->customer;
            // Eligibility is always derived from Closed Won + the configured rule.
            $won = Carbon::parse($c->closed_won_at);
            $eligible = $dayMode === 'business' ? $this->addBusinessDays($won->copy(), $days) : $won->copy()->addDays($days);
            $c->eligibility_date = $eligible->toDateString();
            $c->days_to_eligible = (int) Carbon::today()->diffInDays($eligible->copy()->startOfDay(), false);
            $c->current_amount = $c->amount + collect($c->adjustments)->sum('delta');

            return $c;
        })->sortByDesc('closed_won_at')->values();
    }

    private function addBusinessDays(Carbon $d, int $n): Carbon
    {
        while ($n > 0) {
            $d->addDay();
            if (! $d->isWeekend()) {
                $n--;
            }
        }

        return $d;
    }

    public function commissionsScoped(): Collection
    {
        $all = $this->commissions();
        $me = Demo::id();

        return match (Demo::roleKey()) {
            'admin', 'finance' => $all,
            'manager' => $all->filter(fn ($c) => in_array($c->recipient_id, $this->dir->reportIds($me), true))->values(),
            'team_lead', 'agent' => $all->where('recipient_id', $me)->values(),
            default => collect(),
        };
    }

    public function commission(?string $id): ?Record
    {
        return $id ? $this->commissions()->firstWhere('id', $id) : null;
    }

    public function createCommission(array $d): Record
    {
        $lead = $this->leads->find($d['lead_id']);
        $recipient = $this->dir->user($d['recipient_id']);
        $rate = (float) $d['rate'];
        $basis = (float) $d['basis_amount'];
        $amount = $d['rate_type'] === 'fixed' ? $rate : round($basis * $rate / 100, 2);
        $now = Carbon::now()->toDateTimeString();
        $hasSigned = $this->agreements->forLead($lead->id)->contains('status', 'signed');
        $status = $hasSigned ? 'pending' : 'not_eligible';
        $row = DemoStore::create('commissions', [
            'lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'recipient_id' => $recipient->id,
            'role_at_sale' => $recipient->role === 'team_lead' ? 'Team Lead' : ucfirst($recipient->role),
            'type' => $d['type'], 'basis_label' => $d['basis_label'], 'basis_amount' => $basis, 'rate_type' => $d['rate_type'], 'rate' => $rate,
            'amount' => $amount, 'currency' => 'USD', 'recurring_period' => $d['type'] === 'residual' ? 'Monthly' : null,
            'closed_won_at' => $lead->closed_won_at, 'eligibility_date' => null, 'status' => $status,
            'approved_by' => null, 'approved_at' => null, 'due_date' => null, 'paid_date' => null, 'payout_ref' => null,
            'created_by' => Demo::id(), 'created_at' => $now, 'rule_id' => $d['rule_id'] ?? null, 'rule_version' => 1, 'synthetic' => true,
            'adjustments' => [], 'ledger' => [],
            'history' => [['at' => $now, 'by' => Demo::id(), 'field' => 'Created', 'old' => null, 'new' => status_label('commission', $status), 'reason' => $d['reason'] ?? null]],
        ], 'CM-', 5100);
        app(PortalRepository::class)->log('Commission created', $row['id'], $d['reason'] ?? null, 'finance');

        return $this->commission($row['id']);
    }

    public function transition(string $id, string $to, ?string $reason = null, ?string $payoutRef = null): void
    {
        $c = $this->commission($id);
        $now = Carbon::now();
        $patch = ['status' => $to];
        if ($to === 'approved') {
            $patch += ['approved_by' => Demo::id(), 'approved_at' => $now->toDateTimeString(), 'due_date' => $now->copy()->addDays(7)->toDateString()];
        }
        if ($to === 'due') {
            $patch += ['due_date' => $now->toDateString()];
        }
        if ($to === 'paid') {
            $patch += ['paid_date' => $now->toDateString(), 'payout_ref' => $payoutRef ?: 'PAYOUT-DEMO-'.rand(1000, 9999)];
        }
        DemoStore::patch('commissions', $id, $patch);
        DemoStore::append('commissions', $id, 'history', ['at' => $now->toDateTimeString(), 'by' => Demo::id(), 'field' => 'Status', 'old' => status_label('commission', $c->status), 'new' => status_label('commission', $to), 'reason' => $reason]);
        app(PortalRepository::class)->log('Commission '.status_label('commission', $to), $id, $reason, 'finance');
    }

    /** Never edit a paid amount - add a linked adjustment entry instead. */
    public function adjust(string $id, float $delta, string $reason): void
    {
        $now = Carbon::now()->toDateTimeString();
        DemoStore::append('commissions', $id, 'adjustments', ['id' => 'ADJ-'.strtoupper(substr(uniqid(), -4)), 'at' => $now, 'by' => Demo::id(), 'delta' => $delta, 'reason' => $reason, 'linked_to' => $id]);
        DemoStore::append('commissions', $id, 'history', ['at' => $now, 'by' => Demo::id(), 'field' => 'Adjustment', 'old' => null, 'new' => ($delta >= 0 ? '+' : '').number_format($delta, 2), 'reason' => $reason]);
        app(PortalRepository::class)->log('Commission adjustment', $id, $reason, 'finance');
    }

    /**
     * A signed deal later marked Closed Lost / cancelled: apply the configurable reversal policy
     * to UNPAID commissions only. Paid commissions are never deleted - they get a linked review note.
     */
    public function applyReversalPolicy(string $leadId, string $reason): int
    {
        $policy = DemoStore::flag('reversal_policy', config('portal.defaults.reversal_policy'));
        $n = 0;
        foreach ($this->commissions()->where('lead_id', $leadId) as $c) {
            if (in_array($c->status, ['reversed'], true)) {
                continue;
            }
            if ($c->status === 'paid') {
                DemoStore::append('commissions', $c->id, 'history', ['at' => Carbon::now()->toDateTimeString(), 'by' => Demo::id(), 'field' => 'Review', 'old' => null, 'new' => 'Deal cancelled after payout - adjustment needs Finance review', 'reason' => $reason]);

                continue;
            }
            if ($policy === 'no_reversal') {
                continue;
            }
            $this->transition($c->id, $policy === 'auto_reverse' ? 'reversed' : 'on_hold', 'Opportunity marked Closed Lost: '.$reason.' (policy: '.$policy.')');
            $n++;
        }

        return $n;
    }

    /** Re-evaluates Pending -> Eligible using the configured rule and today's date (demo helper). */
    public function recalculateEligibility(): int
    {
        $n = 0;
        foreach ($this->commissions() as $c) {
            if ($c->status === 'pending' && $c->days_to_eligible <= 0) {
                $this->transition($c->id, 'eligible', 'Eligibility date reached (recalculated)');
                $n++;
            }
        }

        return $n;
    }

    // ---------------------------------------------------------------- cash & totals

    /** @return array{labels:array,cash_in:array,cash_out:array,refunds:array,commission:array,net:array} */
    public function cashSeries(): array
    {
        $rows = $this->rows('cash');
        $labels = $rows->map(fn ($r) => Carbon::today()->addMonthsNoOverflow($r->month_offset)->format('M Y'))->all();

        return [
            'labels' => $labels,
            'cash_in' => $rows->pluck('cash_in')->all(),
            'refunds' => $rows->pluck('refunds')->all(),
            'commission' => $rows->pluck('commission_paid')->all(),
            'other_out' => $rows->pluck('other_out')->all(),
            'net' => $rows->map(fn ($r) => $r->cash_in - $r->refunds - $r->commission_paid - $r->other_out)->all(),
        ];
    }

    public function totals(): array
    {
        $com = $this->commissions();
        $subs = $this->subscriptions();
        $agreements = $this->agreements->all();
        $payments = $this->payments();

        return [
            'contracted_mrr' => $agreements->where('status', 'signed')->sum('mrr'),
            'signed_value' => $agreements->where('status', 'signed')->sum(fn ($a) => $a->one_time_total + $a->mrr * $a->term_months),
            'active_subscriptions' => $subs->where('status', 'active')->count(),
            'expected_billing_30d' => $subs->where('status', 'active')->sum('amount') + $payments->where('status', 'pending')->sum('amount'),
            'received_30d' => $payments->where('status', 'completed')->filter(fn ($p) => Carbon::parse($p->date)->gte(Carbon::now()->subDays(30)))->sum('amount'),
            'failed' => $payments->where('status', 'failed')->sum('amount'),
            'outstanding' => $payments->whereIn('status', ['pending', 'failed'])->sum('amount'),
            'commission_pending' => $com->where('status', 'pending')->sum('current_amount'),
            'commission_eligible' => $com->where('status', 'eligible')->sum('current_amount'),
            'commission_approved' => $com->where('status', 'approved')->sum('current_amount'),
            'commission_due' => $com->where('status', 'due')->sum('current_amount'),
            'commission_paid' => $com->where('status', 'paid')->sum('current_amount'),
            'commission_hold' => $com->where('status', 'on_hold')->sum('current_amount'),
        ];
    }
}
