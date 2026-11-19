<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/** Proof of wire, staff verification, and what verification sets in motion (the balance invoice and its due date). */
final class PaymentService
{
    public function __construct(private ReservationService $reservations, private InviteService $invites, private SecureUpload $uploads)
    {
    }

    public function submitProof(User $user, Invoice $invoice, UploadedFile $file): Payment
    {
        $r = $invoice->reservation;
        if ($r->user_id !== $user->id || $r->cancelled_at || $invoice->status !== 'open') {
            throw ValidationException::withMessages(['proof' => 'This invoice is not open for payment.']);
        }
        if ($invoice->payments()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['proof' => 'Your proof is already waiting for review.']);
        }
        $stored = $this->uploads->store($file, 'proofs', 'proof', true);
        $payment = Payment::create([
            'reservation_id' => $r->id, 'invoice_id' => $invoice->id, 'method' => 'wire', 'amount_cents' => $invoice->amount_cents,
            'status' => 'pending', 'proof_path' => $stored['path'], 'proof_name' => $stored['name'], 'submitted_at' => now(),
        ]);
        $r->update(['status' => $invoice->kind === 'deposit' ? 'proof_submitted' : 'balance_proof_submitted']);
        if ($invoice->kind === 'deposit') {
            $this->invites->log('proof_uploaded', $r->invite, $user, $r->id);
        }
        Audit::record('payment.proof_submitted', $payment, null, ['invoice' => $invoice->number], $user->id);

        return $payment;
    }

    public function verify(Payment $payment, User $admin): void
    {
        abort_unless($payment->status === 'pending', 422);
        $invoice = $payment->invoice;
        $r = $payment->reservation()->first();
        $balanceInvoice = null;

        DB::transaction(function () use ($payment, $admin, $invoice, $r, &$balanceInvoice) {
            $payment->update(['status' => 'verified', 'verified_by' => $admin->id, 'verified_at' => now()]);
            $invoice->update(['status' => 'paid']);
            if ($invoice->kind === 'deposit') {
                // The balance clock starts now, when the deposit is verified, not when the member sent the wire.
                $due = now()->addDays(Pricing::balanceDays());
                $r->update(['status' => 'balance_invoiced', 'deposit_verified_at' => now(), 'balance_due_at' => $due]);
                $balanceInvoice = $this->reservations->issue($r, 'balance', $r->balance_cents, $due);
            } else {
                $r->update(['status' => 'completed', 'balance_verified_at' => now()]);
            }
        });
        Audit::record('payment.verified', $payment, null, ['invoice' => $invoice->number], $admin->id);
        $invite = $r->invite;
        if ($invoice->kind === 'deposit') {
            $this->invites->log('deposit_paid', $invite, $r->user, $r->id);
            if ($balanceInvoice) {
                $this->reservations->email($balanceInvoice->fresh());
            }
        } else {
            $this->invites->log('balance_paid', $invite, $r->user, $r->id);
            $this->notice($r->user, 'Your balance is verified', ['Thank you. Your pioneer allocation is paid in full.', 'We will be in touch about delivery.']);
        }
    }

    public function reject(Payment $payment, User $admin, string $note): void
    {
        abort_unless($payment->status === 'pending', 422);
        $payment->update(['status' => 'rejected', 'verified_by' => $admin->id, 'verified_at' => now(), 'note' => $note]);
        $r = $payment->reservation()->first();
        $r->update(['status' => $payment->invoice->kind === 'deposit' ? 'deposit_invoiced' : 'balance_invoiced']);
        Audit::record('payment.rejected', $payment, null, ['invoice' => $payment->invoice->number], $admin->id);
        $this->notice($r->user, 'We could not verify your proof of wire', ['Sign in to your dashboard to see what we need, then upload a new proof.']);
    }

    public function extendDue(Reservation $r, \DateTimeInterface $due, User $admin): void
    {
        abort_unless($r->balance_due_at && ! $r->balance_verified_at, 422);
        $r->update(['balance_due_at' => $due]);
        $r->invoices()->where('kind', 'balance')->update(['due_at' => $due]);
        $r->reminders()->delete();
        Audit::record('reservation.due_extended', $r, null, ['due' => $due->format('Y-m-d')], $admin->id);
        $this->notice($r->user, 'Your balance due date has changed', ['Sign in to your dashboard to see your new due date.']);
    }

    public function cancel(Reservation $r, User $admin): void
    {
        $r->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $r->invoices()->where('status', 'open')->update(['status' => 'void']);
        $r->payments()->where('status', 'pending')->update(['status' => 'rejected', 'note' => 'Reservation cancelled']);
        Audit::record('reservation.cancelled', $r, null, null, $admin->id);
    }

    /** A short plain email: a heading and a few lines, no amounts and no links. */
    public function notice(User $user, string $heading, array $lines): void
    {
        try {
            Mail::send('mail.notice', ['name' => $user->name, 'heading' => $heading, 'lines' => $lines], fn ($m) => $m->to($user->email)->subject($heading));
        } catch (\Throwable $e) {
            Log::warning('Notice email failed: '.$e->getMessage());
        }
    }
}
