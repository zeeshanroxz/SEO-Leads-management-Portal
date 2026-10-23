<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phase 1 UI-simulation checks: what each previewed role can open.
 * (Real authorisation is a Phase 2 server-side concern.)
 */
class RoleAccessTest extends TestCase
{
    public function test_every_screen_renders_for_admin(): void
    {
        $paths = ['/dashboard', '/leads', '/leads/create', '/leads/L-1001', '/review-queue', '/customers', '/customers/C-001', '/admin/users', '/admin/forms', '/admin/forms/FORM-1',
            '/admin/projects', '/admin/visibility', '/admin/activity', '/admin/settings', '/audits', '/audits/A-2001', '/audits/A-2001/preview', '/calendar',
            '/settings/calendar-integrations', '/learning', '/finance/handoff', '/finance/dashboard', '/agreements', '/agreements/AG-3001', '/agreements/create',
            '/finance/billing', '/integrations/square', '/commissions', '/commissions/CM-5001', '/commissions-rules', '/revenue', '/notifications', '/notifications/settings'];

        foreach ($paths as $path) {
            $this->asRole('admin')->get($path)->assertOk();
        }
    }

    public function test_login_and_public_signing_pages_render_without_a_role(): void
    {
        $this->get('/login')->assertOk()->assertSee('Login mockup');
        $this->get('/sign/AG-3003')->assertOk()->assertSee('DEMO CUSTOMER SIGNING PAGE');
    }

    public function test_agent_cannot_open_finance_or_admin_screens(): void
    {
        foreach (['/agreements', '/agreements/AG-3001', '/finance/dashboard', '/finance/billing', '/admin/users', '/admin/forms', '/review-queue', '/revenue', '/integrations/square'] as $path) {
            $this->asRole('agent')->get($path)->assertForbidden();
        }
    }

    public function test_manager_sees_mrr_but_never_agreement_content(): void
    {
        $this->asRole('manager')->get('/revenue')->assertOk()->assertSee('Fully signed agreements only');
        $this->asRole('manager')->get('/agreements/AG-3001')->assertForbidden();
        $this->asRole('manager')->get('/finance/dashboard')->assertForbidden();
    }

    public function test_auditor_is_limited_to_assigned_requests(): void
    {
        $this->asRole('auditor')->get('/audits/A-2004')->assertOk();          // assigned to Priya
        $this->asRole('auditor')->get('/audits/A-2005')->assertForbidden();   // assigned to Tomas
        $this->asRole('auditor')->get('/commissions')->assertForbidden();
    }

    public function test_agent_only_sees_own_leads_and_hides_restricted_upsell_details(): void
    {
        $this->asRole('agent')->get('/leads/L-1008')->assertOk();
        $this->asRole('agent')->get('/leads/L-1005')->assertForbidden();      // another agent's lead
        $this->asRole('agent')->get('/leads/L-1004')->assertOk()->assertSee('Services &amp; upsell details are restricted', false);
        $this->asRole('agent')->get('/leads/L-1001')->assertDontSee('Agreement &amp; billing', false);
    }

    public function test_finance_cannot_see_call_notes_unless_granted(): void
    {
        $this->asRole('finance')->get('/leads/L-1001')->assertOk()->assertDontSee('Activity timeline');
        $this->asRole('finance')->get('/leads/L-1022')->assertOk()->assertSee('Activity timeline');   // Admin granted notes on L-1022
    }

    public function test_finance_manager_flag_controls_manager_commission_actions(): void
    {
        $this->asRole('finance')->withSession(['demo_finance_manager' => false])->get('/commissions')->assertOk()->assertSee('Finance Manager');
        $this->asRole('finance')->withSession(['demo_finance_manager' => false])->post('/commissions/CM-5007/transition', ['to' => 'approved'])->assertForbidden();
        $this->asRole('finance')->withSession(['demo_finance_manager' => true])->post('/commissions/CM-5007/transition', ['to' => 'approved'])->assertRedirect();
    }
}
