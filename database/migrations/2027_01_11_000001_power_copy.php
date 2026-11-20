<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = <<<'T'
Two batteries, one purpose

The vehicle carries two separate battery packs.
T;

    private const NEW = <<<'T'
Two batteries, one purpose

Under the skin, the frame, the running gear and the power system are built around one rule: no single failure should leave you without a safe way down. The vehicle carries two separate battery packs.
T;

    /** The power card text now describes the cutaway picture. Text an admin has edited is left alone. */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'power')->value('body');
        if ($body && str_contains($body, self::OLD)) {
            DB::table('page_sections')->where('slug', 'power')->update(['body' => str_replace(self::OLD, self::NEW, $body)]);
        }
    }

    public function down(): void {}
};
