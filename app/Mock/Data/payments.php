<?php

use App\Mock\MockData as M;

$p = fn ($id, $cust, $ag, $amt, $type, $status, $day, $note = null) => [
    'id' => $id, 'square_payment_id' => 'DEMO-PAY-'.substr(md5($id), 0, 8), 'customer_id' => $cust, 'agreement_id' => $ag,
    'amount' => $amt, 'currency' => 'USD', 'type' => $type, 'status' => $status, 'date' => M::dt($day, '03:00'),
    'method' => 'Card •••• 4242 (demo)', 'note' => $note,
];

return [
    $p('PAY-6001', 'C-001', 'AG-3001', 2000, 'one_time', 'completed', -40, 'Setup fee + first month'),
    $p('PAY-6002', 'C-001', 'AG-3001', 1200, 'subscription', 'completed', -10),
    $p('PAY-6003', 'C-002', 'AG-3002', 2250, 'one_time', 'completed', -19, '50% deposit'),
    $p('PAY-6004', 'C-002', 'AG-3002', 90, 'subscription', 'completed', -18),
    $p('PAY-6005', 'C-002', 'AG-3002', 90, 'subscription', 'failed', -3, 'Card declined (simulated)'),
    $p('PAY-6006', 'C-002', 'AG-3002', 2250, 'one_time', 'pending', 10, 'Launch balance - scheduled'),
    $p('PAY-6007', 'C-020', 'AG-3004', 700, 'subscription', 'completed', -95),
    $p('PAY-6008', 'C-020', 'AG-3004', 700, 'subscription', 'completed', -65),
    $p('PAY-6009', 'C-020', 'AG-3004', 700, 'subscription', 'refunded', -35, 'Refunded in full - service dispute settled'),
    $p('PAY-6010', 'C-020', 'AG-3004', -700, 'refund', 'completed', -33, 'Refund of PAY-6009'),
    $p('PAY-6011', 'C-001', 'AG-3001', 1200, 'subscription', 'completed', -40, 'Month 1 (included in PAY-6001 batch)'),
];
