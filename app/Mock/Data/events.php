<?php

use App\Mock\MockData as M;

$leads = collect(M::get('leads'))->keyBy('id');
$att = fn (string $id, string $resp = 'accepted') => ['user_id' => $id, 'response' => $resp];

$e = function (string $id, string $title, string $type, ?string $lead, int $day, string $start, string $end, string $organizer, array $attendees, array $extra = []) use ($leads) {
    return array_merge([
        'id' => $id, 'title' => $title, 'type' => $type, 'lead_id' => $lead,
        'customer_id' => $lead ? $leads[$lead]['customer_id'] : null,
        'start' => M::dt($day, $start), 'end' => M::dt($day, $end), 'timezone' => 'UTC',
        'organizer_id' => $organizer, 'attendees' => $attendees,
        'location' => $type === 'meeting' ? 'https://zoom.example/j/000-000-'.substr($id, -3).' (demo link)' : 'Phone',
        'notes' => null, 'reminders' => [['minutes' => 30, 'channel' => 'in_app'], ['minutes' => 60, 'channel' => 'email']],
        'status' => 'scheduled', 'cancel_reason' => null,
        'sync' => ['google' => 'not_connected', 'zoom' => 'not_connected', 'last_sync' => null, 'error' => null],
    ], $extra);
};

return [
    $e('E-4001', 'Discovery meeting - Greenleaf Organic Market', 'meeting', 'L-1008', 1, '10:00', '10:45', 'U-10', [$att('U-08', 'accepted')], ['notes' => 'Walk through goals from intake form.', 'sync' => ['google' => 'synced', 'zoom' => 'synced', 'last_sync' => M::dt(0, '08:15'), 'error' => null]]),
    $e('E-4002', 'Callback - Summit Auto Care', 'call', 'L-1009', 0, '11:30', '11:45', 'U-11', [], ['sync' => ['google' => 'synced', 'zoom' => 'not_connected', 'last_sync' => M::dt(0, '08:15'), 'error' => null]]),
    $e('E-4003', 'Proposal review - Lumen Yoga Studio', 'meeting', 'L-1005', 0, '14:00', '14:30', 'U-12', [$att('U-08'), $att('U-06', 'tentative')], ['notes' => 'Review pricing options with Team Lead before sending.']),
    $e('E-4004', 'Negotiation call - Pixel Forge Print Co.', 'call', 'L-1007', 2, '09:30', '10:00', 'U-08', [$att('U-12')]),
    $e('E-4005', 'Audit walkthrough - Cedar Ridge Realty', 'meeting', 'L-1006', 3, '13:00', '13:45', 'U-11', [$att('U-08'), $att('U-04')], ['notes' => 'Present audit findings to Paula and Victor.']),
    $e('E-4006', 'Follow-up - Willow Creek Pediatrics', 'call', 'L-1016', 1, '15:00', '15:15', 'U-13', [], []),
    $e('E-4007', 'Pitch - Copperline Roofing', 'meeting', 'L-1017', 4, '11:00', '11:45', 'U-14', [$att('U-09')], ['sync' => ['google' => 'failed', 'zoom' => 'not_connected', 'last_sync' => M::dt(0, '07:50'), 'error' => 'Google token expired - reconnect Google Calendar (simulated error). The portal event is safe.']]),
    $e('E-4008', 'Team stand-up - Alpha Team', 'training', null, 0, '09:00', '09:15', 'U-08', [$att('U-10'), $att('U-11'), $att('U-12')], ['location' => 'Room 2 / Zoom', 'reminders' => [['minutes' => 15, 'channel' => 'in_app']]]),
    $e('E-4009', 'Team stand-up - Bravo Team', 'training', null, 0, '09:15', '09:30', 'U-09', [$att('U-13'), $att('U-14')], ['location' => 'Room 3 / Zoom']),
    $e('E-4010', 'Training: Objection handling', 'training', null, 5, '16:00', '17:00', 'U-01', [$att('U-06'), $att('U-07'), $att('U-08'), $att('U-09'), $att('U-10'), $att('U-11'), $att('U-12'), $att('U-13'), $att('U-14')], ['location' => 'Zoom (demo link)', 'notes' => 'Mandatory - see Learning library for the call script.']),
    $e('E-4011', 'Contract questions - Polar Peak Cycles', 'call', 'L-1022', 2, '16:30', '16:45', 'U-14', [$att('U-09')], []),
    $e('E-4012', 'Pipeline review - Alpha Team', 'meeting', null, 3, '10:00', '11:00', 'U-06', [$att('U-08')], ['location' => 'Zoom (demo link)']),
    $e('E-4013', 'Intro call - Maple & Co Bakery (cancelled)', 'call', 'L-1011', -1, '10:30', '10:45', 'U-10', [], ['status' => 'cancelled', 'cancel_reason' => 'Customer asked to wait until the lead is reviewed.']),
    $e('E-4014', 'Pitch - Bluebird Accounting', 'meeting', 'L-1010', -3, '14:00', '14:45', 'U-12', [$att('U-08')], ['status' => 'completed']),
    $e('E-4015', 'Follow-up - Riverbend Veterinary', 'call', 'L-1013', 6, '12:00', '12:15', 'U-12', [], ['status' => 'rescheduled', 'notes' => 'Moved from Tuesday at the customer\'s request.']),
    $e('E-4016', 'Onboarding call - Harbor & Pine', 'meeting', 'L-1002', -6, '11:00', '11:45', 'U-11', [$att('U-08')], ['status' => 'completed']),
    $e('E-4017', 'Quarterly review - Brightside Dental', 'meeting', 'L-1001', 9, '10:00', '10:30', 'U-08', [$att('U-10')], []),
    $e('E-4018', 'Audit briefing - internal', 'meeting', 'L-1005', 1, '16:00', '16:20', 'U-04', [$att('U-12')], ['location' => 'Zoom (demo link)']),
];
