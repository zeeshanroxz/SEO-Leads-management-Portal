<?php

use App\Mock\MockData as M;
use Carbon\Carbon;

// SYNTHETIC amounts derived from sample rates (see commission_rules.php). Not policy.
$leads = collect(M::get('leads'))->keyBy('id');
$users = collect(M::get('users'))->keyBy('id');
$days = (int) config('portal.defaults.commission_eligibility_days', 15);

$cm = function (string $id, string $lead, string $recipient, string $type, float $basisAmount, string $basisLabel, float $rate, string $status, array $o = []) use ($leads, $users, $days) {
    $won = $leads[$lead]['closed_won_at'];
    $eligible = Carbon::parse($won)->addDays($days)->toDateString();

    return array_merge([
        'id' => $id, 'lead_id' => $lead, 'customer_id' => $leads[$lead]['customer_id'],
        'recipient_id' => $recipient, 'role_at_sale' => $users[$recipient]['role'] === 'team_lead' ? 'Team Lead' : ucfirst($users[$recipient]['role']),
        'type' => $type, 'basis_label' => $basisLabel, 'basis_amount' => $basisAmount, 'rate_type' => 'percentage', 'rate' => $rate,
        'amount' => round($basisAmount * $rate / 100, 2), 'currency' => 'USD', 'recurring_period' => $type === 'residual' ? 'Monthly' : null,
        'closed_won_at' => $won, 'eligibility_date' => $eligible, 'status' => $status,
        'approved_by' => null, 'approved_at' => null, 'due_date' => null, 'paid_date' => null, 'payout_ref' => null,
        'created_by' => 'U-02', 'created_at' => M::dt(-1, '10:00'), 'rule_id' => $type === 'one_time' ? 'CR-1' : 'CR-4', 'rule_version' => 1,
        'synthetic' => true, 'adjustments' => [], 'history' => [], 'ledger' => [],
    ], $o);
};

$h = fn (string $at, string $by, string $field, ?string $old, ?string $new, ?string $reason = null) => ['at' => $at, 'by' => $by, 'field' => $field, 'old' => $old, 'new' => $new, 'reason' => $reason];

