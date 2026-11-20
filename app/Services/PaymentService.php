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
        $this->notice($user, 'We received your proof of wire', ['Thank you. We will confirm that your wire has arrived in the escrow account and email you as soon as it is recorded.']);

        return $payment;
    }

    /** Confirms a wire arrived in escrow, on behalf of the escrow agent. A verified deposit does not start the balance clock; the milestone does. */
    public function verify(Payment $payment, User $admin): void
    {
        abort_unless($payment->status === 'pending', 422);
        $invoice = $payment->invoice;
        $r = $payment->reservation()->first();

        DB::transaction(function () use ($payment, $admin, $invoice, $r) {
            $payment->update(['status' => 'verified', 'verified_by' => $admin->id, 'verified_at' => now()]);
            $invoice->update(['status' => 'paid']);
            $r->escrowEntries()->create(['event' => 'received', 'amount_cents' => $payment->amount_cents, 'user_id' => $admin->id, 'note' => $invoice->title()]);
            if ($invoice->kind === 'deposit') {
                $r->update(['status' => 'in_escrow', 'deposit_verified_at' => now()]);
            } else {
                $r->update(['status' => 'completed', 'balance_verified_at' => now()]);
            }
        });
        Audit::record('payment.verified', $payment, null, ['invoice' => $invoice->number], $admin->id);
        $invite = $r->invite;
        $agent = Escrow::agent();
        if ($invoice->kind === 'deposit') {
            $this->invites->log('deposit_paid', $invite, $r->user, $r->id);
            $this->notice($r->user, 'Your deposit is held in escrow', [
                'Your wire has arrived and your deposit is now held in escrow by '.$agent.'. It is not placed in '.brand('name').'\'s operating funds.',
                'Your next step comes when your allocation milestone is confirmed. We will email your balance invoice then, and you will have '.Pricing::balanceDays().' days to pay it.',
            ]);
        } else {
            $this->invites->log('balance_paid', $invite, $r->user, $r->id);
            $this->notice($r->user, 'Your balance is received', ['Thank you. Your pioneer allocation is paid in full and held in escrow by '.$agent.' until settlement with '.brand('name').' is coordinated.', 'We will be in touch about the next steps.']);
        }
    }

    /** An admin confirms the allocation milestone: the balance invoice is issued and its days-to-pay clock starts. */
    public function confirmMilestone(Reservation $r, User $admin, ?string $note = null): Invoice
    {
        abort_unless($r->status === 'in_escrow' && ! $r->cancelled_at && ! $r->milestone_confirmed_at, 422);
        $due = now()->addDays(Pricing::balanceDays());
        $invoice = DB::transaction(function () use ($r, $admin, $due) {
            $r->update(['status' => 'balance_invoiced', 'milestone_confirmed_at' => now(), 'milestone_confirmed_by' => $admin->id, 'balance_due_at' => $due]);

            return $this->reservations->issue($r, 'balance', $r->balance_cents, $due);
        });
        Audit::record('reservation.milestone_confirmed', $r, null, ['note' => $note], $admin->id);
        $this->invites->log('milestone_confirmed', $r->invite, $r->user, $r->id);
        $this->reservations->email($invoice->fresh());

        return $invoice;
    }

    /** Funds held in escrow are released to the company (settlement). Only after the milestone is confirmed. */
    public function release(Reservation $r, User $admin, string $note): void
    {
        $r->load('escrowEntries');
        $held = $r->heldCents();
        abort_unless($r->milestone_confirmed_at && ! $r->cancelled_at && $held > 0, 422);
        $r->escrowEntries()->create(['event' => 'released', 'amount_cents' => $held, 'user_id' => $admin->id, 'note' => $note]);
        $r->update(['released_at' => now()]);
        Audit::record('reservation.funds_released', $r, null, ['cents' => $held, 'note' => $note], $admin->id);
        $this->notice($r->user, 'Your funds have been released to '.brand('name'), ['The funds held in escrow for your allocation have been released to '.brand('name').', as set out in your agreement.']);
    }

    /** A plain message to the member, written by an admin (a delay, a new date, anything they need to know). */
    public function sendUpdate(Reservation $r, User $admin, string $message): void
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R{1,}/', $message))));
        $this->notice($r->user, 'An update on your allocation', $lines);
        Audit::record('reservation.update_sent', $r, null, null, $admin->id);
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

    /**
     * The admin decides what happens to funds held in escrow when a reservation will not go ahead:
     * 'none' (nothing was received), 'refund' (return it to the member) or 'release' (release it to the company under the agreement).
     */
    public function cancel(Reservation $r, User $admin, string $outcome = 'none', ?string $note = null): void
    {
        $r->load('escrowEntries');
        $held = $r->heldCents();
        abort_if($r->cancelled_at || $r->status === 'completed' && $held === 0, 422);
        abort_unless(in_array($outcome, ['none', 'refund', 'release'], true) && ($held > 0) === ($outcome !== 'none'), 422);

        DB::transaction(function () use ($r, $admin, $outcome, $note, $held) {
            if ($outcome === 'refund') {
                $r->escrowEntries()->create(['event' => 'refunded', 'amount_cents' => $held, 'user_id' => $admin->id, 'note' => $note]);
            } elseif ($outcome === 'release') {
                $r->escrowEntries()->create(['event' => 'released', 'amount_cents' => $held, 'user_id' => $admin->id, 'note' => $note]);
            }
            $r->update([
                'status' => 'cancelled', 'cancelled_at' => now(),
                'outcome' => ['none' => 'cancelled', 'refund' => 'cancelled_refunded', 'release' => 'cancelled_released'][$outcome],
                'refunded_at' => $outcome === 'refund' ? now() : $r->refunded_at, 'released_at' => $outcome === 'release' ? now() : $r->released_at,
            ]);
            $r->invoices()->where('status', 'open')->update(['status' => 'void']);
            $r->payments()->where('status', 'pending')->update(['status' => 'rejected', 'note' => 'Reservation cancelled']);
        });
        Audit::record('reservation.cancelled', $r, null, ['outcome' => $outcome, 'cents' => $held, 'note' => $note], $admin->id);
        match ($outcome) {
            'refund' => $this->notice($r->user, 'Your reservation is cancelled and your funds are being returned', ['Your reservation has been cancelled. The funds held in escrow for it are being returned to you.', 'Please allow a few business days for the wire to reach your bank.']),
            'release' => $this->notice($r->user, 'Your reservation has been cancelled', ['Your reservation has been cancelled. The funds held in escrow have been released as set out in your agreement.', 'Contact us if you have questions about this.']),
            default => $this->notice($r->user, 'Your reservation has been cancelled', ['Your reservation has been cancelled. No funds were received, so nothing is owed or held.']),
        };
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
