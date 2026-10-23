<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Demo;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(private PortalRepository $portal, private DirectoryRepository $dir) {}

    public function index()
    {
        $log = $this->portal->auditLog();
        $me = Demo::id();
        // UI simulation of role-scoped history ("Activity / audit history" row in the permission baseline).
        $log = match (Demo::roleKey()) {
            'admin' => $log,
            'finance' => $log->whereIn('category', ['finance', 'agreement'])->values(),
            'auditor' => $log->filter(fn ($a) => $a->category === 'audit' || $a->actor_id === $me)->values(),
            'manager', 'team_lead' => $log->filter(fn ($a) => in_array($a->actor_id, $this->dir->reportIds($me), true))->values(),
            default => $log->where('actor_id', $me)->values(),
        };

        return view('pages.admin.activity', [
            'log' => $log, 'errors' => Demo::can(['admin.audit', 'finance.square']) ? $this->portal->integrationErrors() : collect(),
            'categories' => $log->pluck('category')->unique()->values(), 'scoped' => ! Demo::is('admin'),
        ]);
    }

    public function update(Request $request, string $error)
    {
        $status = $request->validate(['status' => 'required|in:acknowledged,resolved,open'])['status'];
        $this->portal->setErrorStatus($error, $status);
        $this->portal->log('Integration error '.$status, $error, null, 'integration');

        return back()->with('toast', ['message' => $request->boolean('retry') ? 'Retry simulated — the operation was re-queued and succeeded.' : 'Error marked '.$status.'.', 'demo' => $request->boolean('retry')]);
    }
}
