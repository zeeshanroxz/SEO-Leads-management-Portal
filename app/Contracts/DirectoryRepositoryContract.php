<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Directory domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface DirectoryRepositoryContract
{
    public function users(): Collection;

    public function user(?string $id): ?Record;

    public function userName(?string $id): string;

    public function usersByRole(string $role): Collection;

    public function teams(): Collection;

    public function team(?string $id): ?Record;

    public function reportIds(string $userId): array;

    public function projects(): Collection;

    public function project(?string $id): ?Record;

    public function services(): Collection;

    public function service(?string $id): ?Record;

    public function updateProject(string $id, array $fields): void;

    public function updateUser(string $id, array $fields): void;

    public function createUser(array $row): Record;
}
