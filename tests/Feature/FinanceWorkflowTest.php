<?php

namespace Tests\Feature;

use App\Mock\Repositories\AgreementRepository;
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

    private function agreementPayload(array $over = []): array
    {
        return array_merge([
            'lines' => [['service_id' => 'S-ADS-MGMT', 'price' => 900, 'billing' => 'recurring', 'qty' => 1]], 'term_months' => 12, 'currency' => 'USD',
            'clause_ids' => ['CL-PAY', 'CL-ADS'], 'signer_name' => 'Dr. Helen Brooks', 'signer_email' => 'helen@brightsidedental.example',
        ], $over);
    }

    public function test_draft_agreement_can_be_edited_in_place(): void
    {
        $this->asRole('finance')->post('/agreements', $this->agreementPayload(['lead_id' => 'L-1004']))->assertRedirect();
        $id = 'AG-3101';
        $this->get("/agreements/$id")->assertSee("/agreements/$id/edit", false);
        $this->get("/agreements/$id/edit")->assertOk()->assertSee('Save changes');

        $this->put("/agreements/$id", $this->agreementPayload([
            'lines' => [['service_id' => 'S-ADS-MGMT', 'price' => 1200, 'billing' => 'recurring', 'qty' => 2]], 'term_months' => 6, 'signer_email' => 'new@brightsidedental.example', 'change_note' => 'Customer wants a shorter term',
        ]))->assertRedirect(route('agreements.show', $id));

        $a = app(AgreementRepository::class)->find($id);
        $this->assertSame('draft', $a->status);
        $this->assertSame(1, $a->version);                       // edited in place - no new version
        $this->assertSame(6, $a->term_months);
        $this->assertEquals(2400.0, $a->mrr);
        $this->assertSame('new@brightsidedental.example', $a->signers[0]->email);
        $this->get("/agreements/$id")->assertSee('Draft edited (service lines, term, customer signer)', false)->assertSee('Customer wants a shorter term');
        $this->assertCount(1, app(AgreementRepository::class)->forLead('L-1004')->where('status', 'draft'));   // still a single agreement
    }

    public function test_editing_a_ready_to_send_agreement_returns_it_to_draft(): void
    {
        $this->asRole('finance')->post('/agreements', $this->agreementPayload(['lead_id' => 'L-1004']))->assertRedirect();
        $this->post('/agreements/AG-3101/status', ['to' => 'ready_to_send'])->assertRedirect();
        $this->get('/agreements/AG-3101/edit')->assertOk()->assertSee('returns it to');

        $this->put('/agreements/AG-3101', $this->agreementPayload(['payment_schedule' => 'Annually in advance']))->assertRedirect();
        $this->assertSame('draft', app(AgreementRepository::class)->find('AG-3101')->status);
    }

    public function test_clauses_already_in_an_agreement_keep_their_snapshotted_version_when_edited(): void
    {
        $this->asRole('finance')->post('/agreements', $this->agreementPayload(['lead_id' => 'L-1004']))->assertRedirect();
        $this->post('/clauses/CL-PAY', ['body' => 'Brand new payment wording that is long enough.'])->assertRedirect();   // library clause is now v+1

        $before = collect(app(AgreementRepository::class)->find('AG-3101')->clauses)->firstWhere('id', 'CL-PAY');
        $this->put('/agreements/AG-3101', $this->agreementPayload(['term_months' => 24]))->assertRedirect();
        $after = collect(app(AgreementRepository::class)->find('AG-3101')->clauses)->firstWhere('id', 'CL-PAY');

        $this->assertSame($before->version, $after->version);
        $this->assertSame($before->body, $after->body);
    }

    public function test_editing_a_sent_agreement_creates_a_new_version_and_supersedes_the_old_one(): void
    {
        $this->asRole('finance')->get('/agreements/AG-3003/edit')->assertOk()->assertSee('Save as new version')->assertSee('Superseded');
        $this->put('/agreements/AG-3003', $this->agreementPayload([
            'lines' => [['service_id' => 'S-SEO-LOCAL', 'price' => 800, 'billing' => 'recurring', 'qty' => 1]], 'signer_name' => 'Tom Alder', 'signer_email' => 'tom@northwindplumbing.example',
        ]))->assertRedirect(route('agreements.show', 'AG-3101'));

        $repo = app(AgreementRepository::class);
        $this->assertSame('superseded', $repo->find('AG-3003')->status);
        $this->assertSame('AG-3101', $repo->find('AG-3003')->superseded_by);
        $this->assertEquals(950.0, $repo->find('AG-3003')->mrr);           // the old record is never overwritten
        $new = $repo->find('AG-3101');
        $this->assertSame('draft', $new->status);
        $this->assertSame('AG-3003', $new->supersedes);
        $this->assertSame(2, $new->version);
        $this->assertEquals(800.0, $new->mrr);

        $this->get('/agreements/AG-3101')->assertSee('Revision of');
        $this->get('/agreements/AG-3003')->assertSee('superseded by');
        $this->get('/sign/AG-3003')->assertOk()->assertSee('no longer available');   // old signing link is dead
    }

    public function test_signed_and_closed_agreements_cannot_be_edited(): void
    {
        $repo = app(AgreementRepository::class);
        $count = $repo->all()->count();
        $this->asRole('finance')->get('/agreements/AG-3001/edit')->assertRedirect(route('agreements.show', 'AG-3001'))->assertSessionHas('toast.message');
        $this->put('/agreements/AG-3001', $this->agreementPayload(['term_months' => 1]))->assertRedirect(route('agreements.show', 'AG-3001'));
        $this->get('/agreements/AG-3004/edit')->assertRedirect(route('agreements.show', 'AG-3004'));

        $this->assertSame('signed', $repo->find('AG-3001')->status);
        $this->assertSame(12, $repo->find('AG-3001')->term_months);
        $this->assertSame($count, $repo->all()->count());          // no new agreement was created either
        $this->get('/agreements/AG-3001')->assertDontSee('/agreements/AG-3001/edit', false);
    }

    public function test_agreement_edit_is_validated_and_finance_only(): void
    {
        $this->asRole('finance')->put('/agreements/AG-3003', $this->agreementPayload(['lines' => [], 'signer_email' => 'nope']))->assertSessionHasErrors(['lines', 'signer_email']);
        $this->assertSame('viewed', app(AgreementRepository::class)->find('AG-3003')->status);

        foreach (['agent', 'manager', 'auditor'] as $role) {
            $this->asRole($role)->get('/agreements/AG-3003/edit')->assertForbidden();
            $this->asRole($role)->put('/agreements/AG-3003', $this->agreementPayload())->assertForbidden();
        }
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
