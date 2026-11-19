<?php

namespace App\Services;

use App\Models\Setting;

/** The money terms, from admin settings with built-in defaults. All amounts are integer cents. */
final class Pricing
{
    public static function price(): int { return (int) Setting::value('price_cents', config('pioneer.price_cents')); }
    public static function publicPrice(): int { return (int) Setting::value('public_price_cents', config('pioneer.public_price_cents')); }
    public static function deposit(): int { return (int) Setting::value('deposit_cents', config('pioneer.deposit_cents')); }
    public static function balance(): int { return self::price() - self::deposit(); }
    public static function balanceDays(): int { return (int) Setting::value('balance_days', config('pioneer.balance_days')); }
}
