<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public const DEFAULT_BODY = <<<'TXT'
Pioneer allocation agreement

1. What you are reserving. You are reserving one pioneer allocation of the {vehicle} from {company}, at the pioneer price of {price}. The projected public price in 2028 is {public_price}. Your pioneer price is fixed once your deposit is verified.

2. How you pay. You pay in two installments by wire transfer: a deposit of {deposit} now, and the balance of {balance} within {days} days after we verify your deposit. We will tell you the exact balance date as soon as your deposit is verified.

3. Proof of payment. After you send a wire, upload proof of it in your dashboard. Your payment counts only once {company} verifies it.

4. If the balance is not paid. [Terms for a late or missed balance go here.]

5. Cancelling. [Terms for cancelling and refunds go here.]

6. Delivery. [Delivery timing and conditions go here.]

7. About {company}. {company} is an independent portfolio project and is not a licensed financial institution. No deposits are accepted and no accounts are insured.

By accepting, you confirm that you have read this agreement and agree to it.
TXT;

    public static function current(): self
    {
        return static::orderByDesc('version')->first() ?? static::create(['version' => 1, 'body' => self::DEFAULT_BODY, 'published_at' => now()]);
    }

    /** The agreement text with the live names and amounts filled in. */
    public function rendered(): string
    {
        $p = \App\Services\Pricing::class;
        $f = fn (int $c) => \App\Support\Money::cents($c)->format();

        return strtr($this->body, [
            '{company}' => brand('name'), '{vehicle}' => brand('vehicle'),
            '{price}' => $f($p::price()), '{public_price}' => $f($p::publicPrice()), '{deposit}' => $f($p::deposit()),
            '{balance}' => $f($p::balance()), '{days}' => (string) $p::balanceDays(),
        ]);
    }
}
