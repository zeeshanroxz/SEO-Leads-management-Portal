<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Agreement domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface AgreementRepositoryContract
{
    public function all(): Collection;

    public function find(?string $id): ?Record;

    public function forLead(string $leadId): Collection;

    public function forCustomer(string $customerId): Collection;

    public function clauses(): Collection;

    public function clause(?string $id): ?Record;

    public function signedRecurring(): Collection;

    public function create(array $d): Record;

    public function setStatus(string $id, string $status, string $eventText): void;

    public function addEvent(string $id, string $text, string $by = 'System'): void;

    public function simulateCustomerSignature(string $id, string $name, string $method): void;

    public function countersign(string $id): void;

    public function recordOfflineUpload(string $id, string $filename): void;

    public function reviewOffline(string $id, bool $approve, ?string $note): void;
}
