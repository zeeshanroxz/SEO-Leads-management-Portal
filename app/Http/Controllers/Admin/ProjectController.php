<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mock\Repositories\DirectoryRepository;
use App\Mock\Repositories\FormRepository;
use App\Mock\Repositories\PortalRepository;
use App\Support\Statuses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function __construct(private DirectoryRepository $dir, private FormRepository $forms, private PortalRepository $portal) {}

    public function index()
    {
        $dims = collect(['lead_review', 'sales', 'audit', 'agreement', 'billing', 'commission'])->mapWithKeys(fn ($d) => [$d => [
            'label' => config("statuses.$d.label"), 'values' => Statuses::all($d), 'disabled' => Statuses::disabled($d),
        ]]);

        return view('pages.admin.projects', [
            'projects' => $this->dir->projects(), 'services' => $this->dir->services(), 'auditors' => $this->dir->usersByRole('auditor'),
            'forms' => $this->forms->forms(), 'dims' => $dims, 'colors' => ['slate', 'blue', 'indigo', 'violet', 'teal', 'green', 'amber', 'orange', 'red', 'stone'],
        ]);
    }

    public function update(Request $request, string $project)
    {
        $p = $this->dir->project($project);
        abort_unless($p, 404);
        $data = $request->validate([
            'audit_policy' => ['required', Rule::in(['required', 'optional', 'not_applicable'])],
            'auditor_ids' => 'array', 'auditor_ids.*' => 'string', 'form_id' => 'nullable|string', 'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);
        if (empty($data['auditor_ids']) && $data['audit_policy'] !== 'not_applicable') {
            return back()->withErrors(['auditor_ids' => 'Assign at least one auditor when audits are required or optional.']);
        }
        $this->dir->updateProject($p->id, ['audit_policy' => $data['audit_policy'], 'auditor_ids' => $data['auditor_ids'] ?? [], 'form_id' => $data['form_id'] ?: $p->form_id, 'status' => $data['status']]);
        $this->portal->log('Project settings changed', $p->id.' '.$p->name, 'Audit: '.$data['audit_policy'], 'config');

        return back()->with('toast', ['message' => $p->name.' updated.']);
    }

    public function statuses(Request $request)
    {
        $dim = $request->validate(['dimension' => ['required', Rule::in(['lead_review', 'sales', 'audit', 'agreement', 'billing', 'commission'])]])['dimension'];
        if ($request->input('op') === 'toggle') {
            $key = $request->validate(['key' => 'required|string'])['key'];
            $off = Statuses::toggle($dim, $key);
            $this->portal->log('Status '.($off ? 'deactivated' : 'activated'), "$dim.$key", null, 'config');

            return back()->with('toast', ['message' => status_label($dim, $key).' is now '.($off ? 'inactive. Existing records keep it; it can no longer be chosen.' : 'active.')]);
        }
        $data = $request->validate(['label' => 'required|string|max:40', 'color' => 'required|string', 'reason' => 'nullable|boolean'], ['label.required' => 'Give the status a name.']);
        Statuses::add($dim, $data['label'], $data['color'], (bool) ($data['reason'] ?? false));
        $this->portal->log('Status added', "$dim: ".$data['label'], null, 'config');

        return back()->with('toast', ['message' => '“'.$data['label'].'” added to '.config("statuses.$dim.label").' statuses.']);
    }
}
