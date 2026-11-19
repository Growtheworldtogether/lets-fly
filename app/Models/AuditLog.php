<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['old' => 'array', 'new' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit rows cannot be edited.'));
        static::deleting(fn () => throw new LogicException('Audit rows cannot be deleted.'));
    }
}
