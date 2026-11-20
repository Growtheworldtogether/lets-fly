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
