<?php

use App\Mock\MockData as M;

// Reusable terms. Each agreement snapshots the clause text + version used at creation.
// Wording is placeholder prototype text - NOT legal advice and not approved terms.
$c = fn ($id, $title, $scope, $v, $body) => ['id' => $id, 'title' => $title, 'scope' => $scope, 'version' => $v, 'body' => $body, 'status' => 'active', 'updated_at' => M::dt(-30 - $v * 7), 'updated_by' => 'U-02'];

return [
    $c('CL-PAY', 'Payment terms', 'Standard', 3, 'Fees are invoiced in advance per the billing schedule in this agreement. Payment is due on the invoice due date. [Prototype placeholder text - to be replaced by approved wording.]'),
    $c('CL-TERM', 'Term and renewal', 'Standard', 2, 'This agreement runs for the term stated above and renews only as set out in the renewal rules. [Prototype placeholder text.]'),
    $c('CL-CANCEL', 'Cancellation', 'Standard', 2, 'Either party may cancel by written notice as configured in the cancellation notice period. [Prototype placeholder text.]'),
    $c('CL-DATA', 'Data and confidentiality', 'Standard', 1, 'Each party keeps the other\'s confidential information confidential and handles personal data under applicable law. [Prototype placeholder text.]'),
    $c('CL-SEO', 'SEO outcomes disclaimer', 'Service: SEO', 2, 'Search rankings depend on factors outside the provider\'s control and no specific ranking is guaranteed. [Prototype placeholder text.]'),
    $c('CL-WEB', 'Website ownership and handover', 'Service: Web', 1, 'Ownership of delivered website assets transfers on full payment as described. [Prototype placeholder text.]'),
    $c('CL-ADS', 'Advertising spend responsibility', 'Service: Ads', 1, 'Media spend is paid directly by the customer and is separate from management fees. [Prototype placeholder text.]'),
    $c('CL-AUTO', 'Automation scope and change requests', 'Service: Automation', 1, 'Scope is limited to the workflows listed; changes follow the change-request process. [Prototype placeholder text.]'),
];
