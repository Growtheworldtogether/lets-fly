<?php

namespace App\Services;

use App\Models\AgreementVersion;
use App\Models\Invite;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class ReservationService
{
    public function __construct(private InviteService $invites)
    {
    }

    public function active(User $user): ?Reservation
    {
        return Reservation::where('user_id', $user->id)->whereNull('cancelled_at')->latest('id')->first();
    }

    /** Records acceptance of the agreement, creates the reservation and the deposit invoice, and emails it. */
    public function reserve(User $user, string $ip): Reservation
    {
        $agreement = AgreementVersion::current();
        $invite = Invite::where('user_id', $user->id)->first();

        $reservation = DB::transaction(function () use ($user, $ip, $agreement, $invite) {
            $reservation = Reservation::create([
                'user_id' => $user->id, 'invite_id' => $invite?->id, 'reference' => $this->reference(),
                'status' => 'deposit_invoiced',
                'price_cents' => Pricing::price(), 'deposit_cents' => Pricing::deposit(), 'balance_cents' => Pricing::balance(),
                'agreement_version' => $agreement->version, 'agreed_at' => now(), 'agreed_ip' => $ip,
            ]);
            $this->issue($reservation, 'deposit', $reservation->deposit_cents, null);

            return $reservation;
        });
        $this->invites->log('agreed', $invite, $user, $reservation->id);
        $this->invites->log('invoiced', $invite, $user, $reservation->id);
        Audit::record('reservation.created', $reservation, null, ['reference' => $reservation->reference], $user->id);
        $this->email($reservation->invoices()->first());

        return $reservation;
    }

    public function issue(Reservation $r, string $kind, int $cents, ?\DateTimeInterface $due): Invoice
    {
        $invoice = Invoice::create([
            'reservation_id' => $r->id, 'kind' => $kind, 'number' => 'TMP'.Str::random(12),
            'amount_cents' => $cents, 'issued_at' => now(), 'due_at' => $due,
        ]);
        $invoice->update(['number' => 'INV-'.now()->format('Y').'-'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT)]);

        return $invoice;
    }

    /** Sends the invoice email. A mail failure never blocks the reservation; the invoice can be resent. */
    public function email(Invoice $invoice): bool
    {
        $r = $invoice->reservation()->with('user')->first();
        try {
            Mail::send('mail.invoice', ['invoice' => $invoice, 'r' => $r, 'user' => $r->user, 'lines' => Escrow::lines($r), 'notes' => Escrow::notes()], function ($m) use ($r, $invoice) {
                $m->to($r->user->email)->subject('Your invoice '.$invoice->number);
            });
            $invoice->update(['emailed_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invoice email failed: '.$e->getMessage());

            return false;
        }
    }

    private function reference(): string
    {
        do {
            $ref = 'RSV-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (Reservation::where('reference', $ref)->exists());

        return str_replace(['0', 'O', '1', 'I'], ['7', 'K', '8', 'L'], $ref);
    }
}
