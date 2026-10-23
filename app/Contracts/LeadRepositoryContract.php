<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Lead domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface LeadRepositoryContract
{
    public function all(): Collection;

    public function scoped(): Collection;

    public function find(?string $id): ?Record;

    public function findScoped(?string $id): ?Record;

    public function forCustomer(string $customerId): Collection;

    public function customers(): Collection;

    public function customersScoped(): Collection;

    public function customer(?string $id): ?Record;

    public function reviewQueue(): Collection;

    public function teamLeadsMayReviewTeam(): bool;

    public function canReview(Record $lead): bool;

    public function closedWon(): Collection;

    public function duplicates(array $input, ?string $ignoreCustomerId = null): array;

    public function createLead(array $data, bool $submit, bool $duplicateOverride = false, ?string $overrideReason = null): Record;

    public function updateLead(string $id, array $data, array $groups): void;

    public function reopenFields(string $id, array $groups, string $reason): void;

    public function editableGroups(Record $lead): array;

    public function accept(string $id): void;

    public function reject(string $id, string $reason, string $notes): void;

    public function submitDraft(string $id): void;

    public function changeSalesStatus(string $id, string $to, ?string $reason, ?string $notes): void;

    public function addActivity(string $id, string $type, string $title, ?string $body, ?string $outcome = null, ?array $next = null): void;

    public function addContact(string $customerId, array $contact): void;
}
