<?php

use App\Mock\MockData as M;

// Fictional staff. Reporting line: Admin -> Manager -> Team Lead -> Agent.
$u = fn (string $id, string $name, string $role, array $extra = []) => array_merge([
    'id' => $id,
    'name' => $name,
    'email' => strtolower(str_replace([' ', 'á', 'ó'], ['.', 'a', 'o'], $name)).'@callcentre.example',
    'role' => $role,
    'status' => 'active',
    'last_login' => M::dt(0, '08:12'),
    'team_id' => null,
    'manager_id' => null,
    'team_lead_id' => null,
    'projects' => [],
    'phone' => '+1 555 0'.str_pad((string) (100 + (int) substr($id, 2)), 3, '0', STR_PAD_LEFT),
    'two_factor' => $role === 'admin' || $role === 'finance',
], $extra);

return [
    $u('U-01', 'Jordan Hale', 'admin', ['title' => 'Operations Director']),
    $u('U-02', 'Fiona Reyes', 'finance', ['title' => 'Finance Officer', 'permission_sets' => ['Finance', 'Finance Manager']]),
    $u('U-03', 'Kai Brandt', 'finance', ['title' => 'Billing Specialist', 'permission_sets' => ['Finance'], 'last_login' => M::dt(-1, '16:40')]),
    $u('U-04', 'Priya Menon', 'auditor', ['title' => 'SEO Auditor', 'projects' => ['P-SEO', 'P-ADS']]),
    $u('U-05', 'Tomas Rivera', 'auditor', ['title' => 'Web & Automation Auditor', 'projects' => ['P-WEB', 'P-AUT'], 'last_login' => M::dt(-1, '10:05')]),
    $u('U-06', 'Marcus Webb', 'manager', ['title' => 'Sales Manager', 'team_id' => 'T-1']),
    $u('U-07', 'Lena Fischer', 'manager', ['title' => 'Sales Manager', 'team_id' => 'T-2', 'last_login' => M::dt(-2, '09:30')]),
    $u('U-08', 'Sofia Alvarez', 'team_lead', ['title' => 'Team Lead / Closer', 'team_id' => 'T-1', 'manager_id' => 'U-06']),
    $u('U-09', 'Daniel Park', 'team_lead', ['title' => 'Team Lead / Closer', 'team_id' => 'T-2', 'manager_id' => 'U-07']),
    $u('U-10', 'Amir Khan', 'agent', ['title' => 'Sales Agent', 'team_id' => 'T-1', 'manager_id' => 'U-06', 'team_lead_id' => 'U-08']),
    $u('U-11', 'Chloe Bennett', 'agent', ['title' => 'Sales Agent', 'team_id' => 'T-1', 'manager_id' => 'U-06', 'team_lead_id' => 'U-08']),
    $u('U-12', 'Ravi Shah', 'agent', ['title' => 'Sales Agent', 'team_id' => 'T-1', 'manager_id' => 'U-06', 'team_lead_id' => 'U-08']),
    $u('U-13', 'Nina Petrova', 'agent', ['title' => 'Sales Agent', 'team_id' => 'T-2', 'manager_id' => 'U-07', 'team_lead_id' => 'U-09']),
    $u('U-14', 'Oliver Grant', 'agent', ['title' => 'Sales Agent', 'team_id' => 'T-2', 'manager_id' => 'U-07', 'team_lead_id' => 'U-09']),
    $u('U-15', 'Zara Idris', 'agent', ['title' => 'Sales Agent (left company)', 'team_id' => 'T-2', 'manager_id' => 'U-07', 'team_lead_id' => 'U-09', 'status' => 'disabled', 'last_login' => M::dt(-70, '11:00')]),
];
