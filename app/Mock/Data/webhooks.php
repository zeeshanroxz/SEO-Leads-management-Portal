<?php

use App\Mock\MockData as M;

// SIMULATED events. Signature verification and idempotency are illustrated, not performed.
$w = fn ($id, $type, $day, $time, $status, $extra = []) => array_merge([
    'id' => $id, 'event_id' => 'DEMO-EVT-'.substr(md5($id), 0, 10), 'type' => $type, 'received_at' => M::dt($day, $time),
    'signature' => 'verified', 'status' => $status, 'attempts' => 1, 'error' => null, 'related' => null,
    'summary' => '',
], $extra);

return [
    $w('WH-01', 'payment.updated', -10, '03:00:12', 'processed', ['related' => 'PAY-6002', 'summary' => 'Payment completed for SUB-01']),
    $w('WH-02', 'payment.updated', -10, '03:00:13', 'duplicate_ignored', ['related' => 'PAY-6002', 'summary' => 'Same event delivered twice - second delivery ignored (idempotency key matched)']),
    $w('WH-03', 'subscription.updated', -3, '03:00:20', 'processed', ['related' => 'SUB-02', 'summary' => 'Subscription moved to past_due after failed payment']),
    $w('WH-04', 'payment.updated', -3, '03:00:19', 'processed', ['related' => 'PAY-6005', 'summary' => 'Payment failed - card declined (simulated)']),
    $w('WH-05', 'refund.updated', -33, '10:12:44', 'processed', ['related' => 'PAY-6010', 'summary' => 'Refund recorded and linked to PAY-6009']),
    $w('WH-06', 'customer.created', -18, '11:00:05', 'processed', ['related' => 'SQC-002', 'summary' => 'Customer mapped to C-002']),
    $w('WH-07', 'payment.updated', -1, '14:22:01', 'failed', ['attempts' => 3, 'related' => null, 'error' => 'Timeout while updating ledger (simulated). Safe to retry - idempotency key protects against double-booking.', 'summary' => 'Processing failed - retry available']),
    $w('WH-08', 'payment.updated', 0, '02:15:30', 'failed', ['signature' => 'invalid', 'attempts' => 1, 'error' => 'Signature verification failed - payload rejected and not processed (simulated).', 'summary' => 'Rejected: invalid signature']),
    $w('WH-09', 'subscription.created', -40, '12:00:00', 'processed', ['related' => 'SUB-01', 'summary' => 'Subscription created from AG-3001']),
    $w('WH-10', 'invoice.payment_made', -19, '09:31:09', 'processed', ['related' => 'PAY-6003', 'summary' => 'Deposit invoice paid']),
];
