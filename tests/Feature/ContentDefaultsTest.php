<?php

use App\Models\PageSection;

beforeEach(fn () => $this->artisan('migrate:fresh'));

it('moves a section that still has an earlier default text to the current default', function () {
    PageSection::ensureDefaults();
    $earlier = <<<'T'
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
T;
    PageSection::where('slug', 'performance')->update(['body' => str_replace("\n", "\r\n", $earlier)]);

    PageSection::ensureDefaults();

    expect(PageSection::where('slug', 'performance')->value('body'))
        ->toBe(config('vehicle.sections.performance.body'));
});

it('leaves a section an admin has rewritten alone', function () {
    PageSection::ensureDefaults();
    PageSection::where('slug', 'performance')->update(['body' => "My own heading\n\nMy own text."]);

    PageSection::ensureDefaults();

    expect(PageSection::where('slug', 'performance')->value('body'))->toStartWith('My own heading');
});

it('describes the vehicle as thrust-lifted, with no rotors, wing or propeller', function () {
    $all = collect(config('vehicle.sections'))->pluck('body')->implode("\n");

    expect($all)->toContain('cold-gas')->toContain('suction')->toContain('Active aero')
        ->not->toContain('rotor')->not->toContain('propeller')->not->toContain('Wingspan');
});
