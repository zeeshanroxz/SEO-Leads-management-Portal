<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Calendar domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface CalendarRepositoryContract
{
    public function all(): Collection;

    public function scoped(): Collection;

    public function find(?string $id): ?Record;

    public function create(array $d): Record;

    public function update(string $id, array $d): void;

    public function cancel(string $id, string $reason): void;

    public function connections(): Collection;

    public function connectionFor(string $userId): Record;

    public function setConnection(string $userId, string $provider, string $state): void;
}
