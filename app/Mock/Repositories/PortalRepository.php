<?php

namespace App\Mock\Repositories;

use App\Contracts\PortalRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Learning library, notifications, audit trail, integration errors, overrides and settings flags. */
class PortalRepository extends MockRepository implements PortalRepositoryContract
{
    public function __construct(private DirectoryRepository $dir) {}

    // ---------------------------------------------------------------- learning

    public function learning(): Collection
    {
        $role = Demo::roleLabel();

        return $this->rows('learning')->sortByDesc('published_at')->filter(function ($r) use ($role) {
            if (Demo::is('admin')) {
                return true;
            }
            $a = $r->audience;

            return $a->type === 'all' || in_array($role, (array) $a->labels, true) || ($a->type === 'projects');
        })->values();
    }

    public function publishLearning(array $d): Record
    {
        $now = Carbon::now()->toDateTimeString();

        return Record::from(DemoStore::create('learning', [
            'title' => $d['title'], 'category' => $d['category'], 'file_type' => $d['file_type'] ?? 'PDF', 'size' => $d['size'] ?? '— (demo, no upload)',
            'audience' => ['type' => $d['audience_type'], 'labels' => $d['audience_labels']], 'published_at' => $now, 'author_id' => Demo::id(),
            'version' => 1, 'description' => $d['description'], 'read_count' => 0, 'status' => 'published',
            'versions' => [['no' => 1, 'at' => $now, 'note' => 'Current version']],
            'notified' => ['in_app' => ! empty($d['notify_in_app']), 'email' => ! empty($d['notify_email'])],
        ], 'LR-', 100));
    }

    // ---------------------------------------------------------------- notifications

    public function notifications(): Collection
    {
        $role = Demo::roleKey();
        $read = DemoStore::flag('read_notifications', []);

        return $this->rows('notifications')->filter(fn ($n) => in_array($role, (array) $n->for, true))->map(function ($n) use ($read) {
            $n->unread = $n->unread && ! in_array($n->id, $read, true);

            return $n;
        })->sortByDesc('at')->values();
    }

    public function unreadCount(): int
    {
        return $this->notifications()->where('unread', true)->count();
    }

    public function markRead(?string $id = null): void
    {
        $read = DemoStore::flag('read_notifications', []);
        $ids = $id ? [$id] : $this->notifications()->pluck('id')->all();
        DemoStore::setFlag('read_notifications', array_values(array_unique(array_merge($read, $ids))));
    }

    // ---------------------------------------------------------------- audit trail & errors

    public function auditLog(): Collection
    {
        return $this->rows('audit_log')->map(function ($a) {
            $a->actor = $this->dir->user($a->actor_id);

            return $a;
        })->sortByDesc('at')->values();
    }

    public function log(string $action, string $entity, ?string $reason = null, string $category = 'general'): void
    {
        DemoStore::create('audit_log', [
            'at' => Carbon::now()->toDateTimeString(), 'actor_id' => Demo::id(), 'action' => $action, 'entity' => $entity,
            'reason' => $reason, 'category' => $category, 'ip' => '192.0.2.99',
        ], 'AL-', 100);
    }

    public function integrationErrors(): Collection
    {
        return $this->rows('integration_errors')->sortByDesc('at')->values();
    }

    public function setErrorStatus(string $id, string $status): void
    {
        DemoStore::patch('integration_errors', $id, ['status' => $status]);
    }

    // ---------------------------------------------------------------- visibility overrides

    public function overrides(): Collection
    {
        return $this->rows('overrides')->map(function ($o) {
            $o->by_user = $this->dir->user($o->by);

            return $o;
        })->sortByDesc('at')->values();
    }

    public function addOverride(array $d): void
    {
        DemoStore::create('overrides', [
            'lead_id' => $d['lead_id'], 'group_key' => $d['group_key'], 'field_group' => $d['field_group'], 'effect' => $d['effect'],
            'applies_to' => $d['applies_to'], 'reason' => $d['reason'], 'by' => Demo::id(), 'at' => Carbon::now()->toDateTimeString(),
            'status' => 'active', 'expires' => $d['expires'] ?: null,
        ], 'V-', 10);
        $this->log('Visibility override '.$d['effect'], $d['lead_id'].' / '.$d['field_group'], $d['reason'], 'permission');
    }

    public function revokeOverride(string $id, string $reason): void
    {
        DemoStore::patch('overrides', $id, ['status' => 'revoked']);
        $this->log('Visibility override revoked', $id, $reason, 'permission');
    }

    // ---------------------------------------------------------------- flags / settings

    public function setting(string $key, mixed $default = null): mixed
    {
        return DemoStore::flag($key, $default);
    }

    public function setSetting(string $key, mixed $value): void
    {
        DemoStore::setFlag($key, $value);
    }
}
