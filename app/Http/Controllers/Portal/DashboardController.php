<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AgreementVersion;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\Pricing;
use App\Services\ReservationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ReservationService $svc)
    {
        $r = $svc->active($request->user());

        return view('member.dashboard', [
            'r' => $r?->load('invoices'),
            'wire' => (string) Setting::value('wire_details', ''),
            'terms' => ['price' => Pricing::price(), 'public' => Pricing::publicPrice(), 'deposit' => Pricing::deposit(), 'balance' => Pricing::balance(), 'days' => Pricing::balanceDays()],
        ]);
    }

    public function agreement(Request $request, ReservationService $svc)
    {
        if ($svc->active($request->user())) {
            return redirect()->route('dashboard');
        }

        return view('member.agreement', ['agreement' => AgreementVersion::current()]);
    }

    public function reserve(Request $request, ReservationService $svc)
    {
        abort_if($request->session()->has('impersonation'), 403, 'A reservation has to be accepted by the member themselves.');
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Please tick the box to accept the agreement.']);
        if (! $svc->active($request->user())) {
            $svc->reserve($request->user(), (string) $request->ip());
        }

        return redirect()->route('dashboard')->with('toast', 'Your allocation is reserved. Your deposit invoice is below.');
    }

    public function invoicePdf(Request $request, Invoice $invoice)
    {
        $r = $invoice->reservation()->with('user')->firstOrFail();
        abort_unless($r->user_id === $request->user()->id || $request->user()->can('members.manage'), 404);

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice, 'r' => $r, 'user' => $r->user, 'wire' => (string) Setting::value('wire_details', '')])
            ->setPaper('letter')->download($invoice->number.'.pdf');
    }
}
