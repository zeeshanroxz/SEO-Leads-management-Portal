<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private PortalRepository $portal) {}

    public function index()
    {
        return view('pages.notifications.index', ['items' => $this->portal->notifications()]);
    }

    public function read(Request $request)
    {
        $this->portal->markRead($request->input('id'));

        return back()->with('toast', ['message' => $request->filled('id') ? 'Marked as read.' : 'All notifications marked as read.']);
    }

    public function settings()
    {
        $prefs = [
            'lead_review' => ['Lead accepted / rejected', 'Updates on leads you submitted', ['agent', 'team_lead', 'manager']],
            'review_queue' => ['New leads awaiting review', 'When a lead enters your review queue', ['admin', 'manager', 'team_lead']],
            'audit' => ['Audit & quote updates', 'Assignments, submissions, revisions and sends', ['admin', 'auditor', 'agent', 'team_lead', 'manager']],
            'appointment' => ['Appointment reminders', 'Reminder before meetings and follow-ups', ['admin', 'agent', 'team_lead', 'manager']],
            'handoff' => ['Closed Won handoffs', 'New deals ready for an agreement', ['admin', 'finance']],
            'agreement' => ['Agreement activity', 'Viewed, signed, rejected or expired agreements', ['admin', 'finance']],
            'square' => ['Square sync alerts', 'Webhook failures and reconciliation mismatches', ['admin', 'finance']],
            'commission' => ['Commission updates', 'Eligible, approved, due and paid', ['agent', 'team_lead', 'manager', 'finance', 'admin']],
            'learning' => ['New learning documents', 'Training and policy publications', ['admin', 'finance', 'auditor', 'agent', 'team_lead', 'manager']],
        ];
        $mine = collect($prefs)->filter(fn ($p) => in_array(Demo::roleKey(), $p[2], true));

        return view('pages.notifications.settings', ['prefs' => $mine, 'saved' => $this->portal->setting('notif_prefs', [])]);
    }

    public function saveSettings(Request $request)
    {
        $this->portal->setSetting('notif_prefs', $request->input('prefs', []));
        $this->portal->setSetting('notif_reminder_minutes', (int) $request->input('reminder_minutes', 30));

        return back()->with('toast', ['message' => 'Notification preferences saved (stored for this demo session only).']);
    }
}
