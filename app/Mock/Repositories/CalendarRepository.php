<?php

namespace App\Mock\Repositories;

use App\Contracts\CalendarRepositoryContract;
use App\Mock\DemoStore;
use App\Mock\MockData;
use App\Mock\Record;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Internal calendar events and (simulated) provider connections. */
class CalendarRepository extends MockRepository implements CalendarRepositoryContract
{
    public function __construct(private DirectoryRepository $dir, private LeadRepository $leads) {}

    public function all(): Collection
    {
        return $this->rows('events')->map(function (Record $e) {
            $e->organizer = $this->dir->user($e->organizer_id);
            $e->lead = $e->lead_id ? $this->leads->find($e->lead_id) : null;
            $e->attendee_users = collect($e->attendees)->map(fn ($a) => ['user' => $this->dir->user($a->user_id), 'response' => $a->response])->all();

            return $e;
        })->sortBy('start')->values();
    }

    /** Events the current demo role may see (organiser, invitee, or supervisor of the organiser). */
    public function scoped(): Collection
    {
        if (! Demo::can('calendar.use')) {
            return collect();
        }
        $me = Demo::id();
        $reports = $this->dir->reportIds($me);

        return $this->all()->filter(function ($e) use ($me, $reports) {
            if (Demo::is('admin')) {
                return true;
            }
            $invited = collect($e->attendees)->contains(fn ($a) => $a->user_id === $me);

            return $e->organizer_id === $me || $invited || (in_array(Demo::roleKey(), ['manager', 'team_lead'], true) && in_array($e->organizer_id, $reports, true));
        })->values();
    }

    public function find(?string $id): ?Record
    {
        return $id ? $this->all()->firstWhere('id', $id) : null;
    }

    public function create(array $d): Record
    {
        $lead = ! empty($d['lead_id']) ? $this->leads->find($d['lead_id']) : null;
        $row = DemoStore::create('events', [
            'title' => $d['title'], 'type' => $d['type'], 'lead_id' => $d['lead_id'] ?: null, 'customer_id' => $lead?->customer_id,
            'start' => $d['start'], 'end' => $d['end'], 'timezone' => $d['timezone'] ?? 'UTC', 'organizer_id' => Demo::id(),
            'attendees' => collect($d['attendees'] ?? [])->map(fn ($id) => ['user_id' => $id, 'response' => 'needs_action'])->all(),
            'location' => $d['location'] ?? null, 'notes' => $d['notes'] ?? null,
            'reminders' => [['minutes' => (int) ($d['reminder'] ?? 30), 'channel' => 'in_app'], ['minutes' => 60, 'channel' => 'email']],
            'status' => 'scheduled', 'cancel_reason' => null,
            'sync' => ['google' => 'not_connected', 'zoom' => 'not_connected', 'last_sync' => null, 'error' => null],
        ], 'E-', 4100);
        if ($lead) {
            $this->leads->addActivity($lead->id, 'appointment', 'Appointment booked: '.$d['title'], Carbon::parse($d['start'])->format('d M Y, H:i'));
        }

        return $this->find($row['id']);
    }

    public function update(string $id, array $d): void
    {
        $e = $this->find($id);
        $patch = [
            'title' => $d['title'], 'type' => $d['type'], 'start' => $d['start'], 'end' => $d['end'], 'location' => $d['location'] ?? null,
            'notes' => $d['notes'] ?? null, 'lead_id' => $d['lead_id'] ?: null,
            'attendees' => collect($d['attendees'] ?? [])->map(fn ($uid) => ['user_id' => $uid, 'response' => 'needs_action'])->all(),
        ];
        if (Carbon::parse($d['start'])->ne(Carbon::parse($e->start))) {
            $patch['status'] = 'rescheduled';
        }
        DemoStore::patch('events', $id, $patch);
    }

    public function cancel(string $id, string $reason): void
    {
        DemoStore::patch('events', $id, ['status' => 'cancelled', 'cancel_reason' => $reason]);
    }

    public function connections(): Collection
    {
        return $this->rows('calendar_connections');
    }

    public function connectionFor(string $userId): Record
    {
        return $this->connections()->firstWhere('user_id', $userId) ?? Record::from(['id' => 'CC-'.$userId, 'user_id' => $userId, 'google' => 'not_connected', 'zoom' => 'not_connected']);
    }

    /** Simulated connect/disconnect/sync. No OAuth happens. */
    public function setConnection(string $userId, string $provider, string $state): void
    {
        $conn = $this->connectionFor($userId);
        $patch = [$provider => $state, 'last_sync' => $state === 'connected' ? Carbon::now()->toDateTimeString() : ($conn->last_sync ?? null), 'error' => null];
        if (in_array($conn->id, collect(MockData::get('calendar_connections'))->pluck('id')->all(), true)) {
            DemoStore::patch('calendar_connections', $conn->id, $patch);
        } else {
            DemoStore::create('calendar_connections', ['id' => $conn->id, 'user_id' => $userId, 'google' => 'not_connected', 'zoom' => 'not_connected'] + $patch);
            DemoStore::patch('calendar_connections', $conn->id, $patch);
        }
    }
}
