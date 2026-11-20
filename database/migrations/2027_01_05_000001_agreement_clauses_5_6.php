<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $latest = DB::table('agreement_versions')->orderByDesc('version')->first();
        if (! $latest) return;

        $five = '5. If the balance is not paid. [Terms for a late or missed balance go here, including whether {company} may extend the deadline.]';
        $six = '6. Cancelling and refunds. [Terms for cancelling, refunds and what happens if the milestone is not reached go here.]';
        if (! str_contains($latest->body, $five) || ! str_contains($latest->body, $six)) return;

        $body = str_replace(
            [$five, $six],
            [
                '5. If the balance is not paid. If the balance has not been received by its due date, {company} will give you an additional due date. We will tell you the new date by email and on your dashboard.',
                '6. Cancelling and refunds. You may cancel your reservation. If you cancel, everything you have sent is refunded to you in full.',
            ],
            $latest->body
        );
        DB::table('agreement_versions')->insert(['version' => $latest->version + 1, 'body' => $body, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void {}
};
