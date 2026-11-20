<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = <<<'T'
Shaped by the air, finished by hand

The airframe is a single carbon-fibre monocoque, cured in one piece so there are no panel seams to break up the airflow. The result is a surface that is smooth to the touch and measurably quiet in the air. Four slim booms carry eight lift rotors, and each boom folds flush with the body once the aircraft is in forward flight.

The wing folds in under a minute. With the wing stowed, the vehicle is narrow enough to roll into a standard two-car garage, so it can live at home instead of at an airfield.
T;

    private const NEW = <<<'T'
A face you know from a long way off

From the front, the {vehicle} is built from flat planes and sharp creases, with no curves added for decoration. The whole airframe is one carbon-fibre monocoque, cured in a single piece, so there are no panel seams to break up the airflow.

Two slim lamps and a single light bar across the nose are the only detail on the face. In the air they double as navigation lights. Wide glass under a low roof gives the pilot a clear view forward, and the low, wide stance keeps the vehicle steady on the ground. With the wing folded in under a minute, it is narrow enough to roll into a standard two-car garage.
T;

    /** The exterior card text now describes the front-view picture. Text an admin has edited is left alone. */
    public function up(): void
    {
        $body = DB::table('page_sections')->where('slug', 'exterior')->value('body');
        if ($body && str_contains($body, self::OLD)) {
            DB::table('page_sections')->where('slug', 'exterior')->update(['body' => str_replace(self::OLD, self::NEW, $body)]);
        }
    }

    public function down(): void {}
};
