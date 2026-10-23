<?php

use Carbon\Carbon;

// `for` = roles that see it in the demo. Icons are Heroicons names.
$n = fn ($id, $for, $icon, $title, $body, $hours, $link, $unread = true) => [
    'id' => $id, 'for' => $for, 'icon' => $icon, 'title' => $title, 'body' => $body,
    'at' => Carbon::now()->subHours($hours)->toDateTimeString(), 'link' => $link, 'unread' => $unread,
];

return [
    $n('N-01', ['admin', 'manager', 'team_lead'], 'clipboard-document-check', '4 leads awaiting review', 'Maple & Co Bakery and 3 others are in the review queue.', 1, ['review.index', []]),
    $n('N-02', ['agent'], 'check-circle', 'Lead accepted', 'Summit Auto Care was accepted. Original fields are now read-only; add updates as notes.', 5, ['leads.show', ['L-1009']]),
    $n('N-03', ['agent'], 'x-circle', 'Lead rejected', 'Atlas Logistics Ltd was rejected: Outside service area.', 26, ['leads.show', ['L-1014']], false),
    $n('N-04', ['auditor'], 'document-magnifying-glass', 'New audit request', 'Guidance requested for Cedar Ridge Realty.', 2, ['audits.show', ['A-2003']]),
    $n('N-05', ['agent', 'team_lead', 'manager'], 'document-text', 'Audit sent to client', 'Your audit for Cedar Ridge Realty (v2) was sent. (Demo simulation)', 168, ['audits.show', ['A-2001']], false),
    $n('N-06', ['finance', 'admin'], 'inbox-arrow-down', 'New Closed Won handoff', 'Brightside Dental Group (upsell) is ready for an agreement.', 70, ['finance.handoff', []]),
    $n('N-07', ['finance', 'admin'], 'exclamation-triangle', 'Square webhook failed', 'payment.updated failed after 3 attempts (simulated). Retry available.', 26, ['admin.activity', []]),
    $n('N-08', ['agent', 'team_lead', 'manager', 'admin', 'finance', 'auditor'], 'academic-cap', 'New learning document', 'Data protection basics for the floor was published.', 72, ['learning.index', []], false),
    $n('N-09', ['admin', 'manager', 'team_lead', 'agent'], 'calendar-days', 'Reminder: Discovery meeting', 'Greenleaf Organic Market tomorrow at 10:00. (Simulated email reminder)', 4, ['calendar.index', []]),
    $n('N-10', ['agent', 'team_lead', 'manager'], 'banknotes', 'Commission update', 'A commission was approved and is now due. Open Commissions for details.', 48, ['commissions.index', []]),
    $n('N-11', ['finance'], 'document-text', 'Agreement viewed', 'Northwind Plumbing viewed AG-3003 but has not signed.', 52, ['agreements.show', ['AG-3003']]),
    $n('N-12', ['admin'], 'link', 'Google Calendar sync failed', 'Oliver Grant\'s Google token expired (simulated).', 6, ['integrations.calendar', []]),
    $n('N-13', ['manager'], 'arrow-trending-up', 'New fully signed customer', 'AG-3002 is fully signed - MRR/MRC now visible under Recurring revenue.', 450, ['revenue.index', []], false),
];
