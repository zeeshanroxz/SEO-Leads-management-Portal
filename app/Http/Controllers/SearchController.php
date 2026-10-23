<?php

namespace App\Http\Controllers;

use App\Mock\Repositories\AgreementRepository;
use App\Mock\Repositories\LeadRepository;
use App\Support\Demo;
use Illuminate\Http\Request;

/** Global search over the records in the current role's scope. */
class SearchController extends Controller
{
    public function __invoke(Request $request, LeadRepository $leads, AgreementRepository $agreements)
    {
        $q = mb_strtolower(trim((string) $request->query('q')));
        $out = collect();
        if (mb_strlen($q) >= 2) {
            foreach ($leads->scoped() as $l) {
                $hay = mb_strtolower($l->id.' '.$l->customer->business_name.' '.$l->customer->business_email.' '.$l->customer->domain.' '.$l->owner?->name);
                if (str_contains($hay, $q)) {
                    $out->push(['type' => 'Lead', 'label' => $l->customer->business_name, 'sub' => $l->id.' · '.status_label('sales', $l->sales_status).' · '.$l->owner?->name, 'url' => route('leads.show', $l->id)]);
                }
            }
            if (Demo::can('agreements.view')) {
                foreach ($agreements->all() as $a) {
                    if (str_contains(mb_strtolower($a->id.' '.$a->customer?->business_name), $q)) {
                        $out->push(['type' => 'Agreement', 'label' => $a->id.' · '.$a->customer?->business_name, 'sub' => status_label('agreement', $a->status), 'url' => route('agreements.show', $a->id)]);
                    }
                }
            }
        }

        return response()->json(['results' => $out->take(8)->values()]);
    }
}
