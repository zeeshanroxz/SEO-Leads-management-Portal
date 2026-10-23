<?php

// Sample list prices are synthetic and only used to pre-fill the agreement builder.
return [
    ['id' => 'S-SEO-LOCAL', 'name' => 'Local SEO Retainer', 'project_id' => 'P-SEO', 'billing' => 'recurring', 'sample_price' => 1200, 'clause_ids' => ['CL-PAY', 'CL-TERM', 'CL-CANCEL', 'CL-DATA', 'CL-SEO']],
    ['id' => 'S-SEO-CONTENT', 'name' => 'Content Package', 'project_id' => 'P-SEO', 'billing' => 'recurring', 'sample_price' => 450, 'clause_ids' => ['CL-PAY', 'CL-TERM', 'CL-DATA']],
    ['id' => 'S-SEO-SETUP', 'name' => 'SEO Onboarding & Setup', 'project_id' => 'P-SEO', 'billing' => 'one_time', 'sample_price' => 800, 'clause_ids' => ['CL-PAY', 'CL-DATA']],
    ['id' => 'S-WEB-BUILD', 'name' => 'Website Build', 'project_id' => 'P-WEB', 'billing' => 'one_time', 'sample_price' => 4500, 'clause_ids' => ['CL-PAY', 'CL-WEB', 'CL-DATA']],
    ['id' => 'S-WEB-CARE', 'name' => 'Website Care Plan', 'project_id' => 'P-WEB', 'billing' => 'recurring', 'sample_price' => 90, 'clause_ids' => ['CL-PAY', 'CL-TERM', 'CL-CANCEL']],
    ['id' => 'S-ADS-MGMT', 'name' => 'Ad Management', 'project_id' => 'P-ADS', 'billing' => 'recurring', 'sample_price' => 900, 'clause_ids' => ['CL-PAY', 'CL-TERM', 'CL-CANCEL', 'CL-ADS', 'CL-DATA']],
    ['id' => 'S-AUT-FLOW', 'name' => 'Workflow Automation Package', 'project_id' => 'P-AUT', 'billing' => 'one_time', 'sample_price' => 3200, 'clause_ids' => ['CL-PAY', 'CL-AUTO', 'CL-DATA']],
];
