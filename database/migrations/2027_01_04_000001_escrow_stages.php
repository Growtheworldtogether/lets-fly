<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Escrow and staged payments: milestone confirmation, an escrow ledger, and reworded defaults (changed only if still unedited). */
return new class extends Migration
{
    private const OLD_AGREEMENT = <<<'TXT'
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

    private const OLD_FOOTER = '[Company Name] is an independent portfolio project and is not a licensed financial institution. No deposits are accepted and no accounts are insured.';

    private const OLD_DELIVERY = 'Your allocation is reserved with a deposit, and the balance is due within ten days after we verify the deposit.';

    private const NEW_DELIVERY = 'Your allocation is reserved with a deposit that is held in escrow, and the balance is due within ten days after we confirm your allocation milestone.';

    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $t) {
            $t->timestamp('milestone_confirmed_at')->nullable();
            $t->foreignId('milestone_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('released_at')->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->string('outcome', 24)->nullable();
        });

        Schema::create('escrow_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $t->string('event', 12);                 // received | released | refunded
            $t->unsignedBigInteger('amount_cents');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('note', 300)->nullable();
            $t->timestamps();
        });

        // Reservations made before this change: the balance was invoiced automatically, so treat the milestone as confirmed then.
        foreach (DB::table('reservations')->whereNotNull('deposit_verified_at')->get() as $r) {
            DB::table('reservations')->where('id', $r->id)->update(['milestone_confirmed_at' => $r->deposit_verified_at]);
            DB::table('escrow_entries')->insert(['reservation_id' => $r->id, 'event' => 'received', 'amount_cents' => $r->deposit_cents, 'note' => 'Carried over', 'created_at' => $r->deposit_verified_at, 'updated_at' => $r->deposit_verified_at]);
            if ($r->balance_verified_at) {
                DB::table('escrow_entries')->insert(['reservation_id' => $r->id, 'event' => 'received', 'amount_cents' => $r->balance_cents, 'note' => 'Carried over', 'created_at' => $r->balance_verified_at, 'updated_at' => $r->balance_verified_at]);
            }
        }

        if (DB::table('settings')->where('key', 'footer_text')->value('value') === self::OLD_FOOTER) {
            DB::table('settings')->where('key', 'footer_text')->update(['value' => '']);
        }

        $latest = DB::table('agreement_versions')->orderByDesc('version')->first();
        if ($latest && trim($latest->body) === trim(self::OLD_AGREEMENT)) {
            DB::table('agreement_versions')->insert(['version' => $latest->version + 1, 'body' => \App\Models\AgreementVersion::DEFAULT_BODY, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }

        $body = DB::table('page_sections')->where('slug', 'delivery')->value('body');
        if ($body && str_contains($body, self::OLD_DELIVERY)) {
            DB::table('page_sections')->where('slug', 'delivery')->update(['body' => str_replace(self::OLD_DELIVERY, self::NEW_DELIVERY, $body)]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_entries');
        Schema::table('reservations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('milestone_confirmed_by');
            $t->dropColumn(['milestone_confirmed_at', 'released_at', 'refunded_at', 'outcome']);
        });
    }
};
