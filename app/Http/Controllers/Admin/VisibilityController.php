<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mock\Repositories\LeadRepository;
use App\Mock\Repositories\PortalRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisibilityController extends Controller
{
    private const GROUPS = ['upsell' => 'Services and upsell details', 'notes' => 'Internal call notes', 'agreement' => 'Agreement summary', 'commission' => 'Commission details'];

    public function __construct(private PortalRepository $portal, private LeadRepository $leads) {}

    public function index()
    {
        return view('pages.admin.visibility', ['overrides' => $this->portal->overrides(), 'leads' => $this->leads->all(), 'groups' => self::GROUPS]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['required', Rule::in($this->leads->all()->pluck('id')->all())], 'group_key' => ['required', Rule::in(array_keys(self::GROUPS))],
            'effect' => ['required', Rule::in(['restricted', 'granted'])], 'applies_to' => 'required|string|max:120', 'reason' => 'required|string|min:10|max:500', 'expires' => 'nullable|date',
        ], ['reason.required' => 'An audit reason is required for every override.', 'reason.min' => 'Reason must be at least 10 characters.']);
        $this->portal->addOverride($data + ['field_group' => self::GROUPS[$data['group_key']]]);

        return back()->with('toast', ['message' => 'Override saved and logged.']);
    }

    public function revoke(Request $request, string $override)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:300']);
        $this->portal->revokeOverride($override, $data['reason']);

        return back()->with('toast', ['message' => 'Override revoked — default role rules apply again.', 'type' => 'info']);
    }
}
