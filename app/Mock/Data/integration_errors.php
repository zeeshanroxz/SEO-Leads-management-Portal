<?php

use App\Mock\MockData as M;

// SIMULATED integration errors for the UI. Nothing here came from a real provider.
$e = fn ($id, $day, $time, $integration, $severity, $message, $status, $ref = null) => [
    'id' => $id, 'at' => M::dt($day, $time), 'integration' => $integration, 'severity' => $severity, 'message' => $message,
    'status' => $status, 'reference' => $ref, 'retries' => $status === 'open' ? 2 : 0,
];

return [
    $e('IE-1', 0, '07:50', 'Google Calendar', 'error', 'Sync failed for Oliver Grant: OAuth token expired (simulated). Portal event E-4007 is safe; reconnect to retry.', 'open', 'E-4007'),
    $e('IE-2', -1, '14:22', 'Square', 'error', 'Webhook payment.updated failed after 3 attempts: timeout while updating ledger (simulated).', 'open', 'WH-07'),
    $e('IE-3', 0, '02:15', 'Square', 'warning', 'Webhook rejected - signature verification failed (simulated). Payload not processed.', 'open', 'WH-08'),
    $e('IE-4', -4, '11:02', 'Email', 'warning', 'Simulated bounce: mailbox full for a client CC address. No resend attempted.', 'acknowledged', 'A-2001'),
    $e('IE-5', -6, '09:15', 'Zoom', 'warning', 'Meeting link generation rate-limited (simulated); retry succeeded.', 'resolved', 'E-4005'),
    $e('IE-6', -10, '03:00', 'Square', 'info', 'Duplicate webhook delivery detected and ignored (idempotency).', 'resolved', 'WH-02'),
];
