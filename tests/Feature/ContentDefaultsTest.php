<?php

use App\Models\PageSection;

beforeEach(fn () => $this->artisan('migrate:fresh'));

it('moves a section that still has an old default text to the current default', function () {
    PageSection::ensureDefaults();
    $old = "Quick to climb, efficient to cruise\r\n\r\nOld paragraph.\r\n\r\n- Top speed: 1 km/h";
    PageSection::where('slug', 'performance')->update(['body' => $old]);

    PageSection::ensureDefaults();

    expect(PageSection::where('slug', 'performance')->value('body'))
        ->toStartWith('Ready on the pad, quick to the sky');
});

it('leaves a section whose heading an admin has rewritten alone', function () {
    PageSection::ensureDefaults();
    PageSection::where('slug', 'performance')->update(['body' => "My own heading\n\nMy own text."]);

    PageSection::ensureDefaults();

    expect(PageSection::where('slug', 'performance')->value('body'))->toStartWith('My own heading');
});

it('corrects wording that does not match the vehicle in sections already stored', function () {
    PageSection::ensureDefaults();
    PageSection::where('slug', 'flight')->update(['body' => "Heading\n\nYou fly it with a single stick and a throttle lever.\n\n- Control: Single stick and throttle\n- Propeller: 1, rear"]);

    PageSection::ensureDefaults();

    $body = PageSection::where('slug', 'flight')->value('body');
    expect($body)->toContain('with a yoke and a throttle lever')
        ->toContain('- Control: Yoke and throttle')
        ->toContain('- Rear ducted fans: 4')
        ->not->toContain('single stick');
});
