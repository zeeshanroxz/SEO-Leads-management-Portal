<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AgreementRepository;
use Illuminate\Http\Request;

/**
 * PUBLIC customer signing page MOCKUP. No secure token, no stored signature,
 * no legal effect. Phase 2 needs a legal/compliance review before this ships.
 */
class SigningController extends Controller
{
    public function __construct(private AgreementRepository $agreements) {}

    public function show(string $agreement)
    {
        $a = $this->agreements->find($agreement);
        abort_unless($a, 404);

        return view('pages.sign.show', ['a' => $a, 'customer' => $a->customer, 'staff' => session('demo_role') !== null]);
    }

    public function complete(Request $request, string $agreement)
    {
        $a = $this->agreements->find($agreement);
        abort_unless($a, 404);
        if ($request->input('mode') === 'offline') {
            $data = $request->validate(['file_name' => 'required|string|max:200']);
            $this->agreements->recordOfflineUpload($a->id, $data['file_name']);

            return redirect()->route('sign.show', $a->id)->with('toast', ['message' => 'Signed copy received for Finance review (demo — nothing was stored).', 'demo' => true]);
        }
        $data = $request->validate(['name' => 'required|string|max:120', 'method' => 'required|in:typed,drawn', 'consent' => 'accepted'], ['consent.accepted' => 'Please tick the consent box.']);
        $this->agreements->simulateCustomerSignature($a->id, $data['name'], $data['method']);

        return redirect()->route('sign.show', $a->id)->with('toast', ['message' => 'Demo signing complete. No legally valid signature was stored.', 'demo' => true]);
    }
}
