<?php

use App\Mock\MockData as M;

// Reporting line for generated rows.
$line = [
    'U-08' => ['U-08', 'U-06'], 'U-10' => ['U-08', 'U-06'], 'U-11' => ['U-08', 'U-06'], 'U-12' => ['U-08', 'U-06'],
    'U-13' => ['U-09', 'U-07'], 'U-14' => ['U-09', 'U-07'], 'U-15' => ['U-09', 'U-07'],
];

$customers = collect(M::get('customers'))->keyBy('id');
$forms = collect(M::get('forms'))->keyBy('id');
$projects = collect(M::get('projects'))->keyBy('id');

$sources = ['Cold call', 'Inbound call', 'Referral', 'Website form', 'Social outreach', 'Trade show'];
$stageOrder = ['new', 'contacted', 'pitched', 'follow_up_required', 'will_confirm_later', 'appointment_booked', 'audit_quote_requested', 'proposal_sent', 'negotiation', 'closed_won'];

$answerFor = function (array $field, int $seed, array $cust) {
    $opts = $field['options'];

    return match ($field['type']) {
        'short_answer', 'text' => $field['key'] === 'q_current_site' ? $cust['website']
            : ($field['key'] === 'q_deadline' ? 'End of next quarter'
            : ($field['key'] === 'q_competitor' ? 'Larger chain nearby'
            : 'Interested, wants a clear timeline and a named contact.')),
        'number' => 8 + ($seed * 13) % 90,
        'percentage' => 10 + ($seed * 7) % 40,
        'radio', 'dropdown' => $opts[$seed % max(1, count($opts))] ?? null,
        'checkbox' => array_values(array_unique([$opts[$seed % count($opts)], $opts[($seed + 2) % count($opts)]])),
        default => null,
    };
};

$buildAnswers = function (array $lead, int $seed) use ($forms, $projects, $customers, $answerFor) {
    $form = $forms[$projects[$lead['project_id']]['form_id']];
    $created = $lead['created_at'];
    // Snapshot: newest published/archived version that existed when the lead was created.
    $versions = collect($form['versions'])->filter(fn ($v) => $v['status'] !== 'draft' && $v['published_at'] <= $created)->sortByDesc('version');
    $version = $versions->first() ?? collect($form['versions'])->where('status', 'published')->first();
    $answers = [];
    foreach ($version['sections'] as $section) {
        foreach ($section['fields'] as $field) {
            if ($field['condition'] ?? null) {
                continue;
            }
            $answers[] = ['key' => $field['key'], 'label' => $field['label'], 'type' => $field['type'], 'section' => $section['title'], 'value' => $answerFor($field, $seed + strlen($field['key']), $customers[$lead['customer_id']])];
        }
    }

    return [$version['id'], $answers];
};

$text = [
    'contacted' => ['call', 'Introduction call', 'Reached %s. Confirmed they handle marketing decisions and are open to a short follow-up.'],
    'pitched' => ['call', 'Pitch call', 'Walked through the service overview. %s asked about timelines and reporting.'],
    'follow_up_required' => ['note', 'Follow-up needed', '%s asked for a callback after reviewing the information we emailed.'],
    'will_confirm_later' => ['call', 'Will confirm later', '%s is discussing with a partner and will confirm by the end of the week.'],
    'appointment_booked' => ['appointment', 'Appointment booked', 'Discovery meeting booked with %s - see calendar.'],
    'audit_quote_requested' => ['audit', 'Audit / quote requested', 'Requested a project audit and quotation so %s can compare options.'],
    'proposal_sent' => ['email', 'Proposal sent', 'Emailed the proposal summary to %s and CC\'d the closer. (Demo simulation)'],
    'negotiation' => ['call', 'Negotiation call', 'Discussed scope and start date with %s; price discussion ongoing.'],
    'closed_won' => ['status', 'Closed Won', 'Verbal agreement received from %s. Handed off to Finance for the agreement.'],
    'closed_lost' => ['status', 'Closed Lost', 'Customer decided not to proceed.'],
    'not_interested' => ['status', 'Not Interested', 'Customer is not interested at this time.'],
];

