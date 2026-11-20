<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = <<<'T'
Quick to climb, efficient to cruise

Vertical lift is the most demanding part of any flight, so the rotors and battery are sized for it with margin to spare. Once the wing takes over, cruising needs only a fraction of that power. The vehicle is tuned to spend most of its flight in that efficient state.
T;

    private const NEW = <<<'T'
Ready on the pad, quick to the sky

Doors up, pilot in, and the vehicle lifts straight off its pad. Vertical lift is the most demanding part of any flight, so the rotors and battery are sized for it with margin to spare. Once the wing takes over, cruising needs only a fraction of that power, and the vehicle spends most of its flight in that efficient state.
T;

    /** The performance text now describes the night picture. Text an admin has edited is left alone. */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'performance')->value('body');
        if ($body && str_contains($body, self::OLD)) {
            DB::table('page_sections')->where('slug', 'performance')->update(['body' => str_replace(self::OLD, self::NEW, $body)]);
        }
    }

    public function down(): void {}
};
