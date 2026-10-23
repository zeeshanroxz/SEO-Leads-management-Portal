<?php

namespace App\Mock\Repositories;

use App\Contracts\DirectoryRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\Record;
use Illuminate\Support\Collection;

/** Users, teams, projects and services. */
class DirectoryRepository extends MockRepository implements DirectoryRepositoryContract
{
    public function users(): Collection
    {
        return $this->rows('users');
    }

    public function user(?string $id): ?Record
    {
        return $this->row('users', $id);
    }

    public function userName(?string $id): string
    {
        return $this->user($id)?->name ?? 'Unknown user';
    }

    public function usersByRole(string $role): Collection
    {
        return $this->users()->where('role', $role)->where('status', 'active')->values();
    }

    public function teams(): Collection
    {
        return $this->rows('teams');
    }

    public function team(?string $id): ?Record
    {
        return $this->row('teams', $id);
    }

    /** IDs of users the given user supervises (inclusive of themself). */
    public function reportIds(string $userId): array
    {
        $u = $this->user($userId);
        $ids = [$userId];
        if ($u?->role === 'manager') {
            $ids = array_merge($ids, $this->users()->where('manager_id', $userId)->pluck('id')->all());
        } elseif ($u?->role === 'team_lead') {
            $ids = array_merge($ids, $this->users()->where('team_lead_id', $userId)->pluck('id')->all());
        } elseif ($u?->role === 'admin') {
            $ids = $this->users()->pluck('id')->all();
        }

        return array_values(array_unique($ids));
    }

    public function projects(): Collection
    {
        return $this->rows('projects');
    }

    public function project(?string $id): ?Record
    {
        return $this->row('projects', $id);
    }

    public function services(): Collection
    {
        return $this->rows('services');
    }

    public function service(?string $id): ?Record
    {
        return $this->row('services', $id);
    }

    public function updateProject(string $id, array $fields): void
    {
        DemoStore::patch('projects', $id, $fields);
    }

    public function updateUser(string $id, array $fields): void
    {
        DemoStore::patch('users', $id, $fields);
    }

    public function createUser(array $row): Record
    {
        return Record::from(DemoStore::create('users', $row, 'U-', 20));
    }
}
