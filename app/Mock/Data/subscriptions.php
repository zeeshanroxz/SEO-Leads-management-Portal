<?php

use App\Mock\MockData as M;

return [
    ['id' => 'SUB-01', 'customer_id' => 'C-001', 'agreement_id' => 'AG-3001', 'square_subscription_id' => 'DEMO-SUB-1001', 'plan' => 'Local SEO Retainer', 'amount' => 1200, 'cadence' => 'Monthly', 'status' => 'active', 'started' => M::d(-40), 'next_charge' => M::d(20), 'authorisation' => 'Billing authorisation on signed AG-3001'],
    ['id' => 'SUB-02', 'customer_id' => 'C-002', 'agreement_id' => 'AG-3002', 'square_subscription_id' => 'DEMO-SUB-1002', 'plan' => 'Website Care Plan', 'amount' => 90, 'cadence' => 'Monthly', 'status' => 'past_due', 'started' => M::d(-18), 'next_charge' => M::d(2), 'authorisation' => 'Billing authorisation on signed AG-3002'],
    ['id' => 'SUB-03', 'customer_id' => 'C-020', 'agreement_id' => 'AG-3004', 'square_subscription_id' => 'DEMO-SUB-1003', 'plan' => 'Local SEO Retainer', 'amount' => 700, 'cadence' => 'Monthly', 'status' => 'ended', 'started' => M::d(-360), 'next_charge' => null, 'authorisation' => 'Billing authorisation on signed AG-3004'],
    ['id' => 'SUB-04', 'customer_id' => 'C-021', 'agreement_id' => 'AG-3005', 'square_subscription_id' => null, 'plan' => 'Local SEO + Content', 'amount' => 1650, 'cadence' => 'Monthly', 'status' => 'not_set_up', 'started' => null, 'next_charge' => null, 'authorisation' => 'Blocked: agreement not fully signed'],
];
