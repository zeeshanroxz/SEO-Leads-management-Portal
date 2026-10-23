<?php

use App\Mock\MockData as M;

// !!! SYNTHETIC SAMPLE RATES - NOT POLICY. They exist only so the UI has numbers to show. !!!
$r = fn ($id, $name, $role, $type, $basis, $rate, $v) => [
    'id' => $id, 'name' => $name, 'recipient_role' => $role, 'type' => $type, 'basis' => $basis,
    'rate_type' => 'percentage', 'rate' => $rate, 'period' => $type === 'residual' ? 'Monthly' : null,
    'eligibility_days' => 15, 'day_mode' => 'calendar', 'requires_signed' => true,
    'version' => $v, 'status' => 'active', 'effective_from' => M::d(-120), 'updated_by' => 'U-02', 'synthetic' => true,
    'changes' => [['version' => $v, 'at' => M::dt(-120 + $v, '10:00'), 'by' => 'U-02', 'note' => 'Sample rate set for the prototype.']],
];

return [
    $r('CR-1', 'One-time - Agent', 'Agent', 'one_time', 'First invoice value', 5.0, 2),
    $r('CR-2', 'One-time - Team Lead', 'Team Lead', 'one_time', 'First invoice value', 2.0, 1),
    $r('CR-3', 'One-time - Manager', 'Manager', 'one_time', 'First invoice value', 1.0, 1),
    $r('CR-4', 'Residual - Agent', 'Agent', 'residual', 'Monthly recurring charge', 2.0, 1),
    $r('CR-5', 'Residual - Team Lead', 'Team Lead', 'residual', 'Monthly recurring charge', 1.0, 1),
];
