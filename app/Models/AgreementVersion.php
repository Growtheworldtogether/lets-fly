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

1. What you are reserving. You are reserving one pioneer allocation of the {vehicle} from {company}, at the pioneer price of {price}. The projected public price in 2028 is {public_price}. Your pioneer price is fixed once your deposit is received in escrow.

2. How you pay. You pay in two stages by wire transfer. First, a deposit of {deposit}. Then the balance of {balance}, which is due within {days} days after {company} confirms your allocation milestone and sends you the balance invoice. We will tell you the exact balance date when that invoice is sent.

3. Escrow. Your deposit is not placed in {company}'s operating funds. It is held in trust by our independent escrow agent, {escrow_agent}, until your allocation terms advance, at which point settlement is coordinated with {company}. Funds you send for the balance are held the same way. {company} confirms receipts and milestones on the escrow agent's behalf.

4. Proof of payment. After you send a wire, use your reservation code as the reference and upload proof of it in your dashboard. Your payment counts only once it is confirmed as received in escrow.

5. If the balance is not paid. [Terms for a late or missed balance go here, including whether {company} may extend the deadline.]

6. Cancelling and refunds. [Terms for cancelling, refunds and what happens if the milestone is not reached go here.]

7. Delivery. [Delivery timing and conditions go here.]

8. About {company}. {company} reserves pioneer allocations of the {vehicle}. Funds are held in escrow as described above. Specifications, timelines and delivery dates are targets and may change.

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
            '{balance}' => $f($p::balance()), '{days}' => (string) $p::balanceDays(), '{escrow_agent}' => \App\Services\Escrow::agent(),
        ]);
    }
}
