<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Small key/value settings a Super Admin can change. Falls back to config when unset. */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function bool(string $key, bool $default): bool
    {
        $row = static::find($key);

        return $row ? $row->value === '1' : $default;
    }

    public static function put(string $key, bool $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
    }

    public static function value(string $key, string|int $default): string
    {
        return (string) (static::find($key)?->value ?? $default);
    }

    public static function set(string $key, string|int $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
