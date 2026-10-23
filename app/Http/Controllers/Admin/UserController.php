<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\PortalRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private DirectoryRepository $dir, private PortalRepository $portal) {}

    public function index()
    {
        return view('pages.admin.users', [
            'users' => $this->dir->users(), 'teams' => $this->dir->teams(), 'dir' => $this->dir,
            'roles' => config('portal.roles'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|max:160',
            'role' => ['required', Rule::in(array_keys(config('portal.roles')))], 'team_id' => 'nullable|string',
        ]);
        $team = $this->dir->team($data['team_id'] ?? null);
        $u = $this->dir->createUser([
            'name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'status' => 'active', 'last_login' => null,
            'team_id' => $team?->id, 'manager_id' => in_array($data['role'], ['team_lead', 'agent']) ? $team?->manager_id : null,
            'team_lead_id' => $data['role'] === 'agent' ? $team?->team_lead_id : null, 'projects' => [], 'phone' => '+1 555 0199',
            'title' => config("portal.roles.{$data['role']}.label"), 'two_factor' => false,
        ]);
        $this->portal->log('User created', $u->id.' '.$u->name, null, 'permission');

        return back()->with('toast', ['message' => $u->name.' added (demo record only — no invitation email was sent).', 'demo' => true]);
    }

    public function toggle(Request $request, string $user)
    {
        $u = $this->dir->user($user);
        abort_unless($u, 404);
        $request->validate(['reason' => 'required|string|min:5|max:300']);
        $to = $u->status === 'active' ? 'disabled' : 'active';
        $this->dir->updateUser($u->id, ['status' => $to]);
        $this->portal->log('User '.($to === 'disabled' ? 'disabled' : 're-enabled'), $u->id.' '.$u->name, $request->input('reason'), 'permission');

        return back()->with('toast', ['message' => $u->name.' '.($to === 'disabled' ? 'disabled. History is retained; the account is not deleted.' : 're-enabled.')]);
    }

    public function role(Request $request, string $user)
    {
        $u = $this->dir->user($user);
        abort_unless($u, 404);
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(config('portal.roles')))], 'reason' => 'required|string|min:5|max:300']);
        $this->dir->updateUser($u->id, ['role' => $data['role']]);
        $this->portal->log('User role changed', $u->id.' '.$u->name.' → '.$data['role'], $data['reason'], 'permission');

        return back()->with('toast', ['message' => $u->name.' is now '.config('portal.roles.'.$data['role'].'.label').'. Commission records keep the role at time of sale.']);
    }
}
