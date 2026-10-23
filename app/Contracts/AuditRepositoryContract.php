<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Audit domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface AuditRepositoryContract
{
    public function all(): Collection;

    public function scoped(): Collection;

    public function find(?string $id): ?Record;

    public function findScoped(?string $id): ?Record;

    public function forLead(string $leadId): Collection;

    public function create(string $leadId, string $type, string $notes, string $priority, ?string $due): Record;

    public function saveVersion(string $id, string $html, string $internalHtml, string $note, bool $submit): int;

    public function setStatus(string $id, string $status, ?string $note = null): void;

    public function assign(string $id, string $userId): void;

    public function recordSend(string $id, array $to, array $cc, string $subject, string $message, int $version): void;
}
