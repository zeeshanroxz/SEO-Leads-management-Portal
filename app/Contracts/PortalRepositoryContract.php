<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Portal domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface PortalRepositoryContract
{
    public function learning(): Collection;

    public function publishLearning(array $d): Record;

    public function notifications(): Collection;

    public function unreadCount(): int;

    public function markRead(?string $id = null): void;

    public function auditLog(): Collection;

    public function log(string $action, string $entity, ?string $reason = null, string $category = 'general'): void;

    public function integrationErrors(): Collection;

    public function setErrorStatus(string $id, string $status): void;

    public function overrides(): Collection;

    public function addOverride(array $d): void;

    public function revokeOverride(string $id, string $reason): void;

    public function setting(string $key, mixed $default = null): mixed;

    public function setSetting(string $key, mixed $value): void;
}
