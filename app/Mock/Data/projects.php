<?php

return [
    [
        'id' => 'P-SEO', 'code' => 'SEO', 'name' => 'Local SEO Growth', 'status' => 'active',
        'description' => 'Local search visibility for service businesses.',
        'audit_policy' => 'required', 'form_id' => 'FORM-1', 'auditor_ids' => ['U-04'],
        'service_ids' => ['S-SEO-LOCAL', 'S-SEO-CONTENT', 'S-SEO-SETUP'],
        'status_set' => 'Default pipeline',
    ],
    [
        'id' => 'P-WEB', 'code' => 'WEB', 'name' => 'Website Development', 'status' => 'active',
        'description' => 'New builds, redesigns and care plans.',
        'audit_policy' => 'optional', 'form_id' => 'FORM-2', 'auditor_ids' => ['U-05'],
        'service_ids' => ['S-WEB-BUILD', 'S-WEB-CARE'],
        'status_set' => 'Default pipeline',
    ],
    [
        'id' => 'P-ADS', 'code' => 'ADS', 'name' => 'Paid Ads & Digital Marketing', 'status' => 'active',
        'description' => 'Search and social advertising management.',
        'audit_policy' => 'optional', 'form_id' => 'FORM-3', 'auditor_ids' => ['U-04'],
        'service_ids' => ['S-ADS-MGMT'],
        'status_set' => 'Default pipeline',
    ],
    [
        'id' => 'P-AUT', 'code' => 'AUT', 'name' => 'Business Automation', 'status' => 'active',
        'description' => 'Workflow automation and integrations.',
        'audit_policy' => 'not_applicable', 'form_id' => 'FORM-4', 'auditor_ids' => ['U-05'],
        'service_ids' => ['S-AUT-FLOW'],
        'status_set' => 'Default pipeline',
    ],
];