$next = [
    'new' => ['Make first contact call', 1],
    'contacted' => ['Send overview email and book pitch', 2],
    'pitched' => ['Follow up on pitch questions', 2],
    'follow_up_required' => ['Callback as requested', 1],
    'will_confirm_later' => ['Check in for decision', 3],
    'appointment_booked' => ['Run discovery meeting', 2],
    'audit_quote_requested' => ['Review audit when submitted', 3],
    'proposal_sent' => ['Chase proposal feedback', 2],
    'negotiation' => ['Close on scope and start date', 1],
    'closed_won' => ['Finance handoff in progress', 5],
    'closed_lost' => [null, null],
    'not_interested' => [null, null],
];

// id, customer, project, services, owner, source#, review, sales, createdAgo, acceptedAgo, closedWonAgo, extra
$defs = [
    ['L-1001', 'C-001', 'P-SEO', ['S-SEO-LOCAL', 'S-SEO-SETUP'], 'U-10', 2, 'accepted', 'closed_won', 62, 60, 45, []],
    ['L-1002', 'C-002', 'P-WEB', ['S-WEB-BUILD', 'S-WEB-CARE'], 'U-11', 0, 'accepted', 'closed_won', 44, 43, 22, []],
    ['L-1003', 'C-003', 'P-SEO', ['S-SEO-LOCAL'], 'U-10', 1, 'accepted', 'closed_won', 29, 28, 9, []],
    ['L-1004', 'C-001', 'P-ADS', ['S-ADS-MGMT'], 'U-10', 2, 'accepted', 'closed_won', 14, 13, 3, ['parent_id' => 'L-1001', 'note' => 'Upsell on existing customer']],
    ['L-1005', 'C-004', 'P-SEO', ['S-SEO-LOCAL'], 'U-12', 3, 'accepted', 'proposal_sent', 27, 26, null, []],
    ['L-1006', 'C-005', 'P-SEO', ['S-SEO-LOCAL', 'S-SEO-CONTENT'], 'U-11', 0, 'accepted', 'audit_quote_requested', 26, 25, null, []],
    ['L-1007', 'C-006', 'P-WEB', ['S-WEB-BUILD'], 'U-12', 4, 'accepted', 'negotiation', 23, 22, null, []],
    ['L-1008', 'C-007', 'P-SEO', ['S-SEO-LOCAL'], 'U-10', 1, 'accepted', 'appointment_booked', 20, 19, null, []],
    ['L-1009', 'C-008', 'P-ADS', ['S-ADS-MGMT'], 'U-11', 5, 'accepted', 'follow_up_required', 17, 16, null, []],
    ['L-1010', 'C-009', 'P-AUT', ['S-AUT-FLOW'], 'U-12', 2, 'accepted', 'pitched', 15, 14, null, []],
    ['L-1011', 'C-010', 'P-SEO', ['S-SEO-LOCAL'], 'U-10', 0, 'pending_review', 'new', 2, null, null, []],
    ['L-1012', 'C-011', 'P-WEB', ['S-WEB-BUILD'], 'U-11', 3, 'pending_review', 'new', 1, null, null, []],
    ['L-1013', 'C-012', 'P-SEO', ['S-SEO-LOCAL'], 'U-12', 1, 'pending_review', 'new', 1, null, null, []],
    ['L-1014', 'C-013', 'P-AUT', ['S-AUT-FLOW'], 'U-10', 4, 'rejected', 'new', 11, null, null, ['rejection_reason' => 'Outside service area', 'rejection_notes' => 'Operates only in a region we do not serve. Re-submit if they open a local branch.']],
    ['L-1015', 'C-014', 'P-SEO', ['S-SEO-LOCAL'], 'U-11', 1, 'draft', 'new', 0, null, null, []],
    ['L-1016', 'C-015', 'P-SEO', ['S-SEO-LOCAL'], 'U-13', 2, 'accepted', 'contacted', 19, 18, null, []],
    ['L-1017', 'C-016', 'P-ADS', ['S-ADS-MGMT'], 'U-14', 0, 'accepted', 'will_confirm_later', 14, 13, null, []],
    ['L-1018', 'C-017', 'P-WEB', ['S-WEB-BUILD'], 'U-13', 5, 'accepted', 'closed_lost', 33, 32, null, ['lost_reason' => 'Budget', 'lost_notes' => 'Chose a cheaper template-based option after the quotation. Revisit in six months.']],
    ['L-1019', 'C-018', 'P-AUT', ['S-AUT-FLOW'], 'U-14', 1, 'accepted', 'not_interested', 25, 24, null, ['lost_reason' => 'Already has an agency', 'lost_notes' => 'Happy with their current provider.']],
    ['L-1020', 'C-019', 'P-SEO', ['S-SEO-LOCAL'], 'U-13', 3, 'pending_review', 'new', 3, null, null, []],
    ['L-1021', 'C-020', 'P-SEO', ['S-SEO-LOCAL'], 'U-15', 2, 'accepted', 'closed_won', 118, 117, 100, []],
    ['L-1022', 'C-021', 'P-SEO', ['S-SEO-LOCAL', 'S-SEO-CONTENT'], 'U-14', 1, 'accepted', 'closed_won', 50, 49, 30, []],
    ['L-1023', 'C-022', 'P-WEB', ['S-WEB-BUILD'], 'U-13', 4, 'pending_review', 'new', 0, null, null, []],
    ['L-1025', 'C-024', 'P-SEO', ['S-SEO-LOCAL'], 'U-08', 2, 'pending_review', 'new', 1, null, null, ['note' => 'Created by the Team Lead (own lead)']],
    ['L-1024', 'C-023', 'P-SEO', ['S-SEO-LOCAL'], 'U-14', 2, 'accepted', 'closed_won', 36, 35, 2, []],
];

