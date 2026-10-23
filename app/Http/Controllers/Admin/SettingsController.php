<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mock\DemoStore;
use App\Mock\Repositories\PortalRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(private PortalRepository $portal) {}

    public function index()
    {
        $d = config('portal.defaults');

        return view('pages.admin.settings', [
            'assumptions' => config('portal.assumptions'),
            'values' => [
                'days' => (int) DemoStore::flag('commission_days', $d['commission_eligibility_days']),
                'mode' => DemoStore::flag('commission_day_mode', $d['commission_day_mode']),
                'tl_review_team' => (bool) DemoStore::flag('tl_review_team', false),
                'currency' => DemoStore::flag('currency', config('portal.currency')),
                'reversal' => DemoStore::flag('reversal_policy', $d['reversal_policy']),
                'send_policy' => DemoStore::flag('agent_send_audit', true),
            ],
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'days' => 'required|integer|min:0|max:365', 'mode' => ['required', Rule::in(['calendar', 'business'])],
            'currency' => ['required', Rule::in(['USD', 'GBP', 'EUR', 'CAD', 'AUD'])], 'reversal' => ['required', Rule::in(['manual_review', 'auto_reverse', 'no_reversal'])],
        ]);
        DemoStore::setFlag('commission_days', (int) $data['days']);
        DemoStore::setFlag('commission_day_mode', $data['mode']);
        DemoStore::setFlag('currency', $data['currency']);
        DemoStore::setFlag('reversal_policy', $data['reversal']);
        DemoStore::setFlag('tl_review_team', $request->boolean('tl_review_team'));
        DemoStore::setFlag('agent_send_audit', $request->boolean('send_policy'));
        $this->portal->log('Settings changed', 'General settings', 'Eligibility '.$data['days'].' '.$data['mode'].' days', 'config');

        return back()->with('toast', ['message' => 'Settings saved for this demo session. Commission eligibility dates recalculate from these rules.']);
    }
}
