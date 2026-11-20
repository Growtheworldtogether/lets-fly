<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['agreed_at' => 'datetime', 'deposit_verified_at' => 'datetime', 'balance_due_at' => 'datetime', 'balance_verified_at' => 'datetime', 'cancelled_at' => 'datetime', 'milestone_confirmed_at' => 'datetime', 'released_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    public const STATUS = [
        'deposit_invoiced' => 'Deposit invoiced', 'proof_submitted' => 'Proof of wire submitted', 'in_escrow' => 'Deposit held in escrow', 'deposit_paid' => 'Deposit held in escrow',
        'balance_invoiced' => 'Milestone confirmed, balance invoiced', 'balance_proof_submitted' => 'Balance proof submitted', 'completed' => 'Paid in full', 'cancelled' => 'Cancelled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function milestoneConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'milestone_confirmed_by');
    }

    public function escrowEntries(): HasMany
    {
        return $this->hasMany(EscrowEntry::class)->orderBy('id');
    }

    /** Funds currently held in escrow for this reservation: received, less anything released or refunded. */
    public function heldCents(): int
    {
        return (int) $this->escrowEntries->sum(fn ($e) => $e->event === 'received' ? $e->amount_cents : -$e->amount_cents);
    }

    public function escrowLabel(): string
    {
        $held = $this->heldCents();
        if ($held > 0) {
            return 'Held in escrow';
        }
        if ($this->refunded_at) {
            return 'Refunded';
        }
        if ($this->escrowEntries->where('event', 'released')->isNotEmpty()) {
            return 'Released to company';
        }

        return 'No funds yet';
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(Invite::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }
}
