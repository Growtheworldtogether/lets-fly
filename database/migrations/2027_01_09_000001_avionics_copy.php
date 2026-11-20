<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = <<<'T'
A flight deck that looks after you

A 15-inch glass display shows your route, altitude, speed, battery state and traffic on a single screen. A second, smaller display shows the same essentials on a separate circuit, so a failure of one never leaves you without instruments.

Three independent flight computers vote on every control command. If one disagrees with the other two it is shut out and the flight continues. The vehicle broadcasts its position to other aircraft and receives theirs, and warns you early if another aircraft's path crosses yours.
T;

    private const NEW = <<<'T'
Doors up, flight deck ready

Two gull-wing doors lift clear of the cabin and stay open on their own, and the wide canopy lets daylight fall across the whole flight deck. A 15-inch glass display shows your route, altitude, speed, battery state and traffic on a single screen. A second, smaller display shows the same essentials on a separate circuit, so a failure of one never leaves you without instruments.

Three independent flight computers vote on every control command. If one disagrees with the other two it is shut out and the flight continues. The vehicle broadcasts its position to other aircraft and receives theirs, and warns you early if another aircraft's path crosses yours.
T;

    /** The avionics card text now describes the open-doors picture. Text an admin has edited is left alone. */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'avionics')->value('body');
        if ($body && str_contains($body, self::OLD)) {
            DB::table('page_sections')->where('slug', 'avionics')->update(['body' => str_replace(self::OLD, self::NEW, $body)]);
        }
    }

    public function down(): void {}
};
