<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = <<<'T'
Vertical takeoff, wing-borne cruise

Eight lift rotors raise the vehicle straight up from a pad about the size of a parking space. At a safe height the rear propeller takes over, the aircraft accelerates, and the wing begins to carry the weight. The lift rotors then stop and fold away, and from that point the vehicle flies on far less energy, which is where the range comes from.

You fly it with a single stick and a throttle lever. The flight computer handles the transition between hover and cruise, holds your heading and altitude when you let go, and limits the aircraft to its safe envelope. Landing is the same sequence in reverse, and an assisted landing mode can bring you down on the marked pad on its own.
T;

    private const NEW = <<<'T'
Four ducts at the tail, one clean line above them

Seen from above, the tail shows how the vehicle flies. Four ducted outlets sit under a carbon-fibre diffuser, with a single continuous light bar running above them. After a vertical takeoff from a pad about the size of a parking space, these rear ducts push the aircraft forward, the wing begins to carry the weight, and the lift rotors stop and fold away. From that point the vehicle flies on far less energy, which is where the range comes from.

You fly it with a single stick and a throttle lever. The flight computer handles the transition between hover and cruise, holds your heading and altitude when you let go, and limits the aircraft to its safe envelope. Landing is the same sequence in reverse, and an assisted landing mode can bring you down on the marked pad on its own.
T;

    /** The flight card text now describes the tail picture. Text an admin has edited is left alone. */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'flight')->value('body');
        if ($body && str_contains($body, self::OLD)) {
            DB::table('page_sections')->where('slug', 'flight')->update(['body' => str_replace(self::OLD, self::NEW, $body)]);
        }
    }

    public function down(): void {}
};
