<?php

use App\Mock\MockData as M;

$leads = collect(M::get('leads'))->keyBy('id');
$clauses = collect(M::get('clauses'))->keyBy('id');
$services = collect(M::get('services'))->keyBy('id');

$snap = fn (array $ids) => array_map(fn ($id) => ['id' => $id, 'title' => $clauses[$id]['title'], 'version' => $clauses[$id]['version'], 'body' => $clauses[$id]['body']], $ids);
$line = fn (string $sid, float $price, string $billing, ?int $qty = 1) => [
    'service_id' => $sid, 'name' => $services[$sid]['name'], 'price' => $price, 'billing' => $billing, 'qty' => $qty,
];

$ag = function (string $id, string $lead, string $status, array $lines, array $o = []) use ($leads, $snap, $services) {
    $mrr = collect($lines)->where('billing', 'recurring')->sum(fn ($l) => $l['price'] * $l['qty']);
    $once = collect($lines)->where('billing', 'one_time')->sum(fn ($l) => $l['price'] * $l['qty']);
    $clauseIds = array_values(array_unique(array_merge(...array_map(fn ($l) => $services[$l['service_id']]['clause_ids'], $lines))));

    return array_merge([
        'id' => $id, 'lead_id' => $lead, 'customer_id' => $leads[$lead]['customer_id'], 'status' => $status,
        'currency' => 'USD', 'lines' => $lines, 'mrr' => $mrr, 'one_time_total' => $once,
        'term_months' => 12, 'start_date' => null, 'end_date' => null, 'renewal' => 'Auto-renews monthly after the initial term unless cancelled with 30 days notice (prototype default).',
        'payment_schedule' => 'Monthly in advance', 'clauses' => $snap($clauseIds),
        'version' => 1, 'versions' => [], 'signers' => [], 'events' => [],
        'created_by' => 'U-02', 'created_at' => M::dt(-10, '10:00'),
        'signed_at' => null, 'hash' => null, 'billing_status' => 'not_set_up',
        'offline_upload' => null,
    ], $o);
};

$signer = fn (string $name, string $role, string $email, ?string $at, string $method = 'typed') => [
    'name' => $name, 'role' => $role, 'email' => $email, 'signed_at' => $at, 'method' => $at ? $method : null,
    'verification' => $at ? 'Email one-time code (simulated)' : 'Pending', 'consent' => (bool) $at,
];

$ev = fn (string $at, string $text, string $by = 'System') => ['at' => $at, 'text' => $text, 'by' => $by];

