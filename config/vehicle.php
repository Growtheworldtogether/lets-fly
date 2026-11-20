<?php

/*
 * Default text for the vehicle page. Every section can be rewritten in Admin > Vehicle pages.
 *
 * Writing format used by the page:
 *   - Blank-line separated paragraphs.
 *   - "- Label: value" lines become the key-fact grid for that section.
 *   - On the Specifications section, "## Group name" starts a new table group.
 *   - {vehicle} is replaced by the vehicle's name from Admin > Settings.
 *
 * IMPORTANT: every number below is a sample figure for the build. Replace each one with the
 * real figure before the site is shown to anyone who might rely on it.
 */

return [
    'sections' => [
        'showcase' => [
            'title' => '[Vehicle Name]', 'sort' => 0,
            'summary' => 'Secure your place among the pioneers of personal flight.',
            'body' => <<<'T'
- Range: 320 km
- Cruise speed: 240 km/h
- Seats: 2
- Fast charge: 35 min
T,
        ],
        'intro' => [
            'title' => 'Built around the pilot', 'sort' => 5,
            'summary' => 'A two-seat electric aircraft for one owner and their own landing pad.',
            'body' => <<<'T'
{vehicle} is a two-seat electric aircraft that takes off and lands vertically, then flies on its wing like a conventional aircraft. It needs no runway, produces no exhaust and is designed to be flown by one owner from their own landing pad.

Pioneer allocations are limited, invite-only and delivered in the order they are reserved. Everything on this page describes the pioneer edition.
T,
        ],
        'exterior' => [
            'title' => 'Exterior and design', 'sort' => 10,
            'summary' => 'The shape, the materials and the details you see first.',
            'body' => <<<'T'
A face you know from a long way off

From the front, the {vehicle} is built from flat planes and sharp creases, with no curves added for decoration. The whole airframe is one carbon-fibre monocoque, cured in a single piece, so there are no panel seams to break up the airflow.

Two slim lamps and a single light bar across the nose are the only detail on the face. In the air they double as navigation lights. Wide glass under a low roof gives the pilot a clear view forward, and the low, wide stance keeps the vehicle steady on the ground. With the wing folded in under a minute, it is narrow enough to roll into a standard two-car garage.

- Construction: Carbon-fibre monocoque
- Length: 6.1 m
- Wingspan: 9.2 m
- Folded width: 2.4 m
- Height: 2.1 m
- Finish: 7-layer aerospace paint, 6 colours
- Lighting: Integrated LED navigation and landing lights
- Doors: Two gull-wing, hands-free
T,
        ],
        'flight' => [
            'title' => 'Flight and handling', 'sort' => 20,
            'summary' => 'How it takes off, cruises and lands.',
            'body' => <<<'T'
Four ducts at the tail, one clean line above them

Seen from above, the tail shows how the vehicle flies. Four ducted outlets sit under a carbon-fibre diffuser, with a single continuous light bar running above them. After a vertical takeoff from a pad about the size of a parking space, these rear ducts push the aircraft forward, the wing begins to carry the weight, and the lift rotors stop and fold away. From that point the vehicle flies on far less energy, which is where the range comes from.

You fly it with a single stick and a throttle lever. The flight computer handles the transition between hover and cruise, holds your heading and altitude when you let go, and limits the aircraft to its safe envelope. Landing is the same sequence in reverse, and an assisted landing mode can bring you down on the marked pad on its own.

- Takeoff and landing: Vertical, 12 m pad
- Transition: Automatic, about 25 seconds
- Control: Single stick and throttle
- Hands-off hold: Heading, altitude and speed
- Assisted landing: Pad recognition by camera
- Wind limit: 35 km/h gusting
T,
        ],
        'avionics' => [
            'title' => 'Avionics and autopilot', 'sort' => 25,
            'summary' => 'The systems that see, decide and help you fly.',
            'body' => <<<'T'
Doors up, flight deck ready

Two gull-wing doors lift clear of the cabin and stay open on their own, and the wide canopy lets daylight fall across the whole flight deck. A 15-inch glass display shows your route, altitude, speed, battery state and traffic on a single screen. A second, smaller display shows the same essentials on a separate circuit, so a failure of one never leaves you without instruments.

Three independent flight computers vote on every control command. If one disagrees with the other two it is shut out and the flight continues. The vehicle broadcasts its position to other aircraft and receives theirs, and warns you early if another aircraft's path crosses yours.
- Display: 15-inch main, 7-inch backup
- Flight computers: 3, voting
- Traffic: Broadcast and receive, with alerts
- Navigation: Dual satellite receivers with inertial backup
- Route planning: In-cabin and on your phone
- Geofencing: Keeps you out of restricted airspace
- Return to pad: One press, automatic
- Updates: Over the air, only while on the ground
T,
        ],
        'performance' => [
            'title' => 'Performance', 'sort' => 30,
            'summary' => 'Speed, climb and the numbers behind them.',
            'body' => <<<'T'
Quick to climb, efficient to cruise

Vertical lift is the most demanding part of any flight, so the rotors and battery are sized for it with margin to spare. Once the wing takes over, cruising needs only a fraction of that power. The vehicle is tuned to spend most of its flight in that efficient state.

Typical trips are 30 to 150 km, which the vehicle covers with a comfortable reserve. A full 320 km is possible at cruise speed with the reserve intact.

- Top speed: 280 km/h
- Cruise speed: 240 km/h
- Maximum range: 320 km, with reserve
- Vertical climb: 4 m/s
- Forward climb: 8 m/s
- Service ceiling: 3,000 m
- Sound in hover: 62 dBA at 150 m
- Sound in cruise: 48 dBA at 300 m
T,
        ],
        'power' => [
            'title' => 'Power and range', 'sort' => 40,
            'summary' => 'What drives it, how far it goes and how it recharges.',
            'body' => <<<'T'
Two batteries, one purpose

The vehicle carries two separate battery packs. Each can power the aircraft to a safe landing on its own, so the loss of one pack is an inconvenience and not an emergency. Packs are liquid-cooled and monitored cell by cell.

At home, a 22 kW charger fills the packs overnight. At a public fast charger the vehicle charges from 10 to 80 percent in about 35 minutes. The charger is included with your allocation.

- Battery: 120 kWh usable, two packs
- Motors: 9, one per rotor and one for the propeller
- Fast charge, 10 to 80%: 35 min
- Home charge, 0 to 100%: 6 hours
- Charger included: 22 kW home unit
- Battery warranty: 8 years
- Battery monitoring: Cell by cell, live in the app
T,
        ],
        'interior' => [
            'title' => 'Interior and comfort', 'sort' => 50,
            'summary' => 'The cabin, the seats and the controls.',
            'body' => <<<'T'
A cabin that feels like a quiet room

Two seats sit side by side in a cabin with a wide, curved canopy and clear views up and down. The seats are heated, adjust in six directions and can be set up for each pilot with a memory profile. Wide doors open upward and stay open on their own, so getting in is easy even in tight spaces.

Insulation and active noise cancellation keep the cabin quiet enough to talk at a normal volume. A climate system heats and cools the cabin and keeps the canopy clear of fog and ice.

- Seats: 2, side by side, heated and adjustable
- Cabin width: 1.25 m
- Luggage: 90 litres, behind the seats
- Canopy: Wide, with UV-filtering glass
- Climate: Dual-zone, with canopy demisting
- Audio: Active noise cancellation, 6 speakers
- Connectivity: Phone pairing and charging
- Controls: Single stick, throttle and touch display
T,
        ],
        'safety' => [
            'title' => 'Safety systems', 'sort' => 60,
            'summary' => 'The layers of protection built into every flight.',
            'body' => <<<'T'
Redundancy first, then a parachute

The design assumes that parts can fail. Any two of the eight lift rotors can stop without the aircraft losing control. Power, flight computers and sensors are duplicated or tripled. The two battery packs are independent of each other.

If every other measure is exhausted, a whole-aircraft parachute opens above the cabin and lowers the vehicle and its occupants to the ground. The cabin around you is a carbon-fibre safety cell designed to absorb the energy of a hard landing.

- Rotor redundancy: Any 2 of 8 can fail
- Flight computers: Triple, with voting
- Batteries: Two independent packs
- Parachute: Whole-aircraft, deploys above 60 m
- Safety cell: Carbon-fibre cabin, tested to crash loads
- Detect and avoid: Radar and camera, plus traffic broadcast
- Fire protection: Cell-level isolation and cabin sensors
- Automatic landing: Triggers on low battery or pilot incapacity
T,
        ],
        'training' => [
            'title' => 'Pilot training', 'sort' => 70,
            'summary' => 'What you will learn and how long it takes.',
            'body' => <<<'T'
From first lesson to first solo

Training is built for people who have never flown. It starts in a simulator that matches your cockpit, moves to dual-control flights with an instructor beside you, and ends with supervised solo flights from your own pad. Most pilots complete the programme in about eight weeks of part-time study.

Pilot certification rules differ by country and change over time. Your instructor will tell you what is needed where you plan to fly, and we help you through the process.

- Ground school: 20 hours, online and in person
- Simulator: 10 hours
- Dual flight: 15 hours with an instructor
- Supervised solo: 5 hours
- Medical: Standard aviation medical required
- Programme length: About 8 weeks, part-time
- Included: Pioneer owners only, at no extra charge
T,
        ],
        'delivery' => [
            'title' => 'Delivery and ownership', 'sort' => 80,
            'summary' => 'From your allocation to the day it arrives, and what ownership includes.',
            'body' => <<<'T'
Handed over, not just shipped

Your allocation is reserved with a deposit that is held in escrow, and the balance is due within ten days after we confirm your allocation milestone. Allocations are then built in the order they were reserved. We will keep you informed at every stage, from the start of your build to final testing.

On delivery day you collect the vehicle at our delivery centre, fly it with your instructor, and fly home. A mobile service team looks after maintenance at your location, and software updates arrive over the air.

- Estimated delivery: 18 to 24 months from reservation
- Handover: At the delivery centre, with a first flight
- Warranty: 4 years, unlimited hours
- Service: Mobile team, at your location
- Insurance: We arrange cover quotes for you
- Included: Home charger, cover, training programme
- Updates: Over the air
T,
        ],
        'specs' => [
            'title' => 'Specifications', 'sort' => 90,
            'summary' => 'Dimensions, weights and capabilities in full.',
            'body' => <<<'T'
Every figure for the pioneer edition. Specifications are targets and may change before delivery.

## Exterior
- Construction: Carbon-fibre monocoque
- Finish: 7-layer aerospace paint, 6 colours
- Lighting: Integrated LED navigation and landing lights
- Doors: Two gull-wing, hands-free

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
- Sound in hover: 62 dBA at 150 m
- Sound in cruise: 48 dBA at 300 m

## Flight
- Takeoff and landing: Vertical, 12 m pad
- Transition: Automatic, about 25 seconds
- Control: Single stick and throttle
- Hands-off hold: Heading, altitude and speed
- Assisted landing: Pad recognition by camera
- Wind limit: 35 km/h gusting

## Avionics
- Display: 15-inch main, 7-inch backup
- Flight computers: 3, voting
- Traffic: Broadcast and receive, with alerts
- Navigation: Dual satellite receivers with inertial backup
- Route planning: In-cabin and on your phone
- Geofencing: Keeps you out of restricted airspace
- Return to pad: One press, automatic
- Updates: Over the air, only while on the ground

## Power
- Battery: 120 kWh usable, two packs
- Motors: 9, one per rotor and one for the propeller
- Lift rotors: 8
- Propeller: 1, rear
- Fast charge, 10 to 80%: 35 min
- Home charge: 22 kW, 6 hours
- Charger included: 22 kW home unit
- Battery monitoring: Cell by cell, live in the app

## Cabin
- Seats: 2, side by side, heated and adjustable
- Canopy: Wide, with UV-filtering glass
- Climate: Dual-zone, with canopy demisting
- Audio: Active noise cancellation, 6 speakers
- Connectivity: Phone pairing and charging
- Controls: Single stick, throttle and touch display

## Safety
- Rotor redundancy: Any 2 of 8 can fail
- Flight computers: Triple, with voting
- Batteries: Two independent packs
- Parachute: Whole-aircraft, deploys above 60 m
- Safety cell: Carbon-fibre cabin, tested to crash loads
- Detect and avoid: Radar and camera, plus traffic broadcast
- Fire protection: Cell-level isolation and cabin sensors
- Automatic landing: Triggers on low battery or pilot incapacity

## Training
- Ground school: 20 hours, online and in person
- Simulator: 10 hours
- Dual flight: 15 hours with an instructor
- Supervised solo: 5 hours
- Medical: Standard aviation medical required
- Programme length: About 8 weeks, part-time
- Included: Pioneer owners only, at no extra charge

## Ownership
- Estimated delivery: 18 to 24 months from reservation
- Handover: At the delivery centre, with a first flight
- Warranty: 4 years, unlimited hours
- Battery warranty: 8 years
- Service: Mobile team, at your location
- Insurance: We arrange cover quotes for you
- Included: Home charger, cover, training programme
- Price: Pioneer allocation, see your dashboard
T,
        ],
    ],
];
