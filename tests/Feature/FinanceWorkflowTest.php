<?php

namespace Tests\Feature;

use App\Mock\Repositories\FinanceRepository;
use Carbon\Carbon;
use Tests\TestCase;

class FinanceWorkflowTest extends TestCase
{
    public function test_commission_eligibility_is_closed_won_plus_configured_days(): void
    {
        $this->asRole('finance')->get('/commissions')->assertOk();
        $c = app(FinanceRepository::class)->commission('CM-5008');
        $this->assertSame(
            Carbon::parse($c->closed_won_at)->addDays(15)->toDateString(),
            $c->eligibility_date,
        );

    }

    public function test_commission_lifecycle_and_paid_records_are_never_edited(): void
    {
        $this->asRole('finance')->post('/commissions/CM-5006/transition', ['to' => 'approved'])->assertRedirect();
        $this->post('/commissions/CM-5006/transition', ['to' => 'due'])->assertRedirect();
        $this->post('/commissions/CM-5006/transition', ['to' => 'paid', 'payout_ref' => 'PAYOUT-TEST-1'])->assertRedirect();
        $this->get('/commissions/CM-5006')->assertSee('PAYOUT-TEST-1');

        $this->post('/commissions/CM-5006/transition', ['to' => 'approved'])->assertSessionHasErrors('to');   // cannot move a paid commission back
        $this->post('/commissions/CM-5006/adjust', ['delta' => -5, 'reason' => 'Correction after audit'])->assertRedirect();
        $this->get('/commissions/CM-5006')->assertSee('Correction after audit');
        $this->post('/commissions/CM-5006/transition', ['to' => 'reversed'])->assertSessionHasErrors('reason');
    }

    public function test_hold_and_reverse_need_a_reason(): void
    {
        $this->asRole('finance')->post('/commissions/CM-5008/transition', ['to' => 'on_hold'])->assertSessionHasErrors('reason');
        $this->post('/commissions/CM-5008/transition', ['to' => 'on_hold', 'reason' => 'Awaiting signature check'])->assertRedirect();
    }

    public function test_agreement_signing_flow_is_simulated_end_to_end(): void
    {
        $this->asRole('finance')->post('/agreements', [
            'lead_id' => 'L-1004', 'lines' => [['service_id' => 'S-ADS-MGMT', 'price' => 900, 'billing' => 'recurring', 'qty' => 1]], 'term_months' => 12, 'currency' => 'USD',
            'clause_ids' => ['CL-PAY', 'CL-ADS'], 'signer_name' => 'Dr. Helen Brooks', 'signer_email' => 'helen@brightsidedental.example',
        ])->assertRedirect();
        $id = 'AG-3101';
        $this->get("/agreements/$id")->assertOk()->assertSee('Draft');
        $this->post("/agreements/$id/status", ['to' => 'ready_to_send'])->assertRedirect();
        $this->post("/agreements/$id/status", ['to' => 'sent'])->assertRedirect();
        $this->post("/sign/$id", ['name' => 'Dr. Helen Brooks', 'method' => 'typed'])->assertSessionHasErrors('consent');
        $this->post("/sign/$id", ['name' => 'Dr. Helen Brooks', 'method' => 'typed', 'consent' => '1'])->assertRedirect();
        $this->get("/agreements/$id")->assertSee('Partially Signed');
        $this->post("/agreements/$id/status", ['to' => 'countersign'])->assertRedirect();
        $this->get("/agreements/$id")->assertSee('Final PDF');
    }

    public function test_offline_signed_copy_must_be_reviewed_before_the_agreement_is_signed(): void
    {
        $this->asRole('finance')->post('/agreements/AG-3003/offline', ['file_name' => 'signed.pdf'])->assertRedirect();
        $this->post('/agreements/AG-3003/offline-review', ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->post('/agreements/AG-3003/offline-review', ['decision' => 'approve'])->assertRedirect();
        $this->get('/agreements/AG-3003')->assertSee('Accepted');
    }

    public function test_subscription_cannot_be_set_up_before_signature_and_square_mapping(): void
    {
        $this->asRole('finance')->get('/finance/billing')->assertOk()->assertSee('Agreement is not fully signed yet.');
    }

    public function test_closed_lost_after_signature_keeps_agreement_history_and_holds_unpaid_commissions(): void
    {
        $this->asRole('team_lead')->post('/leads/L-1003/status', ['to' => 'closed_lost', 'reason' => 'Chose a competitor', 'notes' => 'Cancelled after signing'])->assertRedirect();
        $this->asRole('finance')->get('/agreements/AG-3003')->assertOk()->assertSee('agreement history retained', false);
        $this->get('/commissions/CM-5008')->assertSee('On Hold');
    }
}
