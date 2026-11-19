<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $rows = Reservation::with('user')->latest('id')->when($request->query('q'), function ($q, $s) {
            $q->where(fn ($w) => $w->where('reference', 'like', "%$s%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$s%")->orWhere('member_number', 'like', "%$s%")));
        })->paginate(25)->withQueryString();

        return view('admin.reservations', ['rows' => $rows]);
    }

    public function show(Reservation $reservation)
    {
        return view('admin.reservation', ['r' => $reservation->load('user', 'invoices.payments')]);
    }

    public function verify(Request $request, Payment $payment, PaymentService $svc)
    {
        $svc->verify($payment, $request->user());

        return redirect()->route('admin.reservation', $payment->reservation_id)->with('toast', 'Payment verified.');
    }

    public function reject(Request $request, Payment $payment, PaymentService $svc)
    {
        $d = $request->validate(['note' => ['required', 'string', 'max:200']], ['note.required' => 'Say what is wrong so the member knows what to fix.']);
        $svc->reject($payment, $request->user(), $d['note']);

        return redirect()->route('admin.reservation', $payment->reservation_id)->with('toast', 'Proof rejected. The member can upload a new one.');
    }

    public function extend(Request $request, Reservation $reservation, PaymentService $svc)
    {
        $d = $request->validate(['due' => ['required', 'date', 'after:today']]);
        $due = \Illuminate\Support\Carbon::parse($d['due'].' 23:59:59', 'America/New_York')->utc();
        $svc->extendDue($reservation, $due, $request->user());

        return back()->with('toast', 'Balance due date moved.');
    }

    public function cancel(Request $request, Reservation $reservation, PaymentService $svc)
    {
        abort_if($reservation->cancelled_at || $reservation->status === 'completed', 422);
        $svc->cancel($reservation, $request->user());

        return back()->with('toast', 'Reservation cancelled.');
    }

    public function resend(Request $request, Invoice $invoice, ReservationService $svc)
    {
        abort_unless($invoice->status === 'open', 422);
        $ok = $svc->email($invoice);

        return back()->with($ok ? 'toast' : 'error', $ok ? 'Invoice sent again.' : 'The email could not be sent. Check the mail settings.');
    }
}
