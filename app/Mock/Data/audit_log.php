<?php

use App\Mock\MockData as M;

$a = fn ($id, $days, $time, $actor, $action, $entity, $reason = null, $cat = 'general') => [
    'id' => $id, 'at' => M::dt($days, $time), 'actor_id' => $actor, 'action' => $action, 'entity' => $entity,
    'reason' => $reason, 'category' => $cat, 'ip' => '192.0.2.'.(10 + (int) substr($id, -2)),
];

return [
    $a('AL-01', 0, '08:12', 'U-02', 'Logged in', 'Session', null, 'auth'),
    $a('AL-02', 0, '09:03', 'U-08', 'Accepted lead', 'L-1009', null, 'lead'),
    $a('AL-03', -1, '14:10', 'U-06', 'Rejected lead', 'L-1014', 'Outside service area', 'lead'),
    $a('AL-04', -1, '15:22', 'U-01', 'Published form version', 'FORM-1 v3', 'Added conditional competitor question', 'config'),
    $a('AL-05', -2, '10:48', 'U-01', 'Visibility override granted', 'L-1004 / upsell details', 'Upsell pricing hidden from original owner until deal is signed', 'permission'),
    $a('AL-06', -2, '11:30', 'U-02', 'Agreement version created', 'AG-3006 v1', null, 'agreement'),
    $a('AL-07', -3, '16:12', 'U-02', 'Square customer linked', 'C-002 -> DEMO-CUST-BBB222', null, 'finance'),
    $a('AL-08', -3, '16:05', 'U-08', 'Marked Closed Won', 'L-1004', null, 'lead'),
    $a('AL-09', -5, '09:44', 'U-02', 'Commission approved', 'CM-5005', null, 'finance'),
    $a('AL-10', -7, '16:10', 'U-11', 'Audit v2 sent to client (demo)', 'A-2001', null, 'audit'),
    $a('AL-11', -8, '13:00', 'U-02', 'Agreement sent (demo)', 'AG-3005', null, 'agreement'),
    $a('AL-12', -9, '10:05', 'U-01', 'User role changed', 'U-12 Ravi Shah', 'Promoted to Alpha Team agent pool', 'permission'),
    $a('AL-13', -14, '10:00', 'U-02', 'Commission put on hold', 'CM-5014', 'Agreement AG-3005 only partially signed', 'finance'),
    $a('AL-14', -33, '10:30', 'U-02', 'Commission reversed', 'CM-5013', 'Customer refund PAY-6010', 'finance'),
    $a('AL-15', -33, '10:00', 'U-01', 'Square token rotated', 'Integration: Square', null, 'integration'),
    $a('AL-16', -41, '13:21', 'U-02', 'Agreement finalised and locked', 'AG-3001 v2', null, 'agreement'),
    $a('AL-17', -45, '16:00', 'U-08', 'Marked Closed Won', 'L-1001', null, 'lead'),
    $a('AL-18', -70, '11:00', 'U-01', 'User disabled', 'U-15 Zara Idris', 'Left the company', 'permission'),
    $a('AL-19', -21, '11:00', 'U-01', 'Published learning document', 'LR-1 v3', null, 'learning'),
    $a('AL-20', -5, '09:00', 'U-01', 'Commission rule updated', 'CR-1 v2', 'Sample rate changed (synthetic)', 'config'),
];
