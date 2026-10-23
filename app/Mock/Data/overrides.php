<?php

use App\Mock\MockData as M;

return [
    ['id' => 'V-1', 'lead_id' => 'L-1004', 'group_key' => 'upsell', 'field_group' => 'Services and upsell details', 'effect' => 'restricted', 'applies_to' => 'Original Agent, Team Lead and Manager', 'reason' => 'Upsell details are not shared with the original deal owners until the upsell is signed.', 'by' => 'U-01', 'at' => M::dt(-2, '10:48'), 'status' => 'active', 'expires' => null],
    ['id' => 'V-2', 'lead_id' => 'L-1022', 'group_key' => 'notes', 'field_group' => 'Internal call notes', 'effect' => 'granted', 'applies_to' => 'Finance (Fiona Reyes)', 'reason' => 'Finance needs context for a pricing exception on this negotiation.', 'by' => 'U-01', 'at' => M::dt(-6, '14:20'), 'status' => 'active', 'expires' => M::d(8)],
    ['id' => 'V-3', 'lead_id' => 'L-1022', 'group_key' => 'agreement', 'field_group' => 'Agreement summary', 'effect' => 'granted', 'applies_to' => 'Team Lead (Daniel Park)', 'reason' => 'Closer needs to explain the contract status to the customer.', 'by' => 'U-01', 'at' => M::dt(-9, '09:10'), 'status' => 'active', 'expires' => M::d(5)],
    ['id' => 'V-4', 'lead_id' => 'L-1021', 'group_key' => 'commission', 'field_group' => 'Commission details', 'effect' => 'restricted', 'applies_to' => 'Former Agent (disabled)', 'reason' => 'Account disabled - access removed.', 'by' => 'U-01', 'at' => M::dt(-70, '11:05'), 'status' => 'revoked', 'expires' => null],
];
