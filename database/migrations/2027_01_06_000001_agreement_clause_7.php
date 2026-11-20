<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $latest = DB::table('agreement_versions')->orderByDesc('version')->first();
        $old = '7. Delivery. [Delivery timing and conditions go here.]';
        if (! $latest || ! str_contains($latest->body, $old)) return;

        $body = str_replace($old, '7. Delivery. {company} plans to deliver your {vehicle} by the end of 2028. If this date changes, we will tell you by email and on your dashboard.', $latest->body);
        DB::table('agreement_versions')->insert(['version' => $latest->version + 1, 'body' => $body, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void {}
};
