<?php

namespace App\Http\Controllers;

use App\Mock\DemoStore;
use Illuminate\Http\Request;

/** Demo-only: role switcher and data reset. NOT authentication. */
class DemoController extends Controller
{
    public function role(Request $request)
    {
        $data = $request->validate(['role' => 'required|in:'.implode(',', array_keys(config('portal.roles'))), 'back' => 'nullable|string']);
        session(['demo_role' => $data['role']]);

        $back = $data['back'] ?? null;
        $host = parse_url((string) $back, PHP_URL_HOST);
        $safe = $back && (! $host || $host === $request->getHost());

        return ($safe && ! $request->boolean('go_dashboard') ? redirect($back) : redirect()->route('dashboard'))
            ->with('toast', ['message' => 'Now previewing as '.config('portal.roles.'.$data['role'].'.label').' (frontend preview only).', 'type' => 'info']);
    }

    public function financeManager(Request $request)
    {
        session(['demo_finance_manager' => ! session('demo_finance_manager', true)]);

        return back()->with('toast', ['message' => 'Finance Manager permission set '.(session('demo_finance_manager') ? 'enabled' : 'disabled').' (preview).', 'type' => 'info']);
    }

    public function reset()
    {
        DemoStore::reset();

        return redirect()->route('dashboard')->with('toast', ['message' => 'Demo data reset to the original mock records.', 'type' => 'success']);
    }
}
