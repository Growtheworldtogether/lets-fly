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
- Range: 480 km
- Top speed: 370+ km/h
- Seats: 1
- Fast charge: 10 min
T,
        ],
        'intro' => [
            'title' => 'Built around the pilot', 'sort' => 5,
            'summary' => 'A single-seat electric vehicle that drives on the road and lifts off for short thruster flights.',
            'body' => <<<'T'
{vehicle} is a single-seat electric vehicle that drives like a hypercar, then lifts off the ground on cold-gas thrusters for short, controlled hops. Active aerodynamics shape the air around it, and a multi-mode suction system pulls it onto the road. It needs no runway and is designed to be owned and driven by one person.

Pioneer allocations are limited, invite-only and delivered in the order they are reserved. Everything on this page describes the pioneer edition.
T,
        ],
        'exterior' => [
            'title' => 'Exterior and design', 'sort' => 10,
            'summary' => 'The shape, the materials and the details you see first.',
            'body' => <<<'T'
A face you know from a long way off

From the front, the {vehicle} is built from flat planes and sharp creases, with no curves added for decoration. The whole body is one carbon-fibre monocoque, cured in a single piece, so there are no panel seams to break up the airflow.

Two slim lamps and a single light bar across the nose are the only detail on the face. Wide glass under a low roof gives the driver a clear view forward, and the low, wide stance keeps the vehicle steady on the ground. At the tail, a two-element wing rises for braking and cornering and retracts flush into the bodywork when parked, while skirts drop around the floor and vents open in the fenders and hood.

There is one colour, Ano White: an aerospace-inspired finish engineered for UV and cryogenic protection, electrostatic dissipation and corrosion resistance, set off by exposed titanium accents and gold-glazed tinted glass.

- Length: 4.5 m
- Width: 2.0 m
- Wheelbase: 2.83 m
- Height: 1.2 m
- Construction: Carbon-fibre monocoque
- Aerodynamics: Active wing, drop-down skirts, fender and hood vents
- Finish: Ano White, one colour, with exposed titanium accents
- Lighting: Integrated LED lights
- Doors: Two gull-wing, hands-free
T,
        ],
        'flight' => [
            'title' => 'Flight and handling', 'sort' => 20,
            'summary' => 'How it drives, lifts off and lands.',
            'body' => <<<'T'
Four ducts at the tail, one clean line above them

Seen from above, the tail shows how the vehicle lifts. Four thruster outlets sit under a carbon-fibre diffuser, with a single continuous light bar running above them. On the road the vehicle drives on its wheels like any electric car. To fly, you select flight mode: ten cold-gas thrusters, four at the rear and six around the front and sides, fire together and lift the vehicle clear of the tarmac, with no ramp or marked pad needed.

You fly it with a steer-by-wire yoke and a throttle. The flight computer adjusts each thruster many times a second and holds your attitude and heading when you let go. A full tank gives about five seconds of thrust, enough for short hops and rolls, and landing is the same sequence in reverse, with the suction system pressing the vehicle onto the surface before the wheels take the weight.

- Takeoff and landing: Vertical, from flat tarmac
- Thrust per tank: About 5 seconds
- Flight height: Up to 6 m
- Control: Yoke and throttle
- Hands-off hold: Attitude and heading
- Wind limit: 35 km/h gusting
T,
        ],
        'avionics' => [
            'title' => 'Avionics and autopilot', 'sort' => 25,
            'summary' => 'The systems that see, decide and help you fly.',
            'body' => <<<'T'
Doors up, flight deck ready

Two gull-wing doors lift clear of the cabin and stay open on their own, and the wide canopy lets daylight fall across the whole flight deck. A central touchscreen shows your route, speed, battery state and gas pressure, two small screens ahead of the yoke repeat the essentials, and a small red display on the windshield shows speed and lap times without taking your eyes off the road.

Three independent flight computers vote on every control command. If one disagrees with the other two it is shut out and the drive or flight continues. All controls, steering, braking and throttle, are electronic, so the computer can trim them in a fraction of a second, and the steer-by-wire system gives clear feedback on every surface.
- Displays: Central touchscreen, two forward screens, windshield display
- Controls: Steer-by-wire, brake-by-wire and throttle-by-wire
- Flight computers: 3, voting
- Navigation: Dual satellite receivers with inertial backup
- Route planning: In-cabin and on your phone
- Geofencing: Keeps you out of restricted airspace
- Updates: Over the air, only while on the ground
T,
        ],
        'performance' => [
            'title' => 'Performance', 'sort' => 30,
            'summary' => 'Speed, lift and the numbers behind them.',
            'body' => <<<'T'
Ready on the pad, quick to the sky

Doors up, driver in, and the vehicle rolls out like any hypercar. Two rear motors deliver more than a megawatt, enough to reach 100 km/h in under 0.6 seconds, and the active aero keeps the car flat and stable at speed. Flight mode adds the thrusters for short, controlled hops, and the suction system pulls air out from under the car to hold it firmly to the surface at both ends of every flight.

Typical trips are 30 to 150 km, which the vehicle covers with a comfortable reserve, and a single charge is targeted at up to 480 km. Select Track mode and the car lowers and its suspension changes, then choose high downforce or low drag as the track needs. The fans and sealing skirts keep working at any speed, so downforce is there even at zero speed.

- 0 to 100 km/h: Under 0.6 s
- Top speed: 370+ km/h
- Range: 480 km target
- Power: Over 1 MW
- Torque: 1,400+ Nm
- Thrust per tank: About 5 seconds
T,
        ],
        'gravity' => [
            'title' => 'Gravity defying', 'sort' => 35,
            'summary' => 'How the vehicle lifts and stays steady in the air.',
            'body' => <<<'T'
Gravity Defying

Ten cold-gas thrusters burn nothing: they release compressed air from two carbon-wrapped tanks, delivering more than 80 kN of thrust. All-electric actuators trim each thruster in a fraction of a second, so the vehicle lifts, steadies and rolls under control.
T,
        ],
        'power' => [
            'title' => 'Power and range', 'sort' => 40,
            'summary' => 'What drives it, what lifts it and how it recharges.',
            'body' => <<<'T'
Sprung to land, charged in minutes

Under the skin, a sprung wheel assembly on a rigid frame absorbs touchdown, so landing feels like arriving rather than dropping. Two electric motors drive the rear wheels, one per wheel, fed by a single 800-volt battery pack that is liquid-cooled and monitored cell by cell.

At a public fast charger the vehicle charges from 15 to 80 percent in about 10 minutes. At home, the included 22 kW charger fills the pack overnight, and the onboard compressor refills the thruster tanks from the battery.

- Fast charge, 15 to 80%: 10 min
- Home charge, 0 to 100%: 8 hours
- Charger included: 22 kW home unit
- Battery: 120 kWh usable, one 800 V pack
- Motors: 2, rear axle, one per rear wheel
- Thrusters: 10 cold-gas, 4 rear and 6 front and side
- Thruster air: 600 litres at 690 bar, two tanks
- Tank refill: Onboard compressor, under 30 minutes
- Battery monitoring: Cell by cell, live in the app
T,
        ],
        'interior' => [
            'title' => 'Interior and comfort', 'sort' => 50,
            'summary' => 'The cabin, the seat and the controls.',
            'body' => <<<'T'
A dark, quiet cabin built around one pale seat

One seat sits under a wide, curved canopy, facing a single central display. The seat is Ultrawhite with grey microsuede accents, set against exposed carbon fibre, magnesium and titanium trim and a soft red glow along each door. It is heated and moulded into the chassis, and the seatback angle and pedals adjust to fit the driver. Wide doors open upward and stay open on their own, so getting in is easy even in tight spaces.

A next-generation heat pump heats and cools the cabin, and gold-glazed glass rejects infrared heat from the sun. A wireless charger keeps your phone powered.

- Seats: 1
- Cabin width: 1.4 m
- Display: 1 central
- Seat features: Fixed and heated, backrest and pedals adjust
- Colour: Ultrawhite, grey microsuede accents
- Glass: Gold-glazed, infrared-rejecting
- Climate: Heat pump
- Connectivity: Wireless phone charger
- Controls: Yoke, throttle and touch display
T,
        ],
        'safety' => [
            'title' => 'Safety systems', 'sort' => 60,
            'summary' => 'The layers of protection built into every drive and flight.',
            'body' => <<<'T'
Steady in the air, redundant everywhere

The design assumes that parts can fail. All-electric actuators trim the thrusters in fractions of a second, so the vehicle holds level through every hop. Power, flight computers and sensors are duplicated or tripled.

On the road, predictive sensors watch behind you, and if a rear impact is coming the active wing retracts flush into the tail before it happens. The cabin around you is a carbon-fibre safety cell designed to absorb the energy of a hard landing.

- Stabilisation: Electric actuators, trimmed in fractions of a second
- Flight computers: Triple, with voting
- Rear impact: Wing retracts before impact
- Safety cell: Carbon-fibre cabin, tested to crash loads
- Fire protection: Cell-level isolation and cabin sensors
T,
        ],
        'training' => [
            'title' => 'Pilot training', 'sort' => 70,
            'summary' => 'What you will learn and how long it takes.',
            'body' => <<<'T'
From first lesson to first solo

Training is built for people who have never flown. It starts in a simulator that matches your cockpit, moves to dual-control flights with an instructor beside you, and ends with supervised solo flights on a marked landing area. Most pilots complete the programme in about eight weeks of part-time study.

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
        'reserving' => [
            'title' => 'How reserving works', 'sort' => 75,
            'summary' => 'How the deposit, escrow and balance work.',
            'body' => <<<'T'
Two payments, both held in escrow

To reserve, you accept the allocation agreement and wire a deposit. The deposit is held in escrow by {agent}, and not by us. Nothing is charged to a card and nothing is paid on this website.

When your allocation milestone is confirmed, we email your balance invoice, and you wire the balance to the same escrow account within {days} days. If you need more time, tell us before the due date.

Send money only to the account shown on your dashboard. We will never ask you to send it anywhere else. Your agreement explains what happens if you cancel.

- Payments: 2
- Balance due: {days} days
- Held in escrow: 100%
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
- Aerodynamics: Active wing, drop-down skirts, fender and hood vents
- Finish: Ano White, one colour, with exposed titanium accents
- Lighting: Integrated LED lights
- Doors: Two gull-wing, hands-free

## Dimensions
- Length: 4.5 m
- Width: 2.0 m
- Wheelbase: 2.83 m
- Height: 1.2 m
- Cabin width: 1.4 m

## Weights
- Empty weight: Under 1,700 kg
- Seats: 1

## Performance
- 0 to 100 km/h: Under 0.6 s
- Top speed: 370+ km/h
- Range: 480 km target
- Power: Over 1 MW
- Torque: 1,400+ Nm
- Thrust per tank: About 5 seconds
- Flight height: Up to 6 m

## Flight
- Takeoff and landing: Vertical, from flat tarmac
- Lift: 10 cold-gas thrusters, over 80 kN
- Control: Yoke and throttle
- Hands-off hold: Attitude and heading
- Wind limit: 35 km/h gusting

## Active aero and suction
- Rear wing: Two-element, retracts flush into the tail
- Skirts: 4, drop-down, seal the underbody
- Wing modes: Road, low drag, high downforce
- Track mode: Lowers the car and changes the suspension
- Suction fans: 4 electric, about 20 kW each
- Downforce at standstill: Up to 2 G
- Inverted hold: Yes, even when stationary

## Avionics
- Displays: Central touchscreen, two forward screens, windshield display
- Controls: Steer-by-wire, brake-by-wire and throttle-by-wire
- Flight computers: 3, voting
- Navigation: Dual satellite receivers with inertial backup
- Route planning: In-cabin and on your phone
- Geofencing: Keeps you out of restricted airspace
- Updates: Over the air, only while on the ground

## Power
- Battery: 120 kWh usable, one 800 V pack
- Motors: 2, rear axle, one per rear wheel
- Thrusters: 10 cold-gas, 4 rear and 6 front and side
- Thruster air: 600 litres at 690 bar, two tanks
- Tank refill: Onboard compressor, under 30 minutes
- Fast charge, 15 to 80%: 10 min
- Home charge: 22 kW, 8 hours
- Charger included: 22 kW home unit
- Battery monitoring: Cell by cell, live in the app

## Cabin
- Seats: 1, fixed, heated, backrest and pedals adjust
- Glass: Gold-glazed, infrared-rejecting
- Climate: Heat pump
- Connectivity: Wireless phone charger
- Controls: Yoke, throttle and touch display

## Safety
- Stabilisation: Electric actuators, trimmed in fractions of a second
- Flight computers: Triple, with voting
- Rear impact: Wing retracts before impact
- Safety cell: Carbon-fibre cabin, tested to crash loads
- Fire protection: Cell-level isolation and cabin sensors

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
- Service: Mobile team, at your location
- Insurance: We arrange cover quotes for you
- Included: Home charger, cover, training programme
- Price: Pioneer allocation, see your dashboard
T,
        ],
    ],
];
