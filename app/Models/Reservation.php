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
        return ['agreed_at' => 'datetime', 'deposit_verified_at' => 'datetime', 'balance_due_at' => 'datetime', 'balance_verified_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public const STATUS = [
        'deposit_invoiced' => 'Deposit invoiced', 'proof_submitted' => 'Proof of wire submitted', 'deposit_paid' => 'Deposit verified',
        'balance_invoiced' => 'Balance invoiced', 'balance_proof_submitted' => 'Balance proof submitted', 'completed' => 'Paid in full', 'cancelled' => 'Cancelled',
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

    public function invite(): BelongsTo
    {
        return $this->belongsTo(Invite::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }
}
