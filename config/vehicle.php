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
            'summary' => 'A two-seat electric vehicle that drives on the road and lifts off for short flights.',
            'body' => <<<'T'
{vehicle} is a two-seat electric vehicle that drives like a sports car, then lifts off the ground on cold-gas thrusters for short, controlled flights. Active aerodynamics shape the air around it, and a multi-mode suction system keeps it planted on the road. It needs no runway and is designed to be owned and flown by one person.

Pioneer allocations are limited, invite-only and delivered in the order they are reserved. Everything on this page describes the pioneer edition.
T,
        ],
        'exterior' => [
            'title' => 'Exterior and design', 'sort' => 10,
            'summary' => 'The shape, the materials and the details you see first.',
            'body' => <<<'T'
A face you know from a long way off

From the front, the {vehicle} is built from flat planes and sharp creases, with no curves added for decoration. The whole body is one carbon-fibre monocoque, cured in a single piece, so there are no panel seams to break up the airflow.

Two slim lamps and a single light bar across the nose are the only detail on the face. Wide glass under a low roof gives the pilot a clear view forward, and the low, wide stance keeps the vehicle steady on the ground. At the tail, an active rear wing and underbody flaps move to shape the airflow at every speed and lie flat when the vehicle is parked. It is compact enough to park in a standard garage.

- Length: 4.9 m
- Width: 2.0 m
- Height: 1.2 m
- Construction: Carbon-fibre monocoque
- Aerodynamics: Active rear wing and underbody flaps
- Finish: 7-layer aerospace paint, 6 colours
- Lighting: Integrated LED lights
- Doors: Two gull-wing, hands-free
T,
        ],
        'flight' => [
            'title' => 'Flight and handling', 'sort' => 20,
            'summary' => 'How it drives, lifts off and lands.',
            'body' => <<<'T'
Four ducts at the tail, one clean line above them

Seen from above, the tail shows how the vehicle lifts. Four thruster outlets sit under a carbon-fibre diffuser, with a single continuous light bar running above them. On the road the vehicle drives on its wheels like any electric car. To fly, you select flight mode: the cold-gas thrusters fire together and lift the vehicle clear of the ground, while the active aero surfaces trim the air flowing over the body to keep it level.

You fly it with a yoke and a throttle lever. The flight computer adjusts each thruster many times a second, holds your height and heading when you let go, and keeps the vehicle inside its safe limits. Landing is the same sequence in reverse, and the suction system settles the vehicle onto the surface before the wheels take the weight. An assisted landing mode can bring you down on a marked pad on its own.

- Takeoff and landing: Vertical, from a flat surface
- Flight time: Up to 8 minutes per flight
- Flight height: Up to 30 m
- Control: Yoke and throttle
- Hands-off hold: Height and heading
- Assisted landing: Pad recognition by camera
- Wind limit: 35 km/h gusting
T,
        ],
        'avionics' => [
            'title' => 'Avionics and autopilot', 'sort' => 25,
            'summary' => 'The systems that see, decide and help you fly.',
            'body' => <<<'T'
Doors up, flight deck ready

Two gull-wing doors lift clear of the cabin and stay open on their own, and the wide canopy lets daylight fall across the whole flight deck. A 15-inch glass display shows your route, height, speed, battery state, gas pressure and traffic on a single screen. A second, smaller display shows the same essentials on a separate circuit, so a failure of one never leaves you without instruments.

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
            'summary' => 'Speed, lift and the numbers behind them.',
            'body' => <<<'T'
Ready on the pad, quick to the sky

Doors up, pilot in, and the vehicle rolls out like any sports car. On the road the electric motors give instant torque, and the active aero keeps the car flat and stable at speed. Flight mode adds the thrusters for short, controlled lifts over obstacles, water or traffic, and the suction system holds the vehicle firmly to the surface at both ends of every flight.

Typical trips are 30 to 150 km on the road, which the vehicle covers with a comfortable reserve. A full 320 km is possible at cruise speed with the reserve intact.

- Top speed: 280 km/h
- Cruise speed: 240 km/h
- 0 to 100 km/h: 2.1 s
- Maximum range: 320 km, with reserve
- Flight time: Up to 8 minutes per flight
- Flight height: Up to 30 m
T,
        ],
        'gravity' => [
            'title' => 'Gravity defying', 'sort' => 35,
            'summary' => 'How the vehicle lifts and stays steady in the air.',
            'body' => <<<'T'
Gravity Defying

Cold-gas thrusters use all-electric valves to deliver precise bursts of thrust, correcting the vehicle's attitude in a fraction of a second. Fired together they lift the vehicle clear of the ground, and fired in pairs they steer and steady it in the air.
T,
        ],
        'power' => [
            'title' => 'Power and range', 'sort' => 40,
            'summary' => 'What drives it, what lifts it and how it recharges.',
            'body' => <<<'T'
Sprung to land, powered twice over

Under the skin, a sprung wheel assembly on a rigid frame absorbs touchdown, so landing feels like arriving rather than dropping. The vehicle carries two separate battery packs. Each can power it to a safe landing on its own, so the loss of one pack is an inconvenience and not an emergency. Packs are liquid-cooled and monitored cell by cell.

At home, a 22 kW charger fills the packs overnight and refills the thruster gas tank. At a public fast charger the vehicle charges from 10 to 80 percent in about 35 minutes. The charger is included with your allocation.

- Fast charge, 10 to 80%: 35 min
- Home charge, 0 to 100%: 6 hours
- Charger included: 22 kW home unit
- Battery: 120 kWh usable, two packs
- Motors: 4, one per wheel
- Thrusters: Cold-gas, 4 rear and 4 corner
- Thruster gas refill: About 10 minutes at home
- Battery warranty: 8 years
- Battery monitoring: Cell by cell, live in the app
T,
        ],
        'interior' => [
            'title' => 'Interior and comfort', 'sort' => 50,
            'summary' => 'The cabin, the seats and the controls.',
            'body' => <<<'T'
A dark, quiet cabin with two pale seats

Two seats sit side by side under a wide, curved canopy, facing a single central display. The seats are pale against a dark cabin, with a soft red glow running along each door. They are heated, adjust in six directions and can be set up for each pilot with a memory profile. Wide doors open upward and stay open on their own, so getting in is easy even in tight spaces.

Insulation and active noise cancellation keep the cabin quiet enough to talk at a normal volume. A climate system heats and cools the cabin and keeps the canopy clear of fog and ice.

- Seats: 2, side by side, heated and adjustable
- Cabin width: 1.25 m
- Luggage: 90 litres, behind the seats
- Canopy: Wide, with UV-filtering glass
- Climate: Dual-zone, with canopy demisting
- Audio: Active noise cancellation, 6 speakers
- Connectivity: Phone pairing and charging
- Controls: Yoke, throttle and touch display
T,
        ],
        'safety' => [
            'title' => 'Safety systems', 'sort' => 60,
            'summary' => 'The layers of protection built into every drive and flight.',
            'body' => <<<'T'
Redundancy first, then a parachute

The design assumes that parts can fail. Any two of the eight thrusters can stop without the vehicle losing control, because the others rebalance within a fraction of a second. Power, flight computers and sensors are duplicated or tripled. The two battery packs are independent of each other.

If every other measure is exhausted, a whole-vehicle parachute opens above the cabin and lowers the vehicle and its occupants to the ground. The cabin around you is a carbon-fibre safety cell designed to absorb the energy of a hard landing.

- Thruster redundancy: Any 2 of 8 can fail
- Flight computers: Triple, with voting
- Batteries: Two independent packs
- Parachute: Whole-vehicle, deploys above 60 m
- Safety cell: Carbon-fibre cabin, tested to crash loads
- Detect and avoid: Radar and camera, plus traffic broadcast
- Fire protection: Cell-level isolation and cabin sensors
- Automatic landing: Triggers on low battery, low gas or pilot incapacity
T,
        ],
        'training' => [
            'title' => 'Pilot training', 'sort' => 70,
            'summary' => 'What you will learn and how long it takes.',
            'body' => <<<'T'
From first lesson to first solo

Training is built for people who have never flown. It starts in a simulator that matches your cockpit, moves to dual-control flights with an instructor beside you, and ends with supervised solo flights from your own marked pad. Most pilots complete the programme in about eight weeks of part-time study.

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

On delivery day you collect the vehicle at our delivery centre, fly it with your instructor, and drive home. A mobile service team looks after maintenance at your location, and software updates arrive over the air.

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
- Aerodynamics: Active rear wing and underbody flaps
- Finish: 7-layer aerospace paint, 6 colours
- Lighting: Integrated LED lights
- Doors: Two gull-wing, hands-free

## Dimensions
- Length: 4.9 m
- Width: 2.0 m
- Height: 1.2 m
- Cabin width: 1.25 m
- Luggage volume: 90 litres

## Weights
- Empty weight: 1,450 kg
- Payload: 240 kg
- Maximum weight: 1,690 kg
- Seats: 2

## Performance
- Top speed: 280 km/h
- Cruise speed: 240 km/h
- 0 to 100 km/h: 2.1 s
- Range: 320 km, with reserve
- Flight time: Up to 8 minutes per flight
- Flight height: Up to 30 m

## Flight
- Takeoff and landing: Vertical, from a flat surface
- Lift: Cold-gas thrusters, 4 rear and 4 corner
- Control: Yoke and throttle
- Hands-off hold: Height and heading
- Assisted landing: Pad recognition by camera
- Wind limit: 35 km/h gusting

## Active aero and suction
- Active aero: Rear wing and underbody flaps
- Suction system: Multi-mode, ground sealing
- Surface hold: Firm grip at any angle, at low speed
- Downforce modes: High downforce or low drag

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
- Motors: 4, one per wheel
- Thrusters: Cold-gas, 4 rear and 4 corner
- Thruster gas refill: About 10 minutes at home
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
- Controls: Yoke, throttle and touch display

## Safety
- Thruster redundancy: Any 2 of 8 can fail
- Flight computers: Triple, with voting
- Batteries: Two independent packs
- Parachute: Whole-vehicle, deploys above 60 m
- Safety cell: Carbon-fibre cabin, tested to crash loads
- Detect and avoid: Radar and camera, plus traffic broadcast
- Fire protection: Cell-level isolation and cabin sensors
- Automatic landing: Triggers on low battery, low gas or pilot incapacity

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
