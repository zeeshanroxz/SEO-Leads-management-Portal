<?php

namespace App\Contracts;

use App\Mock\Record;
use Illuminate\Support\Collection;

/**
 * Data-access contract for the Form domain.
 *
 * Phase 1 binds this to the session-backed mock repository (no database).
 * Phase 2 (after explicit approval) binds it to an Eloquent implementation, so
 * controllers and Blade views do not need to change.
 */
interface FormRepositoryContract
{
    public function forms(): Collection;

    public function form(?string $id): ?Record;

    public function version(?string $versionId): ?Record;

    public function fixedFields(): Collection;

    public function setFixedRequired(string $id, bool $required): void;

    public function publishedVersion(?string $formId): ?Record;

    public function draftVersion(?string $formId): ?Record;

    public function publishedByProject(): array;

    public function saveDraft(string $formId, array $sections, ?string $note = null): void;

    public function publish(string $formId, string $note): ?Record;

    public function discardDraft(string $formId): void;
}
