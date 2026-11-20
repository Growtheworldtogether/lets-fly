<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = <<<'T'
A cabin that feels like a quiet room

Two seats sit side by side in a cabin with a wide, curved canopy and clear views up and down. The seats are heated, adjust in six directions and can be set up for each pilot with a memory profile. Wide doors open upward and stay open on their own, so getting in is easy even in tight spaces.
T;

    private const NEW = <<<'T'
A dark, quiet cabin with two pale seats

Two seats sit side by side under a wide, curved canopy, facing a single central display. The seats are pale against a dark cabin, with a soft red glow running along each door. They are heated, adjust in six directions and can be set up for each pilot with a memory profile. Wide doors open upward and stay open on their own, so getting in is easy even in tight spaces.
T;

    /** The interior card text now describes the cabin picture. Text an admin has edited is left alone. */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'interior')->value('body');
        if ($body && str_contains($body, self::OLD)) {
            DB::table('page_sections')->where('slug', 'interior')->update(['body' => str_replace(self::OLD, self::NEW, $body)]);
        }
    }

    public function down(): void {}
};