return [
    // L-1001 - Closed Won 45 days ago
    $cm('CM-5001', 'L-1001', 'U-10', 'one_time', 2000, 'First invoice value', 5, 'paid', ['approved_by' => 'U-02', 'approved_at' => M::dt(-28, '10:00'), 'due_date' => M::d(-25), 'paid_date' => M::d(-22), 'payout_ref' => 'PAYOUT-DEMO-0001', 'created_at' => M::dt(-44, '10:00'), 'history' => [$h(M::dt(-44, '10:00'), 'U-02', 'Created', null, 'Pending'), $h(M::dt(-28, '10:00'), 'U-02', 'Status', 'Eligible', 'Approved'), $h(M::dt(-22, '12:00'), 'U-02', 'Status', 'Due', 'Paid', 'Payout recorded')]]),
    $cm('CM-5002', 'L-1001', 'U-08', 'one_time', 2000, 'First invoice value', 2, 'paid', ['rule_id' => 'CR-2', 'approved_by' => 'U-02', 'approved_at' => M::dt(-28, '10:00'), 'due_date' => M::d(-25), 'paid_date' => M::d(-22), 'payout_ref' => 'PAYOUT-DEMO-0002', 'created_by' => 'U-06', 'history' => [$h(M::dt(-44, '10:30'), 'U-06', 'Created', null, 'Pending')]]),
    $cm('CM-5003', 'L-1001', 'U-06', 'one_time', 2000, 'First invoice value', 1, 'paid', ['rule_id' => 'CR-3', 'approved_by' => 'U-02', 'approved_at' => M::dt(-28, '11:00'), 'due_date' => M::d(-25), 'paid_date' => M::d(-22), 'payout_ref' => 'PAYOUT-DEMO-0003', 'created_by' => 'U-02']),
    $cm('CM-5004', 'L-1001', 'U-10', 'residual', 1200, 'Monthly recurring charge', 2, 'due', ['approved_by' => 'U-02', 'approved_at' => M::dt(-3, '10:00'), 'due_date' => M::d(0), 'ledger' => [
        ['period' => 'Month 1', 'amount' => 24.0, 'status' => 'paid', 'date' => M::d(-12), 'ref' => 'PAYOUT-DEMO-0011'],
        ['period' => 'Month 2', 'amount' => 24.0, 'status' => 'due', 'date' => M::d(0), 'ref' => null],
    ]]),
    // L-1002 - Closed Won 22 days ago (eligible 7 days ago)
    $cm('CM-5005', 'L-1002', 'U-11', 'one_time', 2250, 'First invoice value', 5, 'approved', ['approved_by' => 'U-02', 'approved_at' => M::dt(-2, '10:00'), 'due_date' => M::d(5), 'history' => [$h(M::dt(-2, '10:00'), 'U-02', 'Status', 'Eligible', 'Approved')]]),
    $cm('CM-5006', 'L-1002', 'U-08', 'one_time', 2250, 'First invoice value', 2, 'eligible', ['rule_id' => 'CR-2', 'created_by' => 'U-06']),
    $cm('CM-5007', 'L-1002', 'U-06', 'one_time', 2250, 'First invoice value', 1, 'eligible', ['rule_id' => 'CR-3', 'created_by' => 'U-06', 'history' => [$h(M::dt(-1, '10:00'), 'U-06', 'Created', null, 'Eligible', 'Added for my team')]]),
    // L-1003 - Closed Won 9 days ago (eligible in 6 days)
    $cm('CM-5008', 'L-1003', 'U-10', 'one_time', 950, 'First invoice value', 5, 'pending'),
    $cm('CM-5009', 'L-1003', 'U-08', 'one_time', 950, 'First invoice value', 2, 'pending', ['rule_id' => 'CR-2', 'created_by' => 'U-06']),
    // L-1004 - Closed Won 3 days ago, agreement not yet created
    $cm('CM-5010', 'L-1004', 'U-10', 'one_time', 900, 'First invoice value', 5, 'not_eligible', ['history' => [$h(M::dt(-3, '16:00'), 'U-02', 'Created', null, 'Not Eligible', 'Waiting for signed agreement (prerequisite)')]]),
    // L-1021 - older deal, ended agreement, refund
    $cm('CM-5011', 'L-1021', 'U-15', 'one_time', 700, 'First invoice value', 5, 'paid', ['approved_by' => 'U-02', 'approved_at' => M::dt(-80, '10:00'), 'due_date' => M::d(-80), 'paid_date' => M::d(-78), 'payout_ref' => 'PAYOUT-DEMO-0004']),
    $cm('CM-5012', 'L-1021', 'U-09', 'one_time', 700, 'First invoice value', 2, 'paid', ['rule_id' => 'CR-2', 'approved_by' => 'U-02', 'approved_at' => M::dt(-80, '10:00'), 'due_date' => M::d(-80), 'paid_date' => M::d(-78), 'payout_ref' => 'PAYOUT-DEMO-0005']),
    $cm('CM-5013', 'L-1021', 'U-15', 'residual', 700, 'Monthly recurring charge', 2, 'reversed', ['approved_by' => 'U-02', 'due_date' => M::d(-33), 'adjustments' => [
        ['id' => 'ADJ-01', 'at' => M::dt(-33, '10:30'), 'by' => 'U-02', 'delta' => -14.0, 'reason' => 'Linked refund PAY-6010 reversed the period charge', 'linked_to' => 'CM-5013'],
    ], 'history' => [$h(M::dt(-33, '10:30'), 'U-02', 'Status', 'Paid', 'Reversed', 'Customer refund - see adjustment ADJ-01')]]),
    // L-1022 - partially signed agreement: prerequisite not met
    $cm('CM-5014', 'L-1022', 'U-14', 'one_time', 1650, 'First invoice value', 5, 'on_hold', ['history' => [$h(M::dt(-14, '10:00'), 'U-02', 'Status', 'Eligible', 'On Hold', 'Agreement AG-3005 only partially signed')]]),
    $cm('CM-5015', 'L-1022', 'U-09', 'one_time', 1650, 'First invoice value', 2, 'on_hold', ['rule_id' => 'CR-2', 'created_by' => 'U-07']),
    // L-1024 - Closed Won 2 days ago
    $cm('CM-5016', 'L-1024', 'U-14', 'one_time', 1900, 'First invoice value', 5, 'not_eligible'),
];
