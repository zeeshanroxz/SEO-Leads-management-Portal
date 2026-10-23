<?php

/*
| Independent status dimensions (spec section 4.1). Each value: [label, colour, flags].
| Colours map to .chip-* classes in resources/css/app.css.
| Flags: reason = a reason/note is mandatory when moving into the status,
|        terminal = status ends the flow.
*/

return [

    'lead_review' => [
        'label' => 'Lead review',
        'values' => [
            'draft' => ['Draft', 'slate'],
            'pending_review' => ['Pending Review', 'amber'],
            'accepted' => ['Accepted', 'green'],
            'rejected' => ['Rejected', 'red', ['reason' => true, 'terminal' => true]],
        ],
    ],

    'sales' => [
        'label' => 'Sales opportunity',
        'values' => [
            'new' => ['New', 'slate'],
            'contacted' => ['Contacted', 'blue'],
            'pitched' => ['Pitched', 'indigo'],
            'follow_up_required' => ['Follow-up Required', 'amber'],
            'will_confirm_later' => ['Will Confirm Later', 'orange'],
            'appointment_booked' => ['Appointment Booked', 'teal'],
            'audit_quote_requested' => ['Audit/Quote Requested', 'violet'],
            'proposal_sent' => ['Proposal Sent', 'indigo'],
            'negotiation' => ['Negotiation', 'orange'],
            'closed_won' => ['Closed Won', 'green', ['terminal' => true]],
            'closed_lost' => ['Closed Lost', 'red', ['reason' => true, 'terminal' => true]],
            'not_interested' => ['Not Interested', 'stone', ['reason' => true, 'terminal' => true]],
        ],
    ],

    'audit' => [
        'label' => 'Audit / quote',
        'values' => [
            'requested' => ['Requested', 'slate'],
            'assigned' => ['Assigned', 'blue'],
            'in_progress' => ['In Progress', 'indigo'],
            'draft' => ['Draft', 'slate'],
            'revision_requested' => ['Revision Requested', 'orange'],
            'submitted' => ['Submitted', 'violet'],
            'approved' => ['Approved', 'green'],
            'sent' => ['Sent', 'teal'],
            'cancelled' => ['Cancelled', 'red', ['terminal' => true]],
        ],
    ],

    'agreement' => [
        'label' => 'Agreement',
        'values' => [
            'draft' => ['Draft', 'slate'],
            'ready_to_send' => ['Ready to Send', 'blue'],
            'sent' => ['Sent', 'indigo'],
            'viewed' => ['Viewed', 'violet'],
            'partially_signed' => ['Partially Signed', 'amber'],
            'signed' => ['Signed', 'green', ['terminal' => true]],
            'rejected' => ['Rejected', 'red', ['terminal' => true]],
            'expired' => ['Expired', 'stone', ['terminal' => true]],
            'cancelled' => ['Cancelled', 'red', ['terminal' => true]],
            'superseded' => ['Superseded', 'stone', ['terminal' => true]],
        ],
    ],

    'billing' => [
        'label' => 'Billing',
        'values' => [
            'not_set_up' => ['Not Set Up', 'slate'],
            'pending' => ['Pending', 'amber'],
            'active' => ['Active', 'green'],
            'past_due' => ['Past Due', 'orange'],
            'failed' => ['Failed', 'red'],
            'cancelled' => ['Cancelled', 'stone', ['terminal' => true]],
            'ended' => ['Ended', 'stone', ['terminal' => true]],
        ],
    ],

    'commission' => [
        'label' => 'Commission',
        'values' => [
            'not_eligible' => ['Not Eligible', 'stone'],
            'pending' => ['Pending', 'amber'],
            'eligible' => ['Eligible', 'blue'],
            'approved' => ['Approved', 'indigo'],
            'due' => ['Due', 'orange'],
            'paid' => ['Paid', 'green', ['terminal' => true]],
            'on_hold' => ['On Hold', 'violet'],
            'reversed' => ['Reversed', 'red', ['terminal' => true]],
        ],
    ],

    // Ad-hoc chips used outside the six business dimensions.
    'misc' => [
        'label' => 'Other',
        'values' => [
            'ok' => ['Healthy', 'green'],
            'warning' => ['Warning', 'amber'],
            'error' => ['Error', 'red'],
            'synced' => ['Synced', 'green'],
            'failed' => ['Failed', 'red'],
            'not_connected' => ['Not connected', 'slate'],
            'connected' => ['Connected', 'green'],
            'processed' => ['Processed', 'green'],
            'duplicate_ignored' => ['Duplicate ignored', 'slate'],
            'matched' => ['Matched', 'green'],
            'mismatch' => ['Mismatch', 'red'],
            'unreviewed' => ['Awaiting review', 'amber'],
            'scheduled' => ['Scheduled', 'blue'],
            'rescheduled' => ['Rescheduled', 'amber'],
            'completed' => ['Completed', 'green'],
            'active' => ['Active', 'green'],
            'disabled' => ['Disabled', 'stone'],
            'published' => ['Published', 'green'],
            'draft' => ['Draft', 'slate'],
            'internal' => ['Internal only', 'amber'],
            'customer' => ['Customer-facing', 'teal'],
            'granted' => ['Granted', 'green'],
            'restricted' => ['Restricted', 'red'],
            'revoked' => ['Revoked', 'stone'],
        ],
    ],
];
