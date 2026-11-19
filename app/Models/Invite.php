<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invite extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime', 'revoked_at' => 'datetime', 'locked_until' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->status !== 'revoked';
    }
}
