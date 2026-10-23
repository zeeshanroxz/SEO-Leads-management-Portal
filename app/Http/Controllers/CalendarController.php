<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\CalendarRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarController extends Controller
{
    public const TYPES = ['call' => 'Call', 'meeting' => 'Meeting', 'follow_up' => 'Follow-up', 'training' => 'Training / internal'];

    public function __construct(private CalendarRepository $calendar, private DirectoryRepository $dir, private LeadRepository $leads, private PortalRepository $portal) {}

    public function index(Request $request)
    {
        $me = Demo::id();
        $events = $this->calendar->scoped()->map(fn ($e) => [
            'id' => $e->id, 'title' => $e->title, 'type' => $e->type, 'start' => $e->start, 'end' => $e->end, 'timezone' => $e->timezone,
            'status' => $e->status, 'cancel_reason' => $e->cancel_reason, 'location' => $e->location, 'notes' => $e->notes,
            'lead_id' => $e->lead_id, 'lead_name' => $e->lead?->customer?->business_name, 'organizer_id' => $e->organizer_id, 'organizer' => $e->organizer?->name,
            'attendees' => collect($e->attendees)->map(fn ($a) => ['user_id' => $a->user_id, 'name' => $this->dir->userName($a->user_id), 'response' => $a->response])->values()->all(),
            'reminders' => collect($e->reminders)->map(fn ($r) => $r->plain())->all(), 'sync' => $e->sync->plain(),
            'mine' => $e->organizer_id === $me, 'url' => $e->lead_id ? route('leads.show', $e->lead_id) : null,
        ])->values()->all();

        // People who can be invited: anyone active in the sales line (UI simulation of "permitted users").
        $invitable = $this->dir->users()->where('status', 'active')->whereIn('role', ['admin', 'manager', 'team_lead', 'agent', 'auditor'])->where('id', '!=', $me)
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => config('portal.roles.'.$u->role.'.label')])->values()->all();

        return view('pages.calendar.index', [
            'events' => $events, 'invitable' => $invitable, 'types' => self::TYPES, 'me' => $me,
            'leads' => $this->leads->scoped()->where('review_status', 'accepted')->map(fn ($l) => ['id' => $l->id, 'name' => $l->customer->business_name])->values()->all(),
            'conn' => $this->calendar->connectionFor($me),
            'start' => ['date' => $request->query('date', Carbon::today()->toDateString()), 'event' => $request->query('event'), 'new' => $request->boolean('new'), 'lead' => $request->query('lead')],
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:140', 'type' => ['required', Rule::in(array_keys(self::TYPES))], 'lead_id' => 'nullable|string',
            'date' => 'required|date', 'start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i|after:start_time',
            'timezone' => 'required|string|max:40', 'location' => 'nullable|string|max:200', 'notes' => 'nullable|string|max:1000',
            'attendees' => 'nullable|array', 'attendees.*' => 'string', 'reminder' => 'nullable|integer',
        ], ['end_time.after' => 'The end time must be after the start time.', 'title.required' => 'Give the event a title.']);
        $data['start'] = $data['date'].' '.$data['start_time'].':00';
        $data['end'] = $data['date'].' '.$data['end_time'].':00';

        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $e = $this->calendar->create($data);
        $this->portal->log('Appointment created', $e->id, null, 'calendar');

        return redirect()->route('calendar.index', ['date' => $data['date'], 'event' => $e->id])->with('toast', [
            'message' => 'Event created in the portal calendar.'.(count($data['attendees'] ?? []) ? ' Invitations are shown to attendees in-app; email invites are simulated.' : ''), 'demo' => count($data['attendees'] ?? []) > 0,
        ]);
    }

    public function update(Request $request, string $event)
    {
        $e = $this->calendar->find($event);
        abort_unless($e && (Demo::is('admin') || $e->organizer_id === Demo::id()), 403, 'Only the organiser or an Admin can change this event.');
        $data = $this->validated($request);
        $this->calendar->update($event, $data);
        $this->portal->log('Appointment updated', $event, null, 'calendar');

        return redirect()->route('calendar.index', ['date' => $data['date'], 'event' => $event])->with('toast', ['message' => 'Event updated. If the time changed, it is marked Rescheduled and attendees are notified (simulated).']);
    }

    public function cancel(Request $request, string $event)
    {
        $e = $this->calendar->find($event);
        abort_unless($e && (Demo::is('admin') || $e->organizer_id === Demo::id()), 403);
        $data = $request->validate(['reason' => 'required|string|min:5|max:300']);
        $this->calendar->cancel($event, $data['reason']);
        $this->portal->log('Appointment cancelled', $event, $data['reason'], 'calendar');

        return redirect()->route('calendar.index', ['date' => Carbon::parse($e->start)->toDateString()])->with('toast', ['message' => 'Event cancelled. It stays visible (struck through) for the record.', 'type' => 'info']);
    }
}
