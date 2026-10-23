<?php

use App\Mock\MockData as M;

$customers = collect(M::get('customers'))->keyBy('id');
$leads = collect(M::get('leads'))->keyBy('id');

$auditHtml = fn (string $biz, string $variant) => <<<HTML
<h2>Local search audit - {$biz}</h2>
<p>This report summarises how <strong>{$biz}</strong> currently appears in local search results and the quickest wins we recommend. <em>Sample content for the prototype.</em></p>
<h3>Key findings</h3>
<ul>
<li>Google Business Profile is <strong>partly complete</strong> (opening hours and services missing).</li>
<li>Site loads in 4.1s on mobile; target is under 2.5s.</li>
<li>Only 12 consistent local citations found; competitors average 38.</li>
</ul>
<h3>Priority actions</h3>
<table><thead><tr><th>#</th><th>Action</th><th>Impact</th><th>Effort</th></tr></thead><tbody>
<tr><td>1</td><td>Complete Business Profile and add services</td><td>High</td><td>Low</td></tr>
<tr><td>2</td><td>Compress hero images, defer scripts</td><td>Medium</td><td>Medium</td></tr>
<tr><td>3</td><td>Build 25 additional local citations</td><td>Medium</td><td>Medium</td></tr>
</tbody></table>
<p>{$variant}</p>
HTML;

$quoteHtml = fn (string $biz) => <<<HTML
<h2>Proposal and quotation - {$biz}</h2>
<p>Thank you for the opportunity. Below is the scope we recommend. <em>All figures are synthetic sample data.</em></p>
<ol><li>Discovery and technical review</li><li>Delivery per the agreed scope</li><li>Monthly reporting and a quarterly review call</li></ol>
<table><thead><tr><th>Item</th><th>Type</th><th>Sample price</th></tr></thead><tbody>
<tr><td>Onboarding and setup</td><td>One-time</td><td>$800</td></tr>
<tr><td>Monthly service</td><td>Recurring</td><td>$950 / month</td></tr>
</tbody></table>
HTML;

$internal = fn (string $text) => "<p>{$text}</p>";

$ver = fn (int $no, string $status, string $author, int $daysAgo, string $html, string $int, string $note) => [
    'no' => $no, 'status' => $status, 'author' => $author, 'at' => M::dt($daysAgo, '15:30'),
    'html' => $html, 'internal_html' => $int, 'note' => $note,
];

$req = function (string $id, string $lead, string $type, string $by, string $auditor, string $status, string $priority, int $createdAgo, int $dueIn, string $notes, array $versions, array $sends = [], array $files = []) use ($leads, $customers) {
    return [
        'id' => $id, 'lead_id' => $lead, 'customer_id' => $leads[$lead]['customer_id'], 'project_id' => $leads[$lead]['project_id'],
        'type' => $type, 'requested_by' => $by, 'assigned_to' => $auditor, 'status' => $status, 'priority' => $priority,
        'created_at' => M::dt(-$createdAgo, '09:40'), 'due_date' => M::d($dueIn), 'notes' => $notes,
        'links' => [$customers[$leads[$lead]['customer_id']]['website']],
        'files' => $files, 'versions' => $versions, 'sends' => $sends,
        'timeline' => array_values(array_filter([
            ['at' => M::dt(-$createdAgo, '09:40'), 'by' => $by, 'text' => 'Request created ('.$type.')'],
            $auditor ? ['at' => M::dt(-$createdAgo, '11:00'), 'by' => 'U-01', 'text' => 'Assigned to '.collect(M::get('users'))->firstWhere('id', $auditor)['name']] : null,
        ])),
    ];
};

