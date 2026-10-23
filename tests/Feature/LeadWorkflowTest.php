<?php

namespace Tests\Feature;

use Tests\TestCase;

class LeadWorkflowTest extends TestCase
{
    public function test_duplicate_check_matches_on_normalised_email_phone_domain_and_name(): void
    {
        $r = $this->asRole('admin')->postJson('/leads/duplicates', ['business_email' => '  INFO@BrightsideDental.example ']);
        $r->assertOk()->assertJsonPath('blocked', true)->assertJsonPath('matches.0.matched_on.0', 'Business email');

        $this->asRole('admin')->postJson('/leads/duplicates', ['business_phone' => '(+1) 555-01 56'])->assertOk();
        $this->asRole('admin')->postJson('/leads/duplicates', ['website' => 'https://www.harborpine.example/about'])->assertJsonPath('matches.0.matched_on.0', 'Website domain');
        $this->asRole('admin')->postJson('/leads/duplicates', ['business_name' => 'Pixel Forge Print Co'])->assertJsonPath('blocked', true);
        $this->asRole('admin')->postJson('/leads/duplicates', ['business_name' => 'Totally New Business', 'business_email' => 'new@totallynew.example'])->assertJsonPath('blocked', false);
    }

    public function test_duplicate_details_are_hidden_when_the_record_is_outside_the_users_scope(): void
    {
        $r = $this->asRole('agent')->postJson('/leads/duplicates', ['business_email' => 'info@maplecobakery.example']); // owned by the same agent -> visible
        $r->assertJsonPath('matches.0.visible', true);
        $r = $this->asRole('agent')->postJson('/leads/duplicates', ['business_email' => 'info@ironcladfitness.example']); // another agent's lead
        $r->assertJsonPath('matches.0.visible', false)->assertJsonPath('matches.0.details', null);
    }

    public function test_rejection_requires_reason_and_note_and_is_visible_afterwards(): void
    {
        $this->asRole('admin')->post('/leads/L-1011/reject', [])->assertSessionHasErrors(['reason', 'notes']);
        $this->asRole('admin')->post('/leads/L-1011/reject', ['reason' => 'Other', 'notes' => 'short'])->assertSessionHasErrors('notes');

        $this->asRole('admin')->post('/leads/L-1011/reject', ['reason' => 'Outside service area', 'notes' => 'Not in a region we serve.'])->assertRedirect();
        $this->get('/leads/L-1011')->assertSee('Rejected: Outside service area', false)->assertSee('Not in a region we serve.');
    }

    public function test_agent_cannot_accept_or_reject(): void
    {
        $this->asRole('agent')->post('/leads/L-1011/accept')->assertForbidden();
        $this->asRole('agent')->post('/leads/L-1011/reject', ['reason' => 'Other', 'notes' => 'Trying to reject my own lead'])->assertForbidden();
    }

    public function test_team_lead_can_accept_their_own_lead_but_not_an_agents_by_default(): void
    {
        $this->asRole('team_lead')->post('/leads/L-1025/accept')->assertRedirect();      // created by Sofia herself
        $this->asRole('team_lead')->post('/leads/L-1011/accept')->assertForbidden();     // Amir's lead
    }

    public function test_accepted_lead_is_locked_for_agent_but_notes_are_allowed(): void
    {
        $this->asRole('agent')->put('/leads/L-1008', ['project_id' => 'P-SEO', 'business_name' => 'Hacked'])->assertForbidden();
        $this->asRole('agent')->post('/leads/L-1008/activity', ['type' => 'note', 'title' => 'Called back'])->assertRedirect();
        $this->get('/leads/L-1008')->assertSee('Called back');
    }

    public function test_terminal_statuses_require_reasons(): void
    {
        $this->asRole('team_lead')->post('/leads/L-1008/status', ['to' => 'not_interested'])->assertSessionHasErrors('reason');
        $this->asRole('team_lead')->post('/leads/L-1008/status', ['to' => 'closed_lost', 'reason' => 'Budget'])->assertSessionHasErrors('notes');
        $this->asRole('team_lead')->post('/leads/L-1008/status', ['to' => 'closed_lost', 'reason' => 'Budget', 'notes' => 'No budget this year'])->assertSessionHasNoErrors();
    }

    public function test_agent_submission_validates_required_custom_questions_and_creates_a_pending_lead(): void
    {
        $base = ['project_id' => 'P-SEO', 'action' => 'submit', 'business_name' => 'Unit Test Cafe', 'business_email' => 'hello@unittestcafe.example', 'business_phone' => '+1 555 0188', 'business_address' => '2 Test Lane', 'contacts' => [['name' => 'Tess', 'designation' => 'Owner']]];
        $this->asRole('agent')->post('/leads', $base)->assertSessionHasErrors(['answers.q_current_site', 'answers.q_service_area', 'answers.q_primary_goal']);

        $ok = $base + ['answers' => ['q_current_site' => 'https://unittestcafe.example', 'q_service_area' => 'Regional', 'q_primary_goal' => 'More phone calls', 'q_budget_share' => 40]];
        $this->asRole('agent')->post('/leads', $ok)->assertRedirect();
        $this->get('/leads')->assertSee('Unit Test Cafe')->assertSee('Pending Review');
        $this->post('/leads', $ok)->assertSessionHasErrors('duplicate');     // second identical submission is blocked
    }

    public function test_percentage_and_number_questions_are_range_checked(): void
    {
        $payload = ['project_id' => 'P-SEO', 'action' => 'submit', 'business_name' => 'Range Test', 'business_email' => 'r@rangetest.example', 'business_phone' => '+1 555 0166', 'business_address' => 'x', 'contacts' => [['name' => 'A', 'designation' => 'B']],
            'answers' => ['q_current_site' => 'x', 'q_service_area' => 'Regional', 'q_primary_goal' => 'More phone calls', 'q_budget_share' => 140]];
        $this->asRole('agent')->post('/leads', $payload)->assertSessionHasErrors('answers.q_budget_share');
    }
}
