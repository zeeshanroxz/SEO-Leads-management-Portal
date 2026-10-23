<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Finance domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface FinanceRepositoryContract
{
    public function square(): Record;

    public function updateSquare(array $fields): void;

    public function squareCustomers(): Collection;

    public function mapSquareCustomer(string $id, string $mode): void;

    public function subscriptions(): Collection;

    public function activateSubscription(string $id): void;

    public function payments(): Collection;

    public function webhooks(): Collection;

    public function retryWebhook(string $id): void;

    public function simulateWebhook(string $type): Record;

    public function reconciliation(): Collection;

    public function resolveReconciliation(string $id, string $note): void;

    public function rules(): Collection;

    public function rule(?string $id): ?Record;

    public function updateRule(string $id, array $fields): void;

    public function commissions(): Collection;

    public function commissionsScoped(): Collection;

    public function commission(?string $id): ?Record;

    public function createCommission(array $d): Record;

    public function transition(string $id, string $to, ?string $reason = null, ?string $payoutRef = null): void;

    public function adjust(string $id, float $delta, string $reason): void;

    public function applyReversalPolicy(string $leadId, string $reason): int;

    public function recalculateEligibility(): int;

    public function cashSeries(): array;

    public function totals(): array;
}
