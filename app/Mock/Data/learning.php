<?php

use App\Mock\MockData as M;

$l = fn ($id, $title, $cat, $type, $size, $audience, $days, $v, $desc, $reads) => [
    'id' => $id, 'title' => $title, 'category' => $cat, 'file_type' => $type, 'size' => $size, 'audience' => $audience,
    'published_at' => M::dt($days, '09:00'), 'author_id' => 'U-01', 'version' => $v, 'description' => $desc, 'read_count' => $reads,
    'status' => 'published', 'versions' => array_map(fn ($n) => ['no' => $n, 'at' => M::dt($days - 20 * ($v - $n), '09:00'), 'note' => $n === $v ? 'Current version' : 'Earlier version'], range($v, 1)),
    'notified' => ['in_app' => true, 'email' => true],
];

return [
    $l('LR-1', 'Cold call script - Local SEO', 'Call scripts', 'PDF', '420 KB', ['type' => 'roles', 'labels' => ['Agent', 'Team Lead']], -12, 3, 'Opening, discovery questions and objection handling for Local SEO outreach.', 9),
    $l('LR-2', 'Objection handling playbook', 'Playbooks', 'PDF', '1.1 MB', ['type' => 'all', 'labels' => ['Everyone']], -20, 2, 'Responses to the ten most common objections.', 13),
    $l('LR-3', 'Lead quality and duplicate rules', 'Process guides', 'PDF', '260 KB', ['type' => 'roles', 'labels' => ['Agent', 'Team Lead', 'Manager']], -30, 1, 'What makes a lead acceptable, and how duplicate detection works.', 11),
    $l('LR-4', 'Requesting an audit or quotation', 'Process guides', 'PDF', '310 KB', ['type' => 'roles', 'labels' => ['Agent', 'Team Lead', 'Manager']], -26, 2, 'Step-by-step: when to request, what to include, what to expect.', 8),
    $l('LR-5', 'Auditor report template guide', 'Playbooks', 'DOCX', '180 KB', ['type' => 'roles', 'labels' => ['Auditor of Project']], -40, 1, 'How to structure customer-facing vs internal notes.', 2),
    $l('LR-6', 'Billing and agreements handbook', 'Policy changes', 'PDF', '540 KB', ['type' => 'roles', 'labels' => ['Finance', 'Admin']], -18, 2, 'Agreement workflow, offline signed copies and reconciliation.', 2),
    $l('LR-7', 'Web Development project brief - training video', 'Product training', 'MP4', '84 MB', ['type' => 'projects', 'labels' => ['Website Development']], -8, 1, 'Recorded session on qualifying website projects.', 5),
    $l('LR-8', 'Data protection basics for the floor', 'Policy changes', 'PDF', '230 KB', ['type' => 'all', 'labels' => ['Everyone']], -3, 1, 'What call notes may be recorded and how to handle customer requests. (Prototype placeholder.)', 4),
];
