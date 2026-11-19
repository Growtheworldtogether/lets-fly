<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'due_at' => 'datetime', 'emailed_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function title(): string
    {
        return $this->kind === 'deposit' ? 'Installment 1: Reservation deposit' : 'Installment 2: Balance';
    }
}
