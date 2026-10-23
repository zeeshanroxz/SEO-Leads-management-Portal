<?php

use App\Mock\MockData as M;

// Masked demo values only. No real Square credential is ever entered, stored or shown.
return [[
    'id' => 'default',
    'environment' => 'sandbox',
    'status' => 'connected',
    'application_id' => 'sandbox-sq0idb-••••••••••••••••',
    'access_token_masked' => 'EAAA••••••••••••••••••••••••••••••Q3f9',
    'webhook_signature_key_masked' => '••••••••••••••••••••••k7Zp',
    'location_id' => 'LOC-DEMO-0001',
    'location_name' => 'Demo Location (sandbox)',
    'webhook_url' => 'https://portal.example/webhooks/square  (demo URL)',
    'last_test_at' => M::dt(-1, '09:12'),
    'last_test_result' => 'ok',
    'token_rotated_at' => M::dt(-33, '10:00'),
    'account' => 'Demo Merchant (sandbox)',
    'currency' => 'USD',
]];