return [
    $ag('AG-3001', 'L-1001', 'signed', [$line('S-SEO-LOCAL', 1200, 'recurring'), $line('S-SEO-SETUP', 800, 'one_time')], [
        'version' => 2, 'start_date' => M::d(-40), 'end_date' => M::d(325), 'created_at' => M::dt(-44, '10:00'), 'signed_at' => M::dt(-41, '13:20'),
        'hash' => 'sha256:9f2c...a41e (demo placeholder)', 'billing_status' => 'active',
        'versions' => [['no' => 1, 'status' => 'superseded', 'at' => M::dt(-44, '10:00'), 'note' => 'First draft sent - price corrected'], ['no' => 2, 'status' => 'signed', 'at' => M::dt(-42, '09:10'), 'note' => 'Corrected setup fee. Final signed version.']],
        'signers' => [$signer('Dr. Helen Brooks', 'Customer', 'helen@brightsidedental.example', M::dt(-41, '13:18')), $signer('Kai Brandt', 'Company signatory', 'kai.brandt@callcentre.example', M::dt(-41, '13:20'))],
        'events' => [$ev(M::dt(-44, '10:00'), 'Draft created', 'Fiona Reyes'), $ev(M::dt(-42, '09:12'), 'Version 2 sent (secure link, demo)', 'Fiona Reyes'), $ev(M::dt(-41, '13:05'), 'Viewed by customer'), $ev(M::dt(-41, '13:18'), 'Signed by Dr. Helen Brooks (typed name, consent ticked)'), $ev(M::dt(-41, '13:20'), 'Counter-signed by Kai Brandt'), $ev(M::dt(-41, '13:21'), 'Final version locked + receipt generated (demo)')],
    ]),
    $ag('AG-3002', 'L-1002', 'signed', [$line('S-WEB-BUILD', 4500, 'one_time'), $line('S-WEB-CARE', 90, 'recurring')], [
        'term_months' => 12, 'start_date' => M::d(-18), 'end_date' => M::d(347), 'created_at' => M::dt(-21, '09:00'), 'signed_at' => M::dt(-19, '11:30'),
        'hash' => 'sha256:41bb...7c20 (demo placeholder)', 'billing_status' => 'active', 'payment_schedule' => '50% on signature, 50% on launch; care plan monthly',
        'versions' => [['no' => 1, 'status' => 'signed', 'at' => M::dt(-21, '09:00'), 'note' => 'Signed first time']],
        'signers' => [$signer('Gareth Lowe', 'Customer', 'gareth@harborpine.example', M::dt(-19, '11:28'), 'drawn'), $signer('Fiona Reyes', 'Company signatory', 'fiona.reyes@callcentre.example', M::dt(-19, '11:30'))],
        'events' => [$ev(M::dt(-21, '09:00'), 'Draft created', 'Fiona Reyes'), $ev(M::dt(-20, '15:00'), 'Sent (demo)', 'Fiona Reyes'), $ev(M::dt(-19, '11:28'), 'Signed by Gareth Lowe (drawn signature)'), $ev(M::dt(-19, '11:30'), 'Counter-signed')],
    ]),
    $ag('AG-3003', 'L-1003', 'viewed', [$line('S-SEO-LOCAL', 950, 'recurring')], [
        'term_months' => 6, 'created_at' => M::dt(-6, '10:00'),
        'versions' => [['no' => 1, 'status' => 'viewed', 'at' => M::dt(-5, '14:00'), 'note' => 'Sent']],
        'signers' => [$signer('Tom Alder', 'Customer', 'tom@northwindplumbing.example', null), $signer('Fiona Reyes', 'Company signatory', 'fiona.reyes@callcentre.example', null)],
        'events' => [$ev(M::dt(-6, '10:00'), 'Draft created', 'Fiona Reyes'), $ev(M::dt(-5, '14:00'), 'Sent (demo)', 'Fiona Reyes'), $ev(M::dt(-2, '09:44'), 'Viewed by customer (not yet signed)')],
    ]),
    $ag('AG-3004', 'L-1021', 'expired', [$line('S-SEO-LOCAL', 700, 'recurring')], [
        'term_months' => 12, 'start_date' => M::d(-365 + 5), 'end_date' => M::d(-5), 'created_at' => M::dt(-98, '10:00'), 'signed_at' => M::dt(-97, '10:00'), 'billing_status' => 'ended',
        'hash' => 'sha256:c0de...11aa (demo placeholder)',
        'versions' => [['no' => 1, 'status' => 'signed', 'at' => M::dt(-98, '10:00'), 'note' => 'Signed']],
        'signers' => [$signer('Walter Abbott', 'Customer', 'walter@fernhillgarden.example', M::dt(-97, '10:00')), $signer('Kai Brandt', 'Company signatory', 'kai.brandt@callcentre.example', M::dt(-97, '10:05'))],
        'events' => [$ev(M::dt(-97, '10:00'), 'Signed by Walter Abbott'), $ev(M::dt(-5, '00:01'), 'Term ended - agreement expired and hidden from active Agent views')],
    ]),
    $ag('AG-3005', 'L-1022', 'partially_signed', [$line('S-SEO-LOCAL', 1200, 'recurring'), $line('S-SEO-CONTENT', 450, 'recurring')], [
        'term_months' => 12, 'created_at' => M::dt(-9, '10:00'),
        'versions' => [['no' => 1, 'status' => 'partially_signed', 'at' => M::dt(-8, '10:00'), 'note' => 'Sent']],
        'signers' => [$signer('Greta Holm', 'Customer', 'greta@polarpeakcycles.example', M::dt(-3, '16:12'), 'drawn'), $signer('Fiona Reyes', 'Company signatory', 'fiona.reyes@callcentre.example', null)],
        'events' => [$ev(M::dt(-8, '10:00'), 'Sent (demo)', 'Fiona Reyes'), $ev(M::dt(-3, '16:12'), 'Signed by Greta Holm (drawn signature)'), $ev(M::dt(-3, '16:13'), 'Waiting for company counter-signature')],
        'billing_status' => 'pending',
    ]),
    $ag('AG-3006', 'L-1024', 'ready_to_send', [$line('S-SEO-LOCAL', 1100, 'recurring'), $line('S-SEO-SETUP', 800, 'one_time')], [
        'term_months' => 12, 'created_at' => M::dt(-1, '09:30'),
        'versions' => [['no' => 1, 'status' => 'ready_to_send', 'at' => M::dt(-1, '09:30'), 'note' => 'Prepared from Closed Won handoff']],
        'signers' => [$signer('Dr. Priyanka Nair', 'Customer', 'priyanka@mosaicdentalstudio.example', null), $signer('Fiona Reyes', 'Company signatory', 'fiona.reyes@callcentre.example', null)],
        'events' => [$ev(M::dt(-1, '09:30'), 'Draft created from Closed Won handoff', 'Fiona Reyes')],
    ]),
];
