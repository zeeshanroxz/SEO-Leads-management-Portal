<?php

namespace App\Mock\Repositories;

use App\Contracts\FormRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Lead forms, form versions and fixed-field settings. */
class FormRepository extends MockRepository implements FormRepositoryContract
{
    public function forms(): Collection
    {
        return $this->rows('forms');
    }

    public function form(?string $id): ?Record
    {
        return $this->row('forms', $id);
    }

    public function version(?string $versionId): ?Record
    {
        foreach ($this->forms() as $f) {
            $v = collect($f->versions)->firstWhere('id', $versionId);
            if ($v) {
                return $v;
            }
        }

        return null;
    }

    public function fixedFields(): Collection
    {
        return $this->rows('fixed_fields');
    }

    public function setFixedRequired(string $id, bool $required): void
    {
        DemoStore::patch('fixed_fields', $id, ['required' => $required]);
    }

    public function publishedVersion(?string $formId): ?Record
    {
        $form = $this->form($formId);

        return $form ? collect($form->versions)->firstWhere('status', 'published') : null;
    }

    public function draftVersion(?string $formId): ?Record
    {
        $form = $this->form($formId);

        return $form ? collect($form->versions)->firstWhere('status', 'draft') : null;
    }

    /** Published versions keyed by project id, as plain arrays for the lead wizard. */
    public function publishedByProject(): array
    {
        $out = [];
        foreach ($this->forms() as $f) {
            $v = $this->publishedVersion($f->id);
            if ($v) {
                $out[$f->project_id] = ['form_id' => $f->id, 'version_id' => $v->id, 'version' => $v->version, 'sections' => $v->plain()['sections']];
            }
        }

        return $out;
    }

    /** Save the builder state into the (single) draft version, creating it when needed. */
    public function saveDraft(string $formId, array $sections, ?string $note = null): void
    {
        $form = $this->form($formId)->plain();
        $versions = $form['versions'];
        $draftIdx = collect($versions)->search(fn ($v) => $v['status'] === 'draft');
        $now = Carbon::now()->toDateTimeString();
        if ($draftIdx === false) {
            $no = collect($versions)->max('version') + 1;
            array_unshift($versions, [
                'id' => 'FV-'.substr($formId, -1).'-'.$no, 'version' => $no, 'status' => 'draft', 'sections' => $sections,
                'change_note' => $note ?: 'Draft changes', 'published_at' => null, 'published_by' => null, 'updated_at' => $now,
            ]);
        } else {
            $versions[$draftIdx]['sections'] = $sections;
            $versions[$draftIdx]['updated_at'] = $now;
            if ($note) {
                $versions[$draftIdx]['change_note'] = $note;
            }
        }
        DemoStore::patch('forms', $formId, ['versions' => array_values($versions)]);
    }

    /** Publish the draft as a new immutable version; the previous published version is archived. */
    public function publish(string $formId, string $note): ?Record
    {
        $form = $this->form($formId)->plain();
        $versions = $form['versions'];
        $now = Carbon::now()->toDateTimeString();
        $idx = collect($versions)->search(fn ($v) => $v['status'] === 'draft');
        if ($idx === false) {
            return null;
        }
        foreach ($versions as $i => $v) {
            if ($v['status'] === 'published') {
                $versions[$i]['status'] = 'archived';
            }
        }
        $versions[$idx]['status'] = 'published';
        $versions[$idx]['published_at'] = $now;
        $versions[$idx]['published_by'] = Demo::id();
        $versions[$idx]['change_note'] = $note;
        DemoStore::patch('forms', $formId, ['versions' => array_values($versions)]);

        return $this->form($formId);
    }

    public function discardDraft(string $formId): void
    {
        $form = $this->form($formId)->plain();
        $versions = array_values(array_filter($form['versions'], fn ($v) => $v['status'] !== 'draft'));
        DemoStore::patch('forms', $formId, ['versions' => $versions]);
    }
}
