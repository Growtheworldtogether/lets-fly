<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AgreementVersion;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\PaymentService;
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
            'r' => $r?->load('invoices.payments'),
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

    public function proof(Request $request, Invoice $invoice, PaymentService $payments)
    {
        abort_if($request->session()->has('impersonation'), 403, 'Proof of payment has to be uploaded by the member.');
        $request->validate(['proof' => ['required', 'file']], ['proof.required' => 'Choose a file to upload.']);
        $payments->submitProof($request->user(), $invoice->load('reservation'), $request->file('proof'));

        return redirect()->route('dashboard')->with('toast', 'Proof received. We will verify it and update your dashboard.');
    }

    public function proofFile(Request $request, Payment $payment)
    {
        abort_unless($payment->reservation->user_id === $request->user()->id || $request->user()->can('payments.verify'), 404);
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path((string) $payment->proof_path);
        abort_unless($payment->proof_path && is_file($path), 404);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'image/jpeg';

        return response()->file($path, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox", 'Cache-Control' => 'private, no-store']);
    }
}