$out = [];
foreach ($defs as $i => [$id, $cid, $pid, $services, $owner, $src, $review, $sales, $createdAgo, $acceptedAgo, $wonAgo, $extra]) {
    [$tl, $mgr] = $line[$owner];
    $cust = $customers[$cid];
    $primary = collect($cust['contacts'])->firstWhere('primary', true)['name'];
    $created = M::dt(-$createdAgo, '09:'.str_pad((string) (($i * 7) % 60), 2, '0', STR_PAD_LEFT));
    $lead = [
        'id' => $id, 'customer_id' => $cid, 'project_id' => $pid, 'service_ids' => $services,
        'owner_id' => $owner, 'team_lead_id' => $tl, 'manager_id' => $mgr,
        'source' => $sources[$src], 'review_status' => $review, 'sales_status' => $sales,
        'created_at' => $created,
        'submitted_at' => $review === 'draft' ? null : $created,
        'reviewed_by' => null, 'reviewed_at' => null,
        'rejection_reason' => null, 'rejection_notes' => null, 'lost_reason' => null, 'lost_notes' => null,
        'parent_id' => null, 'note' => null,
        'closed_won_at' => $wonAgo !== null ? M::dt(-$wonAgo, '15:20') : null,
        'locked' => $review === 'accepted',
    ];
    $lead = array_merge($lead, $extra);

    // Reviewer: mostly the Team Lead accepting their own team's lead; Manager / Admin occasionally.
    if ($review === 'accepted') {
        $lead['reviewed_by'] = [$tl, $mgr, 'U-01'][$i % 3 === 2 ? 1 : 0];
        $lead['reviewed_at'] = M::dt(-$acceptedAgo, '10:45');
    }
    if ($review === 'rejected') {
        $lead['reviewed_by'] = $mgr;
        $lead['reviewed_at'] = M::dt(-($createdAgo - 1), '11:10');
    }

    [$fv, $answers] = $buildAnswers($lead, $i + 3);
    $lead['form_version_id'] = $fv;
    $lead['answers'] = $answers;

    // Activities timeline.
    $acts = [];
    $n = 0;
    $act = function ($type, $by, $at, $title, $body, $extra = []) use (&$acts, &$n, $id) {
        $acts[] = array_merge(['id' => $id.'-A'.(++$n), 'type' => $type, 'by' => $by, 'at' => $at, 'title' => $title, 'body' => $body], $extra);
    };
    if ($review !== 'draft') {
        $act('system', $owner, $created, 'Lead submitted for review', 'Submitted using form version '.$fv.' ('.count($answers).' answers snapshotted).');
    } else {
        $act('system', $owner, $created, 'Draft saved', 'Draft not yet submitted for review.');
    }
    if ($review === 'accepted') {
        $act('status', $lead['reviewed_by'], $lead['reviewed_at'], 'Lead accepted', 'Submitted lead fields are now locked for the Agent.');
    }
    if ($review === 'rejected') {
        $act('status', $lead['reviewed_by'], $lead['reviewed_at'], 'Lead rejected', 'Reason: '.$lead['rejection_reason'].'. '.$lead['rejection_notes']);
    }
    if ($review === 'accepted') {
        $upto = in_array($sales, ['closed_lost', 'not_interested']) ? 'pitched' : $sales;
        $stageIdx = array_search($upto, $stageOrder);
        $day = -$acceptedAgo;
        foreach ($stageOrder as $k => $stage) {
            if ($k === 0 || $k > $stageIdx) {
                continue;
            }
            // Only emit key stages so timelines stay readable.
            if (! in_array($stage, ['contacted', 'pitched', $upto, 'closed_won'])) {
                continue;
            }
            $day += max(1, intdiv($acceptedAgo, 4));
            $day = min($day, -1);
            [$type, $title, $body] = $text[$stage];
            $act($type, $stage === 'closed_won' ? $tl : $owner, M::dt($day, '14:'.str_pad((string) (($k * 11) % 60), 2, '0', STR_PAD_LEFT)), $title, sprintf($body, $primary), $type === 'call' ? ['outcome' => $stage === 'contacted' ? 'Connected' : 'Positive'] : []);
        }
        if (in_array($sales, ['closed_lost', 'not_interested'])) {
            [$type, $title, $body] = $text[$sales];
            $act('status', $tl, M::dt(-3, '16:05'), $title, $body.' Reason: '.$lead['lost_reason'].'. '.$lead['lost_notes']);
        }
    }
    $lead['activities'] = $acts;

    [$nl, $nd] = $next[$sales];
    if ($review === 'pending_review') {
        $nl = 'Awaiting review';
        $nd = 1;
    }
    if ($review === 'draft') {
        $nl = 'Finish and submit draft';
        $nd = 0;
    }
    if ($review === 'rejected') {
        $nl = null;
        $nd = null;
    }
    $lead['next_action'] = $nl ? ['label' => $nl, 'due' => M::d(($i % 5) - 1 + (int) $nd)] : null;

    // Change history (status transitions) - always preserved.
    $hist = [];
    if ($review !== 'draft') {
        $hist[] = ['at' => $created, 'by' => $owner, 'field' => 'Lead review', 'from' => 'Draft', 'to' => 'Pending Review', 'reason' => null];
    }
    if ($review === 'accepted') {
        $hist[] = ['at' => $lead['reviewed_at'], 'by' => $lead['reviewed_by'], 'field' => 'Lead review', 'from' => 'Pending Review', 'to' => 'Accepted', 'reason' => null];
    }
    if ($review === 'rejected') {
        $hist[] = ['at' => $lead['reviewed_at'], 'by' => $lead['reviewed_by'], 'field' => 'Lead review', 'from' => 'Pending Review', 'to' => 'Rejected', 'reason' => $lead['rejection_reason'].' - '.$lead['rejection_notes']];
    }
    if ($review === 'accepted' && $sales !== 'new') {
        $hist[] = ['at' => M::dt(-max(2, intdiv($acceptedAgo, 2)), '12:00'), 'by' => $owner, 'field' => 'Sales status', 'from' => 'New', 'to' => 'Contacted', 'reason' => null];
        if (! in_array($sales, ['contacted'])) {
            $hist[] = ['at' => M::dt(-2, '12:30'), 'by' => $tl, 'field' => 'Sales status', 'from' => 'Contacted', 'to' => status_label('sales', $sales), 'reason' => $lead['lost_reason'] ? $lead['lost_reason'].' - '.$lead['lost_notes'] : null];
        }
    }
    $lead['history'] = $hist;
    $lead['handoff'] = $sales === 'closed_won' ? ['state' => match ($id) {
        'L-1004' => 'new', 'L-1024' => 'in_progress', default => 'done',
    }, 'received_at' => $lead['closed_won_at'], 'assigned_to' => $id === 'L-1004' ? null : 'U-02'] : null;
    $out[] = $lead;
}

return $out;