return [
    $req('A-2001', 'L-1006', 'audit', 'U-11', 'U-04', 'sent', 'normal', 14, -6, 'Please include a competitor comparison for the Cedar Falls area.', [
        $ver(1, 'revision_requested', 'U-04', -10, $auditHtml('Cedar Ridge Realty', 'Draft v1.'), $internal('Competitor data scraped manually - double-check before sending.'), 'First submission'),
        $ver(2, 'sent', 'U-04', -7, $auditHtml('Cedar Ridge Realty', 'Revised to add the competitor comparison requested.'), $internal('Ready. Sent after Team Lead approval.'), 'Added competitor comparison'),
    ], [[
        'to' => ['paula@cedarridgerealty.example'], 'cc' => ['sofia.alvarez@callcentre.example'], 'subject' => 'Your local search audit - Cedar Ridge Realty',
        'message' => 'Hi Paula, please find your audit summary attached. Happy to walk through it on a call.', 'version' => 2, 'at' => M::dt(-7, '16:10'), 'by' => 'U-11',
        'result' => 'Demo simulation - no email was actually sent',
    ]], [['name' => 'competitor-screenshots.zip', 'size' => '3.2 MB', 'by' => 'U-04', 'at' => M::dt(-10, '14:00')]]),
    $req('A-2002', 'L-1006', 'quotation', 'U-11', 'U-04', 'submitted', 'normal', 6, 1, 'Quote for Local SEO + monthly content package.', [
        $ver(1, 'submitted', 'U-04', -1, $quoteHtml('Cedar Ridge Realty'), $internal('Margin is thin on the content package; Team Lead should not discount further.'), 'Initial quote'),
    ]),
    $req('A-2003', 'L-1006', 'guidance', 'U-11', 'U-04', 'assigned', 'high', 0, 3, 'Customer asks whether they should move to a new domain before the SEO work starts. Need guidance for the call.', []),
    $req('A-2004', 'L-1005', 'audit', 'U-12', 'U-04', 'in_progress', 'normal', 5, 2, 'Studio has two locations - audit both Business Profiles.', [
        $ver(1, 'draft', 'U-04', -1, $auditHtml('Lumen Yoga Studio', 'Work in progress.'), $internal('Still need location 2 data.'), 'Work in progress'),
    ]),
    $req('A-2005', 'L-1007', 'quotation', 'U-12', 'U-05', 'revision_requested', 'high', 9, -1, 'Quote for a 12-page rebuild including e-commerce.', [
        $ver(1, 'revision_requested', 'U-05', -3, $quoteHtml('Pixel Forge Print Co.'), $internal('Customer wants e-commerce included - rework pricing.'), 'First quote'),
    ]),
    $req('A-2006', 'L-1008', 'audit', 'U-10', 'U-04', 'assigned', 'normal', 2, 4, 'Grocery store with delivery radius - include service-area pages.', []),
    $req('A-2007', 'L-1001', 'audit', 'U-10', 'U-04', 'sent', 'normal', 58, -50, 'Initial audit before proposal.', [
        $ver(1, 'sent', 'U-04', -54, $auditHtml('Brightside Dental Group', 'Final.'), $internal('None.'), 'Approved by Team Lead'),
    ], [[
        'to' => ['helen@brightsidedental.example'], 'cc' => [], 'subject' => 'Your local search audit - Brightside Dental Group', 'message' => 'Hi Helen, here is your audit.', 'version' => 1, 'at' => M::dt(-54, '17:00'), 'by' => 'U-10',
        'result' => 'Demo simulation - no email was actually sent',
    ]]),
    $req('A-2008', 'L-1009', 'quotation', 'U-11', 'U-04', 'approved', 'normal', 8, 0, 'Management fee quote for Google + Meta ads.', [
        $ver(1, 'approved', 'U-04', -2, $quoteHtml('Summit Auto Care'), $internal('Approved by Marcus.'), 'Approved'),
    ]),
    $req('A-2009', 'L-1010', 'quotation', 'U-12', 'U-05', 'requested', 'normal', 0, 5, 'Quote for CRM + invoicing automation.', []),
    $req('A-2010', 'L-1017', 'audit', 'U-14', 'U-04', 'in_progress', 'normal', 4, 2, 'Roofing - focus on service-area pages and review profile.', [
        $ver(1, 'draft', 'U-04', -1, $auditHtml('Copperline Roofing', 'Work in progress.'), $internal('Needs competitor data.'), 'Work in progress'),
    ]),
];
