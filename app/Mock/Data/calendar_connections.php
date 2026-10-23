<?php

use App\Mock\MockData as M;

// Per-user provider connections. ALL SIMULATED - no OAuth is performed in Phase 1.
return [
    ['id' => 'CC-U-01', 'user_id' => 'U-01', 'google' => 'not_connected', 'zoom' => 'not_connected'],
    ['id' => 'CC-U-06', 'user_id' => 'U-06', 'google' => 'connected', 'zoom' => 'connected', 'last_sync' => M::dt(0, '08:15')],
    ['id' => 'CC-U-08', 'user_id' => 'U-08', 'google' => 'connected', 'zoom' => 'not_connected', 'last_sync' => M::dt(0, '08:15')],
    ['id' => 'CC-U-10', 'user_id' => 'U-10', 'google' => 'connected', 'zoom' => 'connected', 'last_sync' => M::dt(0, '08:15')],
    ['id' => 'CC-U-11', 'user_id' => 'U-11', 'google' => 'connected', 'zoom' => 'not_connected', 'last_sync' => M::dt(0, '08:15')],
    ['id' => 'CC-U-12', 'user_id' => 'U-12', 'google' => 'not_connected', 'zoom' => 'not_connected'],
    ['id' => 'CC-U-14', 'user_id' => 'U-14', 'google' => 'failed', 'zoom' => 'not_connected', 'last_sync' => M::dt(0, '07:50'), 'error' => 'Token expired (simulated) - reconnect Google Calendar.'],
];
