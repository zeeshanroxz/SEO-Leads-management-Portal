<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\CalendarRepository;
use App\Mock\Repositories\DirectoryRepository;
use App\Support\Demo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** SIMULATED Google Calendar / Zoom connections - no OAuth is performed. */
class CalendarIntegrationController extends Controller
{
    public function __construct(private CalendarRepository $calendar, private DirectoryRepository $dir) {}

    public function index()
    {
        return view('pages.calendar.integrations', [
            'conn' => $this->calendar->connectionFor(Demo::id()),
            'team' => Demo::is('admin') ? $this->calendar->connections()->map(function ($c) {
                $c->user = $this->dir->user($c->user_id);

                return $c;
            }) : collect(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['provider' => ['required', Rule::in(['google', 'zoom'])], 'op' => ['required', Rule::in(['connect', 'disconnect', 'sync', 'reconnect'])]]);
        $label = $data['provider'] === 'google' ? 'Google Calendar' : 'Zoom';
        $state = in_array($data['op'], ['connect', 'sync', 'reconnect'], true) ? 'connected' : 'not_connected';
        $this->calendar->setConnection(Demo::id(), $data['provider'], $state);

        $msg = match ($data['op']) {
            'connect', 'reconnect' => $label.' connected (simulated). In production you would approve the provider\'s consent screen first.',
            'sync' => $label.' sync run (simulated). Portal events were pushed one-way; internal events were never at risk.',
            default => $label.' disconnected (simulated). Your portal events are unaffected.',
        };

        return back()->with('toast', ['message' => $msg, 'demo' => true]);
    }
}
