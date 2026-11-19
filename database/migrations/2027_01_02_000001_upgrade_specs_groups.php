<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** The earlier default text of the Specifications section. Only a section still holding exactly this is upgraded; text an admin changed is left alone. */
    private const OLD = <<<'T'
Every figure for the pioneer edition. Specifications are targets and may change before delivery.

## Dimensions
- Length: 6.1 m
- Wingspan: 9.2 m
- Folded width: 2.4 m
- Height: 2.1 m
- Cabin width: 1.25 m
- Luggage volume: 90 litres

## Weights
- Empty weight: 760 kg
- Payload: 240 kg
- Maximum takeoff weight: 1,000 kg
- Seats: 2

## Performance
- Top speed: 280 km/h
- Cruise speed: 240 km/h
- Range: 320 km, with reserve
- Service ceiling: 3,000 m
- Vertical climb: 4 m/s
- Forward climb: 8 m/s

## Power
- Battery: 120 kWh usable, two packs
- Lift rotors: 8
- Propeller: 1, rear
- Fast charge, 10 to 80%: 35 min
- Home charge: 22 kW, 6 hours

## Safety
- Rotor redundancy: Any 2 of 8
- Flight computers: 3, voting
- Parachute: Whole-aircraft
- Traffic: Broadcast and receive

## Ownership
- Warranty: 4 years
- Battery warranty: 8 years
- Delivery: 18 to 24 months
- Price: Pioneer allocation, see your dashboard
T;

    public function up(): void
    {
        $new = config('vehicle.sections.specs.body');
        $row = DB::table('page_sections')->where('slug', 'specs')->first();
        if ($row && $new && trim(str_replace("\r\n", "\n", (string) $row->body)) === trim(self::OLD)) {
            DB::table('page_sections')->where('slug', 'specs')->update(['body' => $new, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
    }
};
