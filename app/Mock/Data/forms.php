<?php

use App\Mock\MockData as M;

$f = fn (string $key, string $label, string $type, array $o = []) => array_merge([
    'key' => $key, 'label' => $label, 'type' => $type, 'help' => null, 'required' => false,
    'options' => [], 'min' => null, 'max' => null, 'active' => true, 'default' => null, 'condition' => null,
], $o);

$seo = [
    ['key' => 'context', 'title' => 'Business context', 'fields' => [
        $f('q_current_site', 'Current website URL', 'short_answer', ['required' => true, 'help' => 'The site customers use today, if any.']),
        $f('q_monthly_leads', 'Approximate leads per month', 'number', ['min' => 0, 'max' => 5000, 'help' => 'Calls + forms + walk-ins, best estimate.']),
        $f('q_service_area', 'Service area', 'dropdown', ['required' => true, 'options' => ['Single location', 'Multi-location (2-5)', 'Regional', 'National']]),
    ]],
    ['key' => 'goals', 'title' => 'Goals', 'fields' => [
        $f('q_primary_goal', 'Primary goal', 'radio', ['required' => true, 'options' => ['More phone calls', 'More form leads', 'More walk-in traffic', 'Brand awareness']]),
        $f('q_services_wanted', 'Services of interest', 'checkbox', ['options' => ['Local SEO', 'Google Business Profile', 'Content', 'Link building', 'Review management']]),
        $f('q_budget_share', 'Share of marketing budget for SEO', 'percentage', ['min' => 0, 'max' => 100, 'default' => 20]),
        $f('q_competitor', 'Main local competitor', 'short_answer', ['condition' => ['key' => 'q_primary_goal', 'equals' => 'More phone calls']]),
        $f('q_notes', 'Anything else we should know?', 'text', ['help' => 'Free text - objections, timing, preferences.']),
    ]],
];

$seoV4 = $seo;
$seoV4[1]['fields'][] = $f('q_decision_maker', 'Is the contact the decision maker?', 'radio', ['options' => ['Yes', 'No', 'Shared decision']]);

$web = [
    ['key' => 'scope', 'title' => 'Project scope', 'fields' => [
        $f('q_pages', 'Estimated number of pages', 'number', ['required' => true, 'min' => 1, 'max' => 500]),
        $f('q_platform', 'Preferred platform', 'dropdown', ['options' => ['WordPress', 'Shopify', 'Custom build', 'Not sure']]),
        $f('q_features', 'Needed features', 'checkbox', ['options' => ['Online booking', 'E-commerce', 'Blog', 'Multilingual', 'Membership area']]),
        $f('q_branding', 'Existing branding assets?', 'radio', ['options' => ['Yes - complete', 'Partial', 'No']]),
        $f('q_deadline', 'Target launch date', 'short_answer'),
    ]],
];

$ads = [
    ['key' => 'ads', 'title' => 'Advertising', 'fields' => [
        $f('q_ad_spend', 'Planned monthly media spend (sample currency)', 'number', ['required' => true, 'min' => 0]),
        $f('q_channels', 'Channels of interest', 'checkbox', ['options' => ['Google Ads', 'Meta Ads', 'LinkedIn Ads', 'TikTok Ads']]),
        $f('q_conversion_goal', 'Conversion goal', 'radio', ['options' => ['Calls', 'Form submissions', 'Online sales', 'App installs']]),
        $f('q_roas_target', 'Target return on ad spend uplift', 'percentage', ['min' => 0, 'max' => 500, 'help' => 'Percentage, 0-500.']),
    ]],
];

$aut = [
    ['key' => 'process', 'title' => 'Processes', 'fields' => [
        $f('q_tools', 'Tools currently used', 'checkbox', ['options' => ['CRM', 'Email marketing', 'Accounting software', 'Help desk']]),
        $f('q_manual_hours', 'Manual admin hours per week', 'number', ['required' => true, 'min' => 0, 'max' => 168]),
        $f('q_urgency', 'Urgency', 'dropdown', ['options' => ['This month', 'This quarter', 'No rush']]),
        $f('q_process', 'Describe the process to automate', 'text', ['required' => true]),
    ]],
];

$v = fn ($formNo, $no, $status, $sections, $note, $daysAgo) => [
    'id' => "FV-$formNo-$no", 'version' => $no, 'status' => $status, 'sections' => $sections,
    'change_note' => $note, 'published_at' => $status === 'published' || $status === 'archived' ? M::dt(-$daysAgo, '11:00') : null,
    'published_by' => 'U-01', 'updated_at' => M::dt(-$daysAgo, '11:00'),
];

return [
    ['id' => 'FORM-1', 'name' => 'Local SEO Growth - lead intake', 'project_id' => 'P-SEO', 'scope' => 'Project', 'versions' => [
        $v(1, 4, 'draft', $seoV4, 'Adds "decision maker" question (not yet published).', 1),
        $v(1, 3, 'published', $seo, 'Added conditional competitor question and budget share.', 21),
        $v(1, 2, 'archived', array_slice($seo, 0, 1), 'Split into sections.', 70),
        $v(1, 1, 'archived', array_slice($seo, 0, 1), 'Initial version.', 120),
    ]],
    ['id' => 'FORM-2', 'name' => 'Website Development - lead intake', 'project_id' => 'P-WEB', 'scope' => 'Project', 'versions' => [
        $v(2, 2, 'published', $web, 'Added branding question.', 40),
        $v(2, 1, 'archived', $web, 'Initial version.', 90),
    ]],
    ['id' => 'FORM-3', 'name' => 'Paid Ads - lead intake', 'project_id' => 'P-ADS', 'scope' => 'Project', 'versions' => [
        $v(3, 1, 'published', $ads, 'Initial version.', 45),
    ]],
    ['id' => 'FORM-4', 'name' => 'Automation - lead intake', 'project_id' => 'P-AUT', 'scope' => 'Project', 'versions' => [
        $v(4, 1, 'published', $aut, 'Initial version.', 33),
    ]],
];
